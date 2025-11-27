<?php

namespace Database\Seeders;

use App\Models\TenagaPendukung;
use App\Models\TenagaPendukungSertifikat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TenagaPendukungSertifikatSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TenagaPendukungSertifikat::truncate();
        Schema::enableForeignKeyConstraints();

        $tpIds = TenagaPendukung::pluck('id')->toArray();
        $data  = [
            [
                'tenaga_pendukung_id' => $tpIds[0] ?? 1,
                'nama_sertifikat'     => 'Sertifikat Tenaga Pendukung Nasional',
                'penyelenggara'       => 'KONI',
                'tanggal_terbit'      => '2022-01-01',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[1] ?? 2,
                'nama_sertifikat'     => 'Sertifikat Tenaga Pendukung Provinsi',
                'penyelenggara'       => 'Dispora',
                'tanggal_terbit'      => '2022-02-01',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[2] ?? 3,
                'nama_sertifikat'     => 'Sertifikat Tenaga Pendukung Kota',
                'penyelenggara'       => 'Pemkot',
                'tanggal_terbit'      => '2022-03-01',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[3] ?? 4,
                'nama_sertifikat'     => 'Sertifikat Tenaga Pendukung Sekolah',
                'penyelenggara'       => 'Sekolah',
                'tanggal_terbit'      => '2022-04-01',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[4] ?? 5,
                'nama_sertifikat'     => 'Sertifikat Tenaga Pendukung Kejuaraan',
                'penyelenggara'       => 'Kemenpora',
                'tanggal_terbit'      => '2022-05-01',
            ],
        ];
        foreach ($data as $item) {
            TenagaPendukungSertifikat::create($item);
        }
    }
}
