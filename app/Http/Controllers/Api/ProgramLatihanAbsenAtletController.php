<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaborKategoriAtlet;
use App\Models\ProgramLatihan;
use App\Models\ProgramLatihanAbsenAtlet;
use App\Models\RekapAbsenProgramLatihan;
use App\Services\ProgramLatihanAbsenWindowService;
use App\Services\ProgramLatihanKehadiranService;
use App\Traits\ManagesRekapAbsenFoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ProgramLatihanAbsenAtletController extends Controller
{
    use ManagesRekapAbsenFoto;

    public function __construct(
        private ProgramLatihanKehadiranService $kehadiranService,
        private ProgramLatihanAbsenWindowService $absenWindowService
    ) {
    }

    public function index(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet', 'pelatih', 'tenagaPendukung']);

            $programLatihan = ProgramLatihan::findOrFail($programId);

            if (!$this->checkProgramAccess($programLatihan, $user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }

            $query = ProgramLatihanAbsenAtlet::query()
                ->with(['atlet', 'media'])
                ->where('program_latihan_id', $programId)
                ->orderByDesc('tanggal');

            $roleId = $user->current_role_id ?? null;

            if ($roleId == 35 && $user->atlet?->id) {
                $query->where('atlet_id', $user->atlet->id);
            } else {
                if (!Gate::allows('Program Latihan Rekap Absen')) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Anda tidak memiliki izin untuk melihat monitoring absen atlet.',
                    ], 403);
                }

                if ($request->filled('atlet_id')) {
                    $query->where('atlet_id', $request->atlet_id);
                }
            }

            if ($request->filled('tanggal')) {
                $query->whereDate('tanggal', $request->tanggal);
            }

            if ($request->filled('bulan')) {
                try {
                    $start = \Carbon\Carbon::createFromFormat('Y-m', $request->bulan)->startOfMonth();
                    $end = $start->copy()->endOfMonth();
                    $query->whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
                } catch (\Throwable) {
                    // ignore invalid bulan filter
                }
            }

            $records = $query->get()->map(fn ($row) => $this->formatAbsenAtletResponse($row));

            return response()->json([
                'status' => 'success',
                'data' => $records,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Absen Atlet error: ' . $e->getMessage(), [
                'program_id' => $programId,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data absen atlet.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getToday(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet']);

            if (($user->current_role_id ?? null) != 35 || !$user->atlet?->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fitur ini hanya untuk atlet.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::findOrFail($programId);

            if (!$programLatihan->wajib_absen_atlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Program ini tidak mewajibkan absen atlet.',
                ], 422);
            }

            if (!$this->checkProgramAccess($programLatihan, $user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }

            $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');

            $absen = ProgramLatihanAbsenAtlet::query()
                ->with('media')
                ->where('program_latihan_id', $programId)
                ->where('atlet_id', $user->atlet->id)
                ->where('tanggal', $today)
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $absen ? $this->formatAbsenAtletResponse($absen) : null,
                'message' => $absen ? null : 'Belum ada absen untuk hari ini.',
            ]);
        } catch (\Exception $e) {
            Log::error('Get Absen Atlet Today error: ' . $e->getMessage(), [
                'program_id' => $programId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil absen hari ini.',
            ], 500);
        }
    }

    public function store(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();
            $user->load(['atlet']);

            if (($user->current_role_id ?? null) != 35 || !$user->atlet?->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya atlet yang dapat melakukan absen.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::findOrFail($programId);

            if (!$programLatihan->wajib_absen_atlet) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Program ini tidak mewajibkan absen atlet.',
                ], 422);
            }

            if (!$this->checkProgramAccess($programLatihan, $user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }

            $today = \Carbon\Carbon::now('Asia/Jakarta')->format('Y-m-d');

            $request->validate([
                'tanggal' => 'nullable|date',
                'status' => ['nullable', Rule::in(['hadir', 'izin', 'sakit', 'alpha'])],
                'catatan' => 'nullable|string|max:1000',
                'foto_absen' => 'required|image|mimes:jpeg,png,gif,webp|max:5120',
                'foto_lokasi' => 'nullable|string',
            ]);

            $tanggal = $request->input('tanggal', $today);

            if ($tanggal !== $today) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Hanya dapat absen untuk tanggal hari ini.',
                ], 422);
            }

            if ($tanggal < $programLatihan->periode_mulai || $tanggal > $programLatihan->periode_selesai) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tanggal harus dalam periode program latihan.',
                ], 422);
            }

            $this->absenWindowService->assertWithinWindow($programLatihan);

            $existing = ProgramLatihanAbsenAtlet::query()
                ->where('program_latihan_id', $programId)
                ->where('atlet_id', $user->atlet->id)
                ->where('tanggal', $tanggal)
                ->first();

            $metadata = $this->parseFotoMetadata($request->input('foto_lokasi'));
            $waktuFoto = $metadata['waktu_foto'] ?? \Carbon\Carbon::now('Asia/Jakarta')->format('H:i:s');

            $rekapHariIni = RekapAbsenProgramLatihan::query()
                ->where('program_latihan_id', $programId)
                ->where('tanggal', $tanggal)
                ->first();

            $status = $request->input('status', 'hadir');
            $catatan = $request->input('catatan');

            if ($existing) {
                $existing->update([
                    'rekap_absen_program_latihan_id' => $rekapHariIni?->id,
                    'status' => $status,
                    'waktu_foto' => $waktuFoto,
                    'lokasi' => $metadata['lokasi'] ?? null,
                    'latitude' => $metadata['latitude'] ?? null,
                    'longitude' => $metadata['longitude'] ?? null,
                    'catatan' => $catatan,
                ]);

                if ($request->hasFile('foto_absen')) {
                    $existing->clearMediaCollection('foto_absen_atlet');
                    $this->uploadFotoAbsenAtletFromRequest($existing, $request, $tanggal);
                }

                $absenAtlet = $existing->fresh(['media', 'atlet']);
                $message = 'Absen berhasil diperbarui.';
                $httpCode = 200;
            } else {
                $absenAtlet = ProgramLatihanAbsenAtlet::create([
                    'program_latihan_id' => $programId,
                    'rekap_absen_program_latihan_id' => $rekapHariIni?->id,
                    'atlet_id' => $user->atlet->id,
                    'tanggal' => $tanggal,
                    'status' => $status,
                    'waktu_foto' => $waktuFoto,
                    'lokasi' => $metadata['lokasi'] ?? null,
                    'latitude' => $metadata['latitude'] ?? null,
                    'longitude' => $metadata['longitude'] ?? null,
                    'catatan' => $catatan,
                    'created_by' => $user->id,
                ]);

                $this->uploadFotoAbsenAtletFromRequest($absenAtlet, $request, $tanggal);
                $absenAtlet->load(['media', 'atlet']);
                $message = 'Absen berhasil disimpan.';
                $httpCode = 201;
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $this->formatAbsenAtletResponse($absenAtlet),
                'ringkasan' => $this->kehadiranService->getAtletAbsenSummary((int) $programId, (int) $user->atlet->id),
            ], $httpCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Store Absen Atlet error: ' . $e->getMessage(), [
                'program_id' => $programId,
                'user_id' => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan absen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function kehadiran(Request $request, $programId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();

            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat ringkasan kehadiran.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::findOrFail($programId);

            if (!$this->checkProgramAccess($programLatihan, $user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }

            $bulan = $request->query('bulan');

            return response()->json([
                'status' => 'success',
                'data' => $this->kehadiranService->getRingkasanProgram((int) $programId, $bulan),
            ]);
        } catch (\Exception $e) {
            Log::error('Get Kehadiran error: ' . $e->getMessage(), [
                'program_id' => $programId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil ringkasan kehadiran.',
            ], 500);
        }
    }

    public function riwayatAtlet(Request $request, $programId, $atletId): JsonResponse
    {
        try {
            $user = $request->user()->fresh();

            if (!Gate::allows('Program Latihan Rekap Absen')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki izin untuk melihat riwayat absen atlet.',
                ], 403);
            }

            $programLatihan = ProgramLatihan::findOrFail($programId);

            if (!$this->checkProgramAccess($programLatihan, $user)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak memiliki akses ke program latihan ini.',
                ], 403);
            }

            $riwayat = $this->kehadiranService->getRiwayatAtlet((int) $programId, (int) $atletId);

            return response()->json([
                'status' => 'success',
                'data' => $riwayat,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Riwayat Atlet error: ' . $e->getMessage(), [
                'program_id' => $programId,
                'atlet_id' => $atletId,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil riwayat absen atlet.',
            ], 500);
        }
    }

    private function formatAbsenAtletResponse(ProgramLatihanAbsenAtlet $row): array
    {
        $media = $row->getFirstMedia('foto_absen_atlet');

        return [
            'id' => $row->id,
            'program_latihan_id' => $row->program_latihan_id,
            'atlet_id' => $row->atlet_id,
            'atlet_nama' => $row->atlet?->nama,
            'tanggal' => $row->tanggal?->format('Y-m-d'),
            'status' => $row->status,
            'status_label' => $this->kehadiranService->statusLabel($row->status),
            'waktu_foto' => $row->waktu_foto,
            'lokasi' => $row->lokasi,
            'latitude' => $row->latitude,
            'longitude' => $row->longitude,
            'catatan' => $row->catatan,
            'foto' => $media ? $this->formatFotoAbsenMedia($media) : null,
            'created_at' => $row->created_at,
        ];
    }

    private function checkProgramAccess(ProgramLatihan $programLatihan, $user): bool
    {
        $roleId = $user->current_role_id ?? null;

        if (!in_array($roleId, [35, 36, 37])) {
            return true;
        }

        $user->load(['atlet', 'pelatih', 'tenagaPendukung']);

        if ($roleId == 35 && $user->atlet && $user->atlet->id) {
            return CaborKategoriAtlet::where('atlet_id', $user->atlet->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }

        if ($roleId == 36 && $user->pelatih && $user->pelatih->id) {
            return \App\Models\CaborKategoriPelatih::where('pelatih_id', $user->pelatih->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }

        if ($roleId == 37 && $user->tenagaPendukung && $user->tenagaPendukung->id) {
            return \App\Models\CaborKategoriTenagaPendukung::where('tenaga_pendukung_id', $user->tenagaPendukung->id)
                ->where('cabor_id', $programLatihan->cabor_id)
                ->where('cabor_kategori_id', $programLatihan->cabor_kategori_id)
                ->whereNull('deleted_at')
                ->exists();
        }

        return false;
    }
}
