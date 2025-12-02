<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResponseResource;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use Illuminate\Http\JsonResponse;

class ParticipantProfileController extends Controller
{
    /**
     * GET /api/rencana-latihan/{rencanaId}/peserta/{jenis}/{pesertaId}/profil
     */
    public function rencanaProfil(int $rencanaId, string $jenis, int $pesertaId): JsonResponse
    {
        return $this->showByJenisAndId($jenis, $pesertaId);
    }

    /**
     * GET /api/pemeriksaan/{pemeriksaanId}/peserta/{jenis}/{pesertaId}/profil
     */
    public function pemeriksaanProfil(int $pemeriksaanId, string $jenis, int $pesertaId): JsonResponse
    {
        return $this->showByJenisAndId($jenis, $pesertaId);
    }

    /**
     * GET /api/turnamen/{turnamenId}/peserta/{jenis}/{pesertaId}/profil
     */
    public function turnamenProfil(int $turnamenId, string $jenis, int $pesertaId): JsonResponse
    {
        return $this->showByJenisAndId($jenis, $pesertaId);
    }

    private function showByJenisAndId(string $jenis, int $id): JsonResponse
    {
        $jenis = strtolower($jenis);
        switch ($jenis) {
            case 'atlet':
                $model = $this->findAtlet($id);
                $data  = $model ? $this->formatAtletProfile($model) : null;
                break;
            case 'pelatih':
                $model = $this->findPelatih($id);
                $data  = $model ? $this->formatPelatihProfile($model) : null;
                break;
            case 'tenaga-pendukung':
            case 'tenaga_pendukung':
                $model = $this->findTenagaPendukung($id);
                $data  = $model ? $this->formatTenagaPendukungProfile($model) : null;
                break;
            default:
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Jenis peserta tidak valid',
                ], 422);
        }

        if (! $data) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Peserta tidak ditemukan',
            ], 404);
        }

        return ApiResponseResource::success($data, 'Data profil peserta berhasil diambil')->response();
    }

    private function findAtlet(int $id): ?Atlet
    {
        return Atlet::with([
            'media', 'kecamatan', 'kelurahan', 'atletOrangTua',
            'sertifikat', 'sertifikat.media',
            'prestasi', 'prestasi.tingkat',
            'dokumen', 'dokumen.jenis_dokumen',
            'kesehatan',
        ])->find($id);
    }

    private function findPelatih(int $id): ?Pelatih
    {
        return Pelatih::with([
            'media', 'kecamatan', 'kelurahan',
            'sertifikat', 'sertifikat.media',
            'prestasi', 'prestasi.tingkat',
            'dokumen', 'dokumen.jenis_dokumen',
            'kesehatan',
        ])->find($id);
    }

    private function findTenagaPendukung(int $id): ?TenagaPendukung
    {
        return TenagaPendukung::with([
            'media', 'kecamatan', 'kelurahan',
            'sertifikat', 'sertifikat.media',
            'prestasi', 'prestasi.tingkat',
            'dokumen', 'dokumen.jenis_dokumen',
            'kesehatan',
        ])->find($id);
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
            $start  = new \DateTime($tanggalBergabung);
            $end    = new \DateTime();
            $diff   = $start->diff($end);
            $years  = (int) $diff->y;
            $months = (int) $diff->m;
            $parts  = [];
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
