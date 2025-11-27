<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pemeriksaan;
use App\Models\CaborKategoriAtlet;
use App\Models\CaborKategoriPelatih;
use App\Models\CaborKategoriTenagaPendukung;
use App\Models\PemeriksaanPeserta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PemeriksaanPesertaController extends Controller
{
    /**
     * List peserta pemeriksaan (mobile)
     */
    public function index(Request $request, int $pemeriksaanId): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::with(['caborKategori'])->find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $jenis  = $request->get('jenis', null); // atlet|pelatih|tenaga-pendukung (opsional)
        $search = $request->get('search', '');

        $query = PemeriksaanPeserta::with(['peserta', 'status'])
            ->where('pemeriksaan_id', $pemeriksaanId);

        if ($jenis) {
            $map = [
                'atlet'            => 'App\\Models\\Atlet',
                'pelatih'          => 'App\\Models\\Pelatih',
                'tenaga-pendukung' => 'App\\Models\\TenagaPendukung',
            ];
            if (isset($map[$jenis])) {
                $query->where('peserta_type', $map[$jenis]);
            }
        }

        if ($search) {
            $query->whereHas('peserta', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'desc')->get()->map(function ($item) {
            $peserta = $item->peserta;
            return [
                'id'      => $item->id,
                'peserta' => [
                    'id'            => $peserta?->id,
                    'nama'          => $peserta?->nama,
                    'foto'          => $peserta?->foto,
                    'jenis_kelamin' => $peserta?->jenis_kelamin,
                    'tanggal_lahir' => $peserta?->tanggal_lahir,
                ],
                'status' => [
                    'id'   => $item->status?->id,
                    'nama' => $item->status?->nama,
                ],
                'catatan_umum' => $item->catatan_umum,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'Data peserta pemeriksaan berhasil diambil',
            'data'    => $items,
        ]);
    }

    /**
     * Create peserta pemeriksaan (mobile)
     */
    public function store(Request $request, int $pemeriksaanId): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $request->validate([
            'atlet_ids'                 => 'nullable|array',
            'atlet_ids.*'               => 'exists:atlets,id',
            'pelatih_ids'               => 'nullable|array',
            'pelatih_ids.*'             => 'exists:pelatihs,id',
            'tenaga_pendukung_ids'      => 'nullable|array',
            'tenaga_pendukung_ids.*'    => 'exists:tenaga_pendukungs,id',
            'ref_status_pemeriksaan_id' => 'nullable|exists:ref_status_pemeriksaan,id',
            'catatan_umum'              => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $now    = now();
            $userId = auth()->id();

            $this->bulkInsertPeserta($pemeriksaanId, $request->atlet_ids, 'App\\Models\\Atlet', $request, $now, $userId);
            $this->bulkInsertPeserta($pemeriksaanId, $request->pelatih_ids, 'App\\Models\\Pelatih', $request, $now, $userId);
            $this->bulkInsertPeserta($pemeriksaanId, $request->tenaga_pendukung_ids, 'App\\Models\\TenagaPendukung', $request, $now, $userId);

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Peserta berhasil ditambahkan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal menambahkan peserta: '.$e->getMessage()], 500);
        }
    }

    /**
     * Update status/catatan peserta
     */
    public function update(Request $request, int $pemeriksaanId, int $id): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $request->validate([
            'ref_status_pemeriksaan_id' => 'nullable|exists:ref_status_pemeriksaan,id',
            'catatan_umum'              => 'nullable|string',
        ]);

        $item = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)->where('id', $id)->first();
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Peserta pemeriksaan tidak ditemukan'], 404);
        }

        $item->ref_status_pemeriksaan_id = $request->ref_status_pemeriksaan_id;
        $item->catatan_umum              = $request->catatan_umum;
        $item->save();

        return response()->json(['status' => 'success', 'message' => 'Peserta berhasil diperbarui']);
    }

    /**
     * Delete peserta pemeriksaan
     */
    public function destroy(int $pemeriksaanId, int $id): JsonResponse
    {
        $item = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)->where('id', $id)->first();
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Peserta pemeriksaan tidak ditemukan'], 404);
        }

        $item->delete();
        return response()->json(['status' => 'success', 'message' => 'Peserta berhasil dihapus']);
    }

    /**
     * Kandidat Atlet untuk form (mobile)
     */
    public function availableAtlet(Request $request, int $pemeriksaanId): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::with(['caborKategori'])->find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $search = $request->get('search', '');

        $query = CaborKategoriAtlet::with(['atlet'])
            ->where('cabor_kategori_id', $pemeriksaan->cabor_kategori_id);

        if ($search) {
            $query->whereHas('atlet', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $data = $query->get()->map(function ($item) {
            $atlet = $item->atlet;
            return [
                'id'            => $atlet->id,
                'nama'          => $atlet->nama,
                'foto'          => $atlet->foto,
                'posisi'        => $item->posisi_atlet ?? '-', // Langsung dari attribute
                'jenis_kelamin' => $atlet->jenis_kelamin,
                'usia'          => $this->calculateAge($atlet->tanggal_lahir),
            ];
        });

        return response()->json(['status' => 'success', 'message' => 'Data atlet tersedia berhasil diambil', 'data' => $data]);
    }

    /**
     * Kandidat Pelatih untuk form (mobile)
     */
    public function availablePelatih(Request $request, int $pemeriksaanId): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::with(['caborKategori'])->find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $search = $request->get('search', '');

        $query = CaborKategoriPelatih::with(['pelatih'])
            ->where('cabor_kategori_id', $pemeriksaan->cabor_kategori_id);

        if ($search) {
            $query->whereHas('pelatih', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        $data = $query->get()->map(function ($item) {
            $pelatih = $item->pelatih;
            return [
                'id'            => $pelatih->id,
                'nama'          => $pelatih->nama,
                'foto'          => $pelatih->foto,
                'jenis_pelatih' => $item->jenis_pelatih ?? '-', // Langsung dari attribute
                'jenis_kelamin' => $pelatih->jenis_kelamin,
                'usia'          => $this->calculateAge($pelatih->tanggal_lahir),
            ];
        });

        return response()->json(['status' => 'success', 'message' => 'Data pelatih tersedia berhasil diambil', 'data' => $data]);
    }

    /**
     * Kandidat Tenaga Pendukung untuk form (mobile)
     */
    public function availableTenagaPendukung(Request $request, int $pemeriksaanId): JsonResponse
    {
        $pemeriksaan = Pemeriksaan::with(['caborKategori'])->find($pemeriksaanId);
        if (!$pemeriksaan) {
            return response()->json(['status' => 'error', 'message' => 'Pemeriksaan tidak ditemukan'], 404);
        }

        $search = $request->get('search', '');

        $query = CaborKategoriTenagaPendukung::with(['tenagaPendukung'])
            ->where('cabor_kategori_id', $pemeriksaan->cabor_kategori_id);

        if ($search) {
            $query->whereHas('tenagaPendukung', function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Pastikan hanya data dengan relasi tenagaPendukung yang valid
        $query->whereHas('tenagaPendukung');

        $data = $query->get()
            ->filter(function ($item) {
                return !is_null($item->tenagaPendukung);
            })
            ->map(function ($item) {
                $tenaga = $item->tenagaPendukung;
                return [
                    'id'                     => $tenaga?->id,
                    'nama'                   => $tenaga?->nama,
                    'foto'                   => $tenaga?->foto,
                    'jenis_tenaga_pendukung' => $item->jenis_tenaga_pendukung ?? '-', // Langsung dari attribute
                    'jenis_kelamin'          => $tenaga?->jenis_kelamin,
                    'usia'                   => $this->calculateAge($tenaga?->tanggal_lahir),
                ];
            })
            ->values();

        return response()->json(['status' => 'success', 'message' => 'Data tenaga pendukung tersedia berhasil diambil', 'data' => $data]);
    }

    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }
        try {
            $birth = new \DateTime($tanggalLahir);
            $today = new \DateTime();
            return (int) $birth->diff($today)->y;
        } catch (\Exception $e) {
            return '-';
        }
    }

    private function bulkInsertPeserta(int $pemeriksaanId, ?array $ids, string $typeClass, Request $request, $now, $userId): void
    {
        if (empty($ids)) {
            return;
        }

        $existing = PemeriksaanPeserta::where('pemeriksaan_id', $pemeriksaanId)
            ->where('peserta_type', $typeClass)
            ->pluck('peserta_id')
            ->toArray();

        $newIds = array_diff($ids, $existing);
        if (empty($newIds)) {
            return;
        }

        $rows = [];
        foreach ($newIds as $pesertaId) {
            $rows[] = [
                'pemeriksaan_id'            => $pemeriksaanId,
                'peserta_id'                => $pesertaId,
                'peserta_type'              => $typeClass,
                'ref_status_pemeriksaan_id' => $request->ref_status_pemeriksaan_id,
                'catatan_umum'              => $request->catatan_umum,
                'created_at'                => $now,
                'updated_at'                => $now,
                'created_by'                => $userId,
                'updated_by'                => $userId,
            ];
        }

        PemeriksaanPeserta::insert($rows);
    }
}
