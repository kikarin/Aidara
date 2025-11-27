<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * GET /api/profil/me
     * Kembalikan profil peserta berdasarkan user yang login (atlet/pelatih/tenaga pendukung)
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        // Urutan deteksi berdasarkan role aktif, lalu fallback cari relasi manapun
        $profile = null;
        $jenis   = null;

        if ($user) {
            if ($user->current_role_id === 35) {
                $profile = $this->findAtletByUserId($user->id);
                $jenis   = 'atlet';
            } elseif ($user->current_role_id === 36) {
                $profile = $this->findPelatihByUserId($user->id);
                $jenis   = 'pelatih';
            } elseif ($user->current_role_id === 37) {
                $profile = $this->findTenagaPendukungByUserId($user->id);
                $jenis   = 'tenaga-pendukung';
            }

            if (! $profile) {
                // Fallback: cek setiap entitas
                if (! $profile) {
                    $profile = $this->findAtletByUserId($user->id);
                    $jenis   = $profile ? 'atlet' : $jenis;
                }
                if (! $profile) {
                    $profile = $this->findPelatihByUserId($user->id);
                    $jenis   = $profile ? 'pelatih' : $jenis;
                }
                if (! $profile) {
                    $profile = $this->findTenagaPendukungByUserId($user->id);
                    $jenis   = $profile ? 'tenaga-pendukung' : $jenis;
                }
            }
        }

        if (! $profile || ! $jenis) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profil peserta tidak ditemukan untuk akun ini',
            ], 404);
        }

        $data = match ($jenis) {
            'atlet'            => $this->formatAtletProfile($profile),
            'pelatih'          => $this->formatPelatihProfile($profile),
            'tenaga-pendukung' => $this->formatTenagaPendukungProfile($profile),
        };

        $data['jenis'] = $jenis;

        return ApiResponseResource::success($data, 'Data profil berhasil diambil')->response();
    }

    /**
     * GET /api/profil/atlet
     */
    public function myAtlet(Request $request): JsonResponse
    {
        $user  = $request->user();
        $atlet = $user ? $this->findAtletByUserId($user->id) : null;

        if (! $atlet) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profil atlet tidak ditemukan untuk akun ini',
            ], 404);
        }

        return ApiResponseResource::success(
            $this->formatAtletProfile($atlet),
            'Data profil atlet berhasil diambil'
        )->response();
    }

    /**
     * GET /api/profil/pelatih
     */
    public function myPelatih(Request $request): JsonResponse
    {
        $user    = $request->user();
        $pelatih = $user ? $this->findPelatihByUserId($user->id) : null;

        if (! $pelatih) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profil pelatih tidak ditemukan untuk akun ini',
            ], 404);
        }

        return ApiResponseResource::success(
            $this->formatPelatihProfile($pelatih),
            'Data profil pelatih berhasil diambil'
        )->response();
    }

    /**
     * GET /api/profil/tenaga-pendukung
     */
    public function myTenagaPendukung(Request $request): JsonResponse
    {
        $user            = $request->user();
        $tenagaPendukung = $user ? $this->findTenagaPendukungByUserId($user->id) : null;

        if (! $tenagaPendukung) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Profil tenaga pendukung tidak ditemukan untuk akun ini',
            ], 404);
        }

        return ApiResponseResource::success(
            $this->formatTenagaPendukungProfile($tenagaPendukung),
            'Data profil tenaga pendukung berhasil diambil'
        )->response();
    }

    private function findAtletByUserId(int $userId): ?Atlet
    {
        return Atlet::with([
            'media',
            'kecamatan',
            'kelurahan',
            'atletOrangTua',
            'sertifikat',
            'sertifikat.media',
            'prestasi',
            'prestasi.tingkat',
            'dokumen',
            'dokumen.jenis_dokumen',
            'kesehatan',
        ])->where('users_id', $userId)->first();
    }

    private function findPelatihByUserId(int $userId): ?Pelatih
    {
        return Pelatih::with([
            'media',
            'kecamatan',
            'kelurahan',
            'sertifikat',
            'sertifikat.media',
            'prestasi',
            'prestasi.tingkat',
            'dokumen',
            'dokumen.jenis_dokumen',
            'kesehatan',
        ])->where('users_id', $userId)->first();
    }

    private function findTenagaPendukungByUserId(int $userId): ?TenagaPendukung
    {
        return TenagaPendukung::with([
            'media',
            'kecamatan',
            'kelurahan',
            'sertifikat',
            'sertifikat.media',
            'prestasi',
            'prestasi.tingkat',
            'dokumen',
            'dokumen.jenis_dokumen',
            'kesehatan',
        ])->where('users_id', $userId)->first();
    }

    private function formatAtletProfile(Atlet $atlet): array
    {
        $orangTua = $atlet->atletOrangTua;

        return [
            'nik'              => $atlet->nik,
            'nama'             => $atlet->nama,
            'jenisKelamin'     => $this->mapJenisKelamin($atlet->jenis_kelamin),
            'tempatLahir'      => $atlet->tempat_lahir,
            'tanggalLahir'     => $this->formatDate($atlet->tanggal_lahir),
            'tanggalBergabung' => $this->formatDate($atlet->tanggal_bergabung),
            'lamaBergabung'    => $this->getLamaBergabung($atlet->tanggal_bergabung),
            'alamat'           => $atlet->alamat,
            'kecamatan'        => $atlet->kecamatan->nama ?? null,
            'kelurahan'        => $atlet->kelurahan->nama ?? null,
            'noHP'             => $atlet->no_hp,
            'email'            => $atlet->email,
            'status'           => $atlet->is_active ? 'Aktif' : 'Nonaktif',
            'foto'             => $atlet->foto,

            'ibu' => [
                'nama'         => $orangTua->nama_ibu_kandung ?? null,
                'tempatLahir'  => $orangTua->tempat_lahir_ibu ?? null,
                'tanggalLahir' => $this->formatDate($orangTua->tanggal_lahir_ibu ?? null),
                'noHP'         => $orangTua->no_hp_ibu     ?? null,
                'pekerjaan'    => $orangTua->pekerjaan_ibu ?? null,
                'alamat'       => $orangTua->alamat_ibu    ?? null,
            ],

            'ayah' => [
                'nama'         => $orangTua->nama_ayah_kandung ?? null,
                'tempatLahir'  => $orangTua->tempat_lahir_ayah ?? null,
                'tanggalLahir' => $this->formatDate($orangTua->tanggal_lahir_ayah ?? null),
                'noHP'         => $orangTua->no_hp_ayah     ?? null,
                'pekerjaan'    => $orangTua->pekerjaan_ayah ?? null,
                'alamat'       => $orangTua->alamat_ayah    ?? null,
            ],

            'wali' => [
                'nama'         => $orangTua->nama_wali         ?? null,
                'tempatLahir'  => $orangTua->tempat_lahir_wali ?? null,
                'tanggalLahir' => $this->formatDate($orangTua->tanggal_lahir_wali ?? null),
                'noHP'         => $orangTua->no_hp_wali     ?? null,
                'pekerjaan'    => $orangTua->pekerjaan_wali ?? null,
                'alamat'       => $orangTua->alamat_wali    ?? null,
            ],

            'sertifikat' => $atlet->sertifikat->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'nama'          => $item->nama_sertifikat,
                    'penyelenggara' => $item->penyelenggara,
                    'tanggalTerbit' => $this->formatDate($item->tanggal_terbit),
                    'file'          => $item->file_url,
                ];
            })->values()->toArray(),

            'prestasi' => $atlet->prestasi->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'namaEvent'  => $item->nama_event,
                    'tingkat'    => $item->tingkat->nama ?? null,
                    'tanggal'    => $this->formatDate($item->tanggal),
                    'peringkat'  => $item->peringkat,
                    'keterangan' => $item->keterangan,
                ];
            })->values()->toArray(),

            'dokumen' => $atlet->dokumen->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'jenis' => $item->jenis_dokumen->nama ?? null,
                    'nomor' => $item->nomor,
                    'file'  => $item->file_url,
                ];
            })->values()->toArray(),

            'kesehatan' => [
                'tinggiBadan'     => optional($atlet->kesehatan)->tinggi_badan,
                'beratBadan'      => optional($atlet->kesehatan)->berat_badan,
                'penglihatan'     => optional($atlet->kesehatan)->penglihatan,
                'pendengaran'     => optional($atlet->kesehatan)->pendengaran,
                'riwayatPenyakit' => optional($atlet->kesehatan)->riwayat_penyakit,
                'alergi'          => optional($atlet->kesehatan)->alergi,
            ],
        ];
    }

    private function formatPelatihProfile(Pelatih $pelatih): array
    {
        return [
            'nik'              => $pelatih->nik,
            'nama'             => $pelatih->nama,
            'jenisKelamin'     => $this->mapJenisKelamin($pelatih->jenis_kelamin),
            'tempatLahir'      => $pelatih->tempat_lahir,
            'tanggalLahir'     => $this->formatDate($pelatih->tanggal_lahir),
            'tanggalBergabung' => $this->formatDate($pelatih->tanggal_bergabung),
            'lamaBergabung'    => $this->getLamaBergabung($pelatih->tanggal_bergabung),
            'alamat'           => $pelatih->alamat,
            'kecamatan'        => $pelatih->kecamatan->nama ?? null,
            'kelurahan'        => $pelatih->kelurahan->nama ?? null,
            'noHP'             => $pelatih->no_hp,
            'email'            => $pelatih->email,
            'status'           => $pelatih->is_active ? 'Aktif' : 'Nonaktif',
            'foto'             => $pelatih->foto,

            'sertifikat' => $pelatih->sertifikat->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'nama'          => $item->nama_sertifikat,
                    'penyelenggara' => $item->penyelenggara,
                    'tanggalTerbit' => $this->formatDate($item->tanggal_terbit),
                    'file'          => $item->file_url,
                ];
            })->values()->toArray(),

            'prestasi' => $pelatih->prestasi->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'namaEvent'  => $item->nama_event,
                    'tingkat'    => $item->tingkat->nama ?? null,
                    'tanggal'    => $this->formatDate($item->tanggal),
                    'peringkat'  => $item->peringkat,
                    'keterangan' => $item->keterangan,
                ];
            })->values()->toArray(),

            'dokumen' => $pelatih->dokumen->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'jenis' => $item->jenis_dokumen->nama ?? null,
                    'nomor' => $item->nomor,
                    'file'  => $item->file_url,
                ];
            })->values()->toArray(),

            'kesehatan' => [
                'tinggiBadan'     => optional($pelatih->kesehatan)->tinggi_badan,
                'beratBadan'      => optional($pelatih->kesehatan)->berat_badan,
                'penglihatan'     => optional($pelatih->kesehatan)->penglihatan,
                'pendengaran'     => optional($pelatih->kesehatan)->pendengaran,
                'riwayatPenyakit' => optional($pelatih->kesehatan)->riwayat_penyakit,
                'alergi'          => optional($pelatih->kesehatan)->alergi,
            ],
        ];
    }

    private function formatTenagaPendukungProfile(TenagaPendukung $model): array
    {
        return [
            'nik'              => $model->nik,
            'nama'             => $model->nama,
            'jenisKelamin'     => $this->mapJenisKelamin($model->jenis_kelamin),
            'tempatLahir'      => $model->tempat_lahir,
            'tanggalLahir'     => $this->formatDate($model->tanggal_lahir),
            'tanggalBergabung' => $this->formatDate($model->tanggal_bergabung),
            'lamaBergabung'    => $this->getLamaBergabung($model->tanggal_bergabung),
            'alamat'           => $model->alamat,
            'kecamatan'        => $model->kecamatan->nama ?? null,
            'kelurahan'        => $model->kelurahan->nama ?? null,
            'noHP'             => $model->no_hp,
            'email'            => $model->email,
            'status'           => $model->is_active ? 'Aktif' : 'Nonaktif',
            'foto'             => $model->foto,

            'sertifikat' => $model->sertifikat->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'nama'          => $item->nama_sertifikat,
                    'penyelenggara' => $item->penyelenggara,
                    'tanggalTerbit' => $this->formatDate($item->tanggal_terbit),
                    'file'          => $item->file_url,
                ];
            })->values()->toArray(),

            'prestasi' => $model->prestasi->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'namaEvent'  => $item->nama_event,
                    'tingkat'    => $item->tingkat->nama ?? null,
                    'tanggal'    => $this->formatDate($item->tanggal),
                    'peringkat'  => $item->peringkat,
                    'keterangan' => $item->keterangan,
                ];
            })->values()->toArray(),

            'dokumen' => $model->dokumen->map(function ($item) {
                return [
                    'id'    => $item->id,
                    'jenis' => $item->jenis_dokumen->nama ?? null,
                    'nomor' => $item->nomor,
                    'file'  => $item->file_url,
                ];
            })->values()->toArray(),

            'kesehatan' => [
                'tinggiBadan'     => optional($model->kesehatan)->tinggi_badan,
                'beratBadan'      => optional($model->kesehatan)->berat_badan,
                'penglihatan'     => optional($model->kesehatan)->penglihatan,
                'pendengaran'     => optional($model->kesehatan)->pendengaran,
                'riwayatPenyakit' => optional($model->kesehatan)->riwayat_penyakit,
                'alergi'          => optional($model->kesehatan)->alergi,
            ],
        ];
    }

    private function mapJenisKelamin($jenis): ?string
    {
        return match ($jenis) {
            'L'     => 'Laki-laki',
            'P'     => 'Perempuan',
            default => null,
        };
    }

    private function formatDate($date): ?string
    {
        if (! $date) {
            return null;
        }
        try {
            return date('j/n/Y', strtotime($date));
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getLamaBergabung($tanggalBergabung): ?string
    {
        if (! $tanggalBergabung) {
            return null;
        }

        try {
            $start = new \DateTime($tanggalBergabung);
            $end   = new \DateTime();
            $diff  = $start->diff($end);

            $years  = (int) $diff->y;
            $months = (int) $diff->m;

            $parts = [];
            if ($years > 0) {
                $parts[] = $years.' tahun';
            }
            if ($months > 0) {
                $parts[] = $months.' bulan';
            }

            return empty($parts) ? '0 bulan' : implode(' ', $parts);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
