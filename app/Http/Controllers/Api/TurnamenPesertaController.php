<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\TurnamenRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;

class TurnamenPesertaController extends Controller
{
    protected $repository;

    public function __construct(TurnamenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get peserta turnamen untuk kelola (CRUD)
     */
    public function index(Request $request, int $turnamenId): JsonResponse
    {
        try {
            $turnamen = $this->repository->getDetailWithRelations($turnamenId);

            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            $pesertaData = $this->repository->getPesertaForCrud($turnamenId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta turnamen berhasil diambil',
                'data'    => [
                    'turnamen' => [
                        'id'              => $turnamen->id,
                        'nama'            => $turnamen->nama,
                        'tanggal_mulai'   => $turnamen->tanggal_mulai,
                        'tanggal_selesai' => $turnamen->tanggal_selesai,
                    ],
                    'atlet'             => $pesertaData['atlet']            ?? [],
                    'pelatih'           => $pesertaData['pelatih']          ?? [],
                    'tenaga_pendukung'  => $pesertaData['tenaga_pendukung'] ?? [],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in peserta turnamen CRUD API: ' . $e->getMessage(), [
                'turnamenId' => $turnamenId,
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data peserta turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add peserta to turnamen
     */
    public function store(Request $request, int $turnamenId): JsonResponse
    {
        try {
            $request->validate([
                'peserta_id'     => 'required|integer',
                'jenis_peserta'  => 'required|in:atlet,pelatih,tenaga-pendukung',
            ], [
                'peserta_id.required'     => 'ID peserta wajib diisi.',
                'peserta_id.integer'      => 'ID peserta harus berupa angka.',
                'jenis_peserta.required'  => 'Jenis peserta wajib diisi.',
                'jenis_peserta.in'        => 'Jenis peserta harus berupa: atlet, pelatih, atau tenaga-pendukung.',
            ]);

            $turnamen = $this->repository->find($turnamenId);
            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            $pesertaId    = $request->peserta_id;
            $jenisPeserta = $request->jenis_peserta;

            // Determine peserta type class
            $pesertaTypeMap = [
                'atlet'             => 'App\\Models\\Atlet',
                'pelatih'           => 'App\\Models\\Pelatih',
                'tenaga-pendukung'  => 'App\\Models\\TenagaPendukung',
            ];

            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? null;
            if (!$pesertaType) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Jenis peserta tidak valid',
                ], 422);
            }

            // Validate peserta exists
            $pesertaModel = app($pesertaType);
            $peserta      = $pesertaModel->find($pesertaId);
            if (!$peserta) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan',
                ], 404);
            }

            // Check if peserta already exists in turnamen
            $existingPeserta = DB::table('turnamen_peserta')
                ->where('turnamen_id', $turnamenId)
                ->where('peserta_id', $pesertaId)
                ->where('peserta_type', $pesertaType)
                ->first();

            if ($existingPeserta) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta sudah terdaftar dalam turnamen ini',
                ], 422);
            }

            // Add peserta to turnamen
            DB::table('turnamen_peserta')->insert([
                'turnamen_id'  => $turnamenId,
                'peserta_id'   => $pesertaId,
                'peserta_type' => $pesertaType,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Peserta berhasil ditambahkan ke turnamen',
                'data'    => [
                    'turnamen_id'   => $turnamenId,
                    'peserta_id'    => $pesertaId,
                    'jenis_peserta' => $jenisPeserta,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal menambahkan peserta turnamen: ' . $e->getMessage(), [
                'exception'   => $e,
                'user_id'     => auth()->id(),
                'turnamen_id' => $turnamenId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambahkan peserta turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove peserta from turnamen
     */
    public function destroy(Request $request, int $turnamenId, int $pesertaId): JsonResponse
    {
        try {
            $request->validate([
                'jenis_peserta' => 'required|in:atlet,pelatih,tenaga-pendukung',
            ], [
                'jenis_peserta.required' => 'Jenis peserta wajib diisi.',
                'jenis_peserta.in'       => 'Jenis peserta harus berupa: atlet, pelatih, atau tenaga-pendukung.',
            ]);

            $turnamen = $this->repository->find($turnamenId);
            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            $jenisPeserta = $request->jenis_peserta;

            // Determine peserta type class
            $pesertaTypeMap = [
                'atlet'             => 'App\\Models\\Atlet',
                'pelatih'           => 'App\\Models\\Pelatih',
                'tenaga-pendukung'  => 'App\\Models\\TenagaPendukung',
            ];

            $pesertaType = $pesertaTypeMap[$jenisPeserta] ?? null;
            if (!$pesertaType) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Jenis peserta tidak valid',
                ], 422);
            }

            // Check if peserta exists in turnamen
            $existingPeserta = DB::table('turnamen_peserta')
                ->where('turnamen_id', $turnamenId)
                ->where('peserta_id', $pesertaId)
                ->where('peserta_type', $pesertaType)
                ->first();

            if (!$existingPeserta) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Peserta tidak ditemukan dalam turnamen ini',
                ], 404);
            }

            // Remove peserta from turnamen
            DB::table('turnamen_peserta')
                ->where('turnamen_id', $turnamenId)
                ->where('peserta_id', $pesertaId)
                ->where('peserta_type', $pesertaType)
                ->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Peserta berhasil dihapus dari turnamen',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal menghapus peserta turnamen: ' . $e->getMessage(), [
                'exception'   => $e,
                'user_id'     => auth()->id(),
                'turnamen_id' => $turnamenId,
                'peserta_id'  => $pesertaId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus peserta turnamen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available peserta (not yet in turnamen)
     */
    public function availablePeserta(Request $request, int $turnamenId): JsonResponse
    {
        try {
            $request->validate([
                'jenis_peserta' => 'required|in:atlet,pelatih,tenaga-pendukung',
                'search'        => 'nullable|string|max:255',
            ], [
                'jenis_peserta.required' => 'Jenis peserta wajib diisi.',
                'jenis_peserta.in'       => 'Jenis peserta harus berupa: atlet, pelatih, atau tenaga-pendukung.',
                'search.string'          => 'Pencarian harus berupa teks.',
                'search.max'             => 'Pencarian maksimal 255 karakter.',
            ]);

            $turnamen = $this->repository->getDetailWithRelations($turnamenId);
            if (!$turnamen) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Turnamen tidak ditemukan',
                ], 404);
            }

            $jenisPeserta = $request->jenis_peserta;
            $search       = $request->search ?? '';

            $availablePeserta = $this->getAvailablePesertaByType($turnamenId, $turnamen->cabor_kategori_id, $jenisPeserta, $search);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data peserta tersedia berhasil diambil',
                'data'    => $availablePeserta,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Gagal mengambil peserta tersedia: ' . $e->getMessage(), [
                'exception'   => $e,
                'user_id'     => auth()->id(),
                'turnamen_id' => $turnamenId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil peserta tersedia: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available peserta by type
     */
    private function getAvailablePesertaByType($turnamenId, $caborKategoriId, $jenisPeserta, $search = '')
    {
        // Get existing peserta IDs in turnamen
        $pesertaTypeMap = [
            'atlet'             => 'App\\Models\\Atlet',
            'pelatih'           => 'App\\Models\\Pelatih',
            'tenaga-pendukung'  => 'App\\Models\\TenagaPendukung',
        ];

        $pesertaType        = $pesertaTypeMap[$jenisPeserta];
        $existingPesertaIds = DB::table('turnamen_peserta')
            ->where('turnamen_id', $turnamenId)
            ->where('peserta_type', $pesertaType)
            ->pluck('peserta_id')
            ->toArray();

        switch ($jenisPeserta) {
            case 'atlet':
                return $this->getAvailableAtlet($caborKategoriId, $existingPesertaIds, $search);
            case 'pelatih':
                return $this->getAvailablePelatih($caborKategoriId, $existingPesertaIds, $search);
            case 'tenaga-pendukung':
                return $this->getAvailableTenagaPendukung($caborKategoriId, $existingPesertaIds, $search);
            default:
                return [];
        }
    }

    /**
     * Get available atlet
     */
    private function getAvailableAtlet($caborKategoriId, $excludeIds, $search)
    {
        $query = DB::table('atlets')
            ->join('cabor_kategori_atlet', 'atlets.id', '=', 'cabor_kategori_atlet.atlet_id')
            ->leftJoin('mst_posisi_atlet', 'cabor_kategori_atlet.posisi_atlet_id', '=', 'mst_posisi_atlet.id')
            ->where('cabor_kategori_atlet.cabor_kategori_id', $caborKategoriId)
            ->where('cabor_kategori_atlet.is_active', 1)
            ->where('atlets.is_active', 1)
            ->whereNull('atlets.deleted_at')
            ->select(
                'atlets.id',
                'atlets.nama',
                'atlets.foto',
                'atlets.jenis_kelamin',
                'atlets.tanggal_lahir',
                'mst_posisi_atlet.nama as posisi'
            );

        if (!empty($excludeIds)) {
            $query->whereNotIn('atlets.id', $excludeIds);
        }

        if (!empty($search)) {
            $query->where('atlets.nama', 'like', '%' . $search . '%');
        }

        $atlets = $query->get();

        return $atlets->map(function ($atlet) {
            // Foto dari kolom, jika kosong coba ambil dari media library
            $fotoUrl = null;
            if (!empty($atlet->foto)) {
                $fotoUrl = url('storage/' . ltrim($atlet->foto, '/'));
            } else {
                $model = Atlet::find($atlet->id);
                if ($model && method_exists($model, 'getFirstMedia')) {
                    $media = $model->getFirstMedia('images');
                    if ($media) {
                        $fotoUrl = method_exists($media, 'getUrl') ? $media->getUrl('webp') : $media->getUrl();
                    }
                }
            }

            return [
                'id'           => $atlet->id,
                'nama'         => $atlet->nama,
                'foto'         => $fotoUrl,
                'jenisKelamin' => $this->mapJenisKelamin($atlet->jenis_kelamin),
                'usia'         => $this->calculateAge($atlet->tanggal_lahir),
                'posisi'       => $atlet->posisi ?? '-',
            ];
        })->toArray();
    }

    /**
     * Get available pelatih
     */
    private function getAvailablePelatih($caborKategoriId, $excludeIds, $search)
    {
        $query = DB::table('pelatihs')
            ->join('cabor_kategori_pelatih', 'pelatihs.id', '=', 'cabor_kategori_pelatih.pelatih_id')
            ->leftJoin('mst_jenis_pelatih', 'cabor_kategori_pelatih.jenis_pelatih_id', '=', 'mst_jenis_pelatih.id')
            ->where('cabor_kategori_pelatih.cabor_kategori_id', $caborKategoriId)
            ->where('cabor_kategori_pelatih.is_active', 1)
            ->where('pelatihs.is_active', 1)
            ->whereNull('pelatihs.deleted_at')
            ->select(
                'pelatihs.id',
                'pelatihs.nama',
                'pelatihs.foto',
                'pelatihs.jenis_kelamin',
                'pelatihs.tanggal_lahir',
                'mst_jenis_pelatih.nama as jenis_pelatih'
            );

        if (!empty($excludeIds)) {
            $query->whereNotIn('pelatihs.id', $excludeIds);
        }

        if (!empty($search)) {
            $query->where('pelatihs.nama', 'like', '%' . $search . '%');
        }

        $pelatihs = $query->get();

        return $pelatihs->map(function ($pelatih) {
            $fotoUrl = null;
            if (!empty($pelatih->foto)) {
                $fotoUrl = url('storage/' . ltrim($pelatih->foto, '/'));
            } else {
                $model = Pelatih::find($pelatih->id);
                if ($model && method_exists($model, 'getFirstMedia')) {
                    $media = $model->getFirstMedia('images');
                    if ($media) {
                        $fotoUrl = method_exists($media, 'getUrl') ? $media->getUrl('webp') : $media->getUrl();
                    }
                }
            }

            return [
                'id'           => $pelatih->id,
                'nama'         => $pelatih->nama,
                'foto'         => $fotoUrl,
                'jenisKelamin' => $this->mapJenisKelamin($pelatih->jenis_kelamin),
                'usia'         => $this->calculateAge($pelatih->tanggal_lahir),
                'jenisPelatih' => $pelatih->jenis_pelatih ?? '-',
            ];
        })->toArray();
    }

    /**
     * Get available tenaga pendukung
     */
    private function getAvailableTenagaPendukung($caborKategoriId, $excludeIds, $search)
    {
        $query = DB::table('tenaga_pendukungs')
            ->join('cabor_kategori_tenaga_pendukung', 'tenaga_pendukungs.id', '=', 'cabor_kategori_tenaga_pendukung.tenaga_pendukung_id')
            ->leftJoin('mst_jenis_tenaga_pendukung', 'cabor_kategori_tenaga_pendukung.jenis_tenaga_pendukung_id', '=', 'mst_jenis_tenaga_pendukung.id')
            ->where('cabor_kategori_tenaga_pendukung.cabor_kategori_id', $caborKategoriId)
            ->where('cabor_kategori_tenaga_pendukung.is_active', 1)
            ->where('tenaga_pendukungs.is_active', 1)
            ->whereNull('tenaga_pendukungs.deleted_at')
            ->select(
                'tenaga_pendukungs.id',
                'tenaga_pendukungs.nama',
                'tenaga_pendukungs.foto',
                'tenaga_pendukungs.jenis_kelamin',
                'tenaga_pendukungs.tanggal_lahir',
                'mst_jenis_tenaga_pendukung.nama as jenis_tenaga_pendukung'
            );

        if (!empty($excludeIds)) {
            $query->whereNotIn('tenaga_pendukungs.id', $excludeIds);
        }

        if (!empty($search)) {
            $query->where('tenaga_pendukungs.nama', 'like', '%' . $search . '%');
        }

        $tenagaPendukungs = $query->get();

        return $tenagaPendukungs->map(function ($tenagaPendukung) {
            $fotoUrl = null;
            if (!empty($tenagaPendukung->foto)) {
                $fotoUrl = url('storage/' . ltrim($tenagaPendukung->foto, '/'));
            } else {
                $model = TenagaPendukung::find($tenagaPendukung->id);
                if ($model && method_exists($model, 'getFirstMedia')) {
                    $media = $model->getFirstMedia('images');
                    if ($media) {
                        $fotoUrl = method_exists($media, 'getUrl') ? $media->getUrl('webp') : $media->getUrl();
                    }
                }
            }

            return [
                'id'                     => $tenagaPendukung->id,
                'nama'                   => $tenagaPendukung->nama,
                'foto'                   => $fotoUrl,
                'jenisKelamin'           => $this->mapJenisKelamin($tenagaPendukung->jenis_kelamin),
                'usia'                   => $this->calculateAge($tenagaPendukung->tanggal_lahir),
                'jenisTenagaPendukung'   => $tenagaPendukung->jenis_tenaga_pendukung ?? '-',
            ];
        })->toArray();
    }

    /**
     * Map jenis kelamin
     */
    private function mapJenisKelamin($jenisKelamin)
    {
        return $jenisKelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    /**
     * Calculate age from birth date
     */
    private function calculateAge($tanggalLahir)
    {
        if (!$tanggalLahir) {
            return '-';
        }

        try {
            $birthDate = new \DateTime($tanggalLahir);
            $today     = new \DateTime();
            $age       = $today->diff($birthDate)->y;
            return $age;
        } catch (\Exception $e) {
            return '-';
        }
    }
}
