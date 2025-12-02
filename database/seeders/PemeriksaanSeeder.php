<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanPeserta;
use App\Models\PemeriksaanPesertaParameter;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use Illuminate\Database\Seeder;

class PemeriksaanSeeder extends Seeder
{
    public function run(): void
    {
        $cabor    = Cabor::first();
        $kategori = CaborKategori::first();
        $tenaga   = TenagaPendukung::first();
        if (! $cabor || ! $kategori || ! $tenaga) {
            return;
        }
        $data = [
            [
                'cabor_id'            => $cabor->id,
                'cabor_kategori_id'   => $kategori->id,
                'tenaga_pendukung_id' => $tenaga->id,
                'nama_pemeriksaan'    => 'Pemeriksaan Fisik Awal',
                'tanggal_pemeriksaan' => '2025-08-01',
                'status'              => 'belum',
            ],
            [
                'cabor_id'            => $cabor->id,
                'cabor_kategori_id'   => $kategori->id,
                'tenaga_pendukung_id' => $tenaga->id,
                'nama_pemeriksaan'    => 'Pemeriksaan Lanjutan',
                'tanggal_pemeriksaan' => '2025-09-01',
                'status'              => 'sebagian',
            ],
        ];
        foreach ($data as $item) {
            Pemeriksaan::create($item);
        }

        // Tambahkan peserta dan parameter dengan nilai & trend
        $pemeriksaan = Pemeriksaan::first();
        if (! $pemeriksaan) {
            return;
        }

        $atletIds   = Atlet::limit(3)->pluck('id');
        $pelatihIds = \App\Models\Pelatih::limit(2)->pluck('id');
        $tpIds      = TenagaPendukung::limit(1)->pluck('id');

        $trendVals   = ['up', 'down', 'flat'];
        $mstParamIds = \App\Models\MstParameter::pluck('id');
        // Pastikan pemeriksaan_parameter tersedia untuk pemeriksaan ini
        $pemeriksaanParameterIds = [];
        foreach ($mstParamIds as $mpId) {
            $pp = \App\Models\PemeriksaanParameter::firstOrCreate([
                'pemeriksaan_id'   => $pemeriksaan->id,
                'mst_parameter_id' => $mpId,
            ]);
            $pemeriksaanParameterIds[] = $pp->id;
        }

        // Helper untuk membuat peserta+parameter
        // Gunakan status yang sudah ada dari RefStatusPemeriksaanSeeder
        $statusIds = \App\Models\RefStatusPemeriksaan::pluck('id')->toArray();

        // Jika tidak ada status, gunakan status pertama yang tersedia atau null
        if (empty($statusIds)) {
            $statusIds = [null];
        }

        $makePeserta = function (string $typeClass, int $pesertaId) use ($pemeriksaan, $pemeriksaanParameterIds, $trendVals, $statusIds) {
            $peserta = PemeriksaanPeserta::create([
                'pemeriksaan_id'            => $pemeriksaan->id,
                'peserta_type'              => $typeClass,
                'peserta_id'                => $pesertaId,
                'ref_status_pemeriksaan_id' => $statusIds[array_rand($statusIds)],
            ]);

            foreach ($pemeriksaanParameterIds as $ppId) {
                PemeriksaanPesertaParameter::create([
                    'pemeriksaan_id'           => $pemeriksaan->id,
                    'pemeriksaan_peserta_id'   => $peserta->id,
                    'pemeriksaan_parameter_id' => $ppId,
                    'nilai'                    => rand(60, 100),
                    'trend'                    => $trendVals[array_rand($trendVals)],
                ]);
            }
        };

        foreach ($atletIds as $aid) {
            $makePeserta(Atlet::class, (int) $aid);
        }
        foreach ($pelatihIds as $pid) {
            $makePeserta(Pelatih::class, (int) $pid);
        }
        foreach ($tpIds as $tid) {
            $makePeserta(TenagaPendukung::class, (int) $tid);
        }
    }
}
