<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\MstDesa;
use App\Models\MstJenisDokumen;
use App\Models\MstKategoriAtlet;
use App\Models\MstKategoriPeserta;
use App\Models\MstKategoriPrestasiPelatih;
use App\Models\MstKecamatan;
use App\Models\MstParameter;
use App\Models\MstPosisiAtlet;
use App\Models\MstTingkat;
use App\Models\RefStatusPemeriksaan;
use App\Models\TenagaPendukung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OptionsController extends Controller
{
    /**
     * Get Kecamatan list
     */
    public function getKecamatan(): JsonResponse
    {
        try {
            $data = MstKecamatan::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kecamatan.',
            ], 500);
        }
    }

    /**
     * Get Kelurahan by Kecamatan ID
     */
    public function getKelurahanByKecamatan($kecamatanId): JsonResponse
    {
        try {
            $data = MstDesa::where('id_kecamatan', $kecamatanId)
                ->select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kelurahan.',
            ], 500);
        }
    }

    /**
     * Get Tingkat list (untuk Prestasi)
     */
    public function getTingkat(): JsonResponse
    {
        try {
            $data = MstTingkat::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data tingkat.',
            ], 500);
        }
    }

    /**
     * Get Kategori Prestasi Pelatih list
     */
    public function getKategoriPrestasiPelatih(): JsonResponse
    {
        try {
            $data = MstKategoriPrestasiPelatih::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori prestasi pelatih.',
            ], 500);
        }
    }

    /**
     * Get Kategori Atlet list (untuk Pelatih Prestasi)
     * Note: Menggunakan MstKategoriPeserta karena table mst_kategori_atlet sudah di-rename menjadi mst_kategori_peserta
     */
    public function getKategoriAtlet(): JsonResponse
    {
        try {
            // Gunakan MstKategoriPeserta karena table sudah di-rename
            $data = MstKategoriPeserta::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Kategori Atlet error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori atlet.',
            ], 500);
        }
    }

    /**
     * Get Jenis Dokumen list
     */
    public function getJenisDokumen(): JsonResponse
    {
        try {
            $data = MstJenisDokumen::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data jenis dokumen.',
            ], 500);
        }
    }

    /**
     * Get Posisi Atlet list (untuk Atlet)
     */
    public function getPosisiAtlet(): JsonResponse
    {
        try {
            $data = MstPosisiAtlet::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data posisi atlet.',
            ], 500);
        }
    }

    /**
     * Get Kategori Peserta list
     */
    public function getKategoriPeserta(): JsonResponse
    {
        try {
            $data = MstKategoriPeserta::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori peserta.',
            ], 500);
        }
    }

    /**
     * Get Cabor list
     */
    public function getCabor(Request $request): JsonResponse
    {
        try {
            $query = Cabor::select('id', 'nama', 'kategori_peserta_id')
                ->orderBy('nama');

            // Filter berdasarkan kategori_peserta_id jika ada
            $kategoriPesertaId = $request->get('kategori_peserta_id');
            if ($kategoriPesertaId && $kategoriPesertaId !== 'all') {
                $query->where('kategori_peserta_id', $kategoriPesertaId);
            }

            $data = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Cabor error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data cabor.',
            ], 500);
        }
    }

    /**
     * Get Cabor Kategori by Cabor ID
     */
    public function getCaborKategoriByCabor($caborId): JsonResponse
    {
        try {
            $data = CaborKategori::where('cabor_id', $caborId)
                ->select('id', 'nama', 'cabor_id', 'jenis_kelamin', 'kategori_peserta_id')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Cabor Kategori error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data cabor kategori.',
            ], 500);
        }
    }

    /**
     * Get Tenaga Pendukung list (untuk Pemeriksaan)
     */
    public function getTenagaPendukung(Request $request): JsonResponse
    {
        try {
            $query = TenagaPendukung::withoutGlobalScopes()
                ->whereNull('tenaga_pendukungs.deleted_at') // Exclude soft deleted
                ->select('id', 'nama')
                ->orderBy('nama');

            // Filter by cabor_kategori_id jika ada (opsional)
            $caborKategoriId = $request->get('cabor_kategori_id');
            if ($caborKategoriId && $caborKategoriId !== 'all') {
                $query->whereHas('caborKategoriTenagaPendukung', function ($q) use ($caborKategoriId) {
                    $q->where('cabor_kategori_id', $caborKategoriId)
                        ->whereNull('cabor_kategori_tenaga_pendukung.deleted_at');
                });
            }

            $data = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Tenaga Pendukung error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data tenaga pendukung.',
            ], 500);
        }
    }

    /**
     * Get Parameter Pemeriksaan list (untuk Pemeriksaan)
     * Exclude kategori 'umum' karena kategori umum untuk atlet parameter umum, bukan pemeriksaan
     */
    public function getParameterPemeriksaan(): JsonResponse
    {
        try {
            $data = MstParameter::withoutGlobalScopes()
                ->where('kategori', '!=', 'umum')
                ->select('id', 'nama', 'satuan', 'kategori')
                ->orderBy('nama')
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'nama' => $item->nama,
                        'satuan' => $item->satuan,
                        'kategori' => $item->kategori,
                        'label' => $item->nama . ' (' . $item->satuan . ')',
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Parameter Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data parameter pemeriksaan.',
            ], 500);
        }
    }

    /**
     * Get Ref Status Pemeriksaan list (untuk Pemeriksaan Peserta)
     */
    public function getRefStatusPemeriksaan(): JsonResponse
    {
        try {
            $data = RefStatusPemeriksaan::select('id', 'nama')
                ->orderBy('nama')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Get Ref Status Pemeriksaan error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data status pemeriksaan.',
            ], 500);
        }
    }

    /**
     * Get all options untuk form (untuk memudahkan mobile app)
     */
    public function getAllOptions(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized.',
                ], 401);
            }

            $pesertaType = $user->peserta_type ?? null;

            $options = [
                'kecamatan' => MstKecamatan::select('id', 'nama')->orderBy('nama')->get(),
                'tingkat' => MstTingkat::select('id', 'nama')->orderBy('nama')->get(),
                'jenis_dokumen' => MstJenisDokumen::select('id', 'nama')->orderBy('nama')->get(),
                'kategori_peserta' => MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get(),
                'cabor' => Cabor::select('id', 'nama', 'kategori_peserta_id')->orderBy('nama')->get(),
            ];

            // Options khusus untuk Atlet
            if ($pesertaType === 'atlet') {
                // Gunakan MstKategoriPeserta karena table mst_kategori_atlet sudah di-rename
                $options['kategori_atlet'] = MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get();
                $options['posisi_atlet'] = MstPosisiAtlet::select('id', 'nama')->orderBy('nama')->get();
            }

            // Options khusus untuk Pelatih
            if ($pesertaType === 'pelatih') {
                $options['kategori_prestasi_pelatih'] = MstKategoriPrestasiPelatih::select('id', 'nama')->orderBy('nama')->get();
                // Gunakan MstKategoriPeserta karena table mst_kategori_atlet sudah di-rename
                $options['kategori_atlet'] = MstKategoriPeserta::select('id', 'nama')->orderBy('nama')->get();
            }

            return response()->json([
                'status' => 'success',
                'data' => $options,
            ]);
        } catch (\Exception $e) {
            \Log::error('GetAllOptions error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data options.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

