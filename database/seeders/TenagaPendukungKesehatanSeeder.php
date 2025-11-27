<?php

namespace Database\Seeders;

use App\Models\TenagaPendukung;
use App\Models\TenagaPendukungKesehatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TenagaPendukungKesehatanSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TenagaPendukungKesehatan::truncate();
        Schema::enableForeignKeyConstraints();

        $tpIds = TenagaPendukung::pluck('id')->toArray();
        $data  = [
            [
                'tenaga_pendukung_id' => $tpIds[0] ?? 1,
                'tinggi_badan'        => '170',
                'berat_badan'         => '65',
                'penglihatan'         => 'Normal',
                'pendengaran'         => 'Normal',
                'riwayat_penyakit'    => null,
                'alergi'              => null,
            ],
            [
                'tenaga_pendukung_id' => $tpIds[1] ?? 2,
                'tinggi_badan'        => '165',
                'berat_badan'         => '60',
                'penglihatan'         => 'Minus',
                'pendengaran'         => 'Normal',
                'riwayat_penyakit'    => 'Asma',
                'alergi'              => 'Debu',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[2] ?? 3,
                'tinggi_badan'        => '175',
                'berat_badan'         => '70',
                'penglihatan'         => 'Normal',
                'pendengaran'         => 'Gangguan Ringan',
                'riwayat_penyakit'    => null,
                'alergi'              => null,
            ],
            [
                'tenaga_pendukung_id' => $tpIds[3] ?? 4,
                'tinggi_badan'        => '168',
                'berat_badan'         => '62',
                'penglihatan'         => 'Plus',
                'pendengaran'         => 'Normal',
                'riwayat_penyakit'    => null,
                'alergi'              => 'Udang',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[4] ?? 5,
                'tinggi_badan'        => '180',
                'berat_badan'         => '80',
                'penglihatan'         => 'Normal',
                'pendengaran'         => 'Normal',
                'riwayat_penyakit'    => 'Hipertensi',
                'alergi'              => null,
            ],
        ];
        foreach ($data as $item) {
            TenagaPendukungKesehatan::create($item);
        }
    }
}
