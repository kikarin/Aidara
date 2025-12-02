<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaborKategori;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use App\Models\MstTingkat;
use App\Models\MstJuara;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TurnamenFormController extends Controller
{
    /**
     * Get list of cabor kategori for form dropdown
     */
    public function getCaborKategoriList(): JsonResponse
    {
        try {
            $caborKategori = CaborKategori::with(['cabor'])
                ->orderBy('cabor_id')
                ->orderBy('nama')
                ->get();

            $data = $caborKategori->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'nama'  => $item->nama,
                    'cabor' => [
                        'id'   => $item->cabor->id,
                        'nama' => $item->cabor->nama,
                    ],
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data cabor kategori berhasil diambil',
                'data'    => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cabor kategori list: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data cabor kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of atlet by cabor kategori
     */
    public function getAtletByCaborKategori(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = Atlet::whereHas('caborKategoriAtlet', function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId)
                  ->where('is_active', 1);
            })
            ->where('is_active', 1);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $atlet = $query->with(['caborKategoriAtlet' => function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId);
            }])
            ->orderBy('nama')
            ->get()
            ->map(function ($item) {
                $posisi = $item->caborKategoriAtlet->first()?->posisi_atlet ?? '-'; // Langsung dari attribute

                return [
                    'id'             => $item->id,
                    'nama'           => $item->nama,
                    'foto'           => $this->getFullPhotoUrl($item->foto),
                    'posisi'         => $posisi,
                    'jenis_kelamin'  => $this->mapJenisKelamin($item->jenis_kelamin),
                    'usia'           => $this->calculateAge($item->tanggal_lahir),
                    'lama_bergabung' => $this->getLamaBergabung($item->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data atlet berhasil diambil',
                'data'    => $atlet,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting atlet by cabor kategori: ' . $e->getMessage(), [
                'cabor_kategori_id' => $caborKategoriId,
                'trace'             => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data atlet: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of pelatih by cabor kategori
     */
    public function getPelatihByCaborKategori(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = Pelatih::whereHas('caborKategoriPelatih', function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId)
                  ->where('is_active', 1);
            })
            ->where('is_active', 1);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $pelatih = $query->with(['caborKategoriPelatih' => function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId);
            }])
            ->orderBy('nama')
            ->get()
            ->map(function ($item) {
                $jenisPelatih = $item->caborKategoriPelatih->first()?->jenis_pelatih ?? '-'; // Langsung dari attribute

                return [
                    'id'             => $item->id,
                    'nama'           => $item->nama,
                    'foto'           => $this->getFullPhotoUrl($item->foto),
                    'jenis_pelatih'  => $jenisPelatih,
                    'jenis_kelamin'  => $this->mapJenisKelamin($item->jenis_kelamin),
                    'usia'           => $this->calculateAge($item->tanggal_lahir),
                    'lama_bergabung' => $this->getLamaBergabung($item->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data pelatih berhasil diambil',
                'data'    => $pelatih,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting pelatih by cabor kategori: ' . $e->getMessage(), [
                'cabor_kategori_id' => $caborKategoriId,
                'trace'             => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data pelatih: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of tenaga pendukung by cabor kategori
     */
    public function getTenagaPendukungByCaborKategori(Request $request, int $caborKategoriId): JsonResponse
    {
        try {
            $search = $request->get('search', '');

            $query = TenagaPendukung::whereHas('caborKategoriTenagaPendukung', function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId)
                  ->where('is_active', 1);
            })
            ->where('is_active', 1);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%");
                });
            }

            $tenagaPendukung = $query->with(['caborKategoriTenagaPendukung' => function ($q) use ($caborKategoriId) {
                $q->where('cabor_kategori_id', $caborKategoriId);
            }])
            ->orderBy('nama')
            ->get()
            ->map(function ($item) {
                $jenisTenagaPendukung = $item->caborKategoriTenagaPendukung->first()?->jenis_tenaga_pendukung ?? '-'; // Langsung dari attribute

                return [
                    'id'                     => $item->id,
                    'nama'                   => $item->nama,
                    'foto'                   => $this->getFullPhotoUrl($item->foto),
                    'jenis_tenaga_pendukung' => $jenisTenagaPendukung,
                    'jenis_kelamin'          => $this->mapJenisKelamin($item->jenis_kelamin),
                    'usia'                   => $this->calculateAge($item->tanggal_lahir),
                    'lama_bergabung'         => $this->getLamaBergabung($item->tanggal_bergabung),
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data tenaga pendukung berhasil diambil',
                'data'    => $tenagaPendukung,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting tenaga pendukung by cabor kategori: ' . $e->getMessage(), [
                'cabor_kategori_id' => $caborKategoriId,
                'trace'             => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data tenaga pendukung: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of tingkat for dropdown
     */
    public function getTingkatList(): JsonResponse
    {
        try {
            $tingkat = MstTingkat::orderBy('nama')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'   => $item->id,
                        'nama' => $item->nama,
                    ];
                });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data tingkat berhasil diambil',
                'data'    => $tingkat,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting tingkat list: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data tingkat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of juara for dropdown
     */
    public function getJuaraList(): JsonResponse
    {
        try {
            $juara = MstJuara::orderBy('nama')
                ->get()
                ->map(function ($item) {
                    return [
                        'id'   => $item->id,
                        'nama' => $item->nama,
                    ];
                });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data juara berhasil diambil',
                'data'    => $juara,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting juara list: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data juara: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get full photo URL
     */
    private function getFullPhotoUrl($photoPath): ?string
    {
        if (!$photoPath) {
            return null;
        }

        if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
            return $photoPath;
        }

        return url('storage/' . $photoPath);
    }

    /**
     * Map jenis kelamin
     */
    private function mapJenisKelamin($jenisKelamin): string
    {
        return match ($jenisKelamin) {
            'L'     => 'Laki-laki',
            'P'     => 'Perempuan',
            default => $jenisKelamin,
        };
    }

    /**
     * Calculate age from birth date
     */
    private function calculateAge($tanggalLahir): int|string
    {
        if (!$tanggalLahir) {
            return '-';
        }

        $birthDate = new \DateTime($tanggalLahir);
        $today     = new \DateTime();
        $age       = $today->diff($birthDate)->y;

        return $age;
    }

    /**
     * Get lama bergabung
     */
    private function getLamaBergabung($tanggalBergabung): string
    {
        if (!$tanggalBergabung) {
            return '-';
        }

        $joinDate = new \DateTime($tanggalBergabung);
        $today    = new \DateTime();
        $diff     = $today->diff($joinDate);

        $years  = $diff->y;
        $months = $diff->m;

        if ($years > 0 && $months > 0) {
            return "{$years} tahun {$months} bulan";
        } elseif ($years > 0) {
            return "{$years} tahun";
        } elseif ($months > 0) {
            return "{$months} bulan";
        } else {
            return 'Kurang dari 1 bulan';
        }
    }
}
