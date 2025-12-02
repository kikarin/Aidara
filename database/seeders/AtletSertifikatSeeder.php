<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\AtletSertifikat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletSertifikatSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        AtletSertifikat::truncate();
        Schema::enableForeignKeyConstraints();

        $atletIds = Atlet::pluck('id')->toArray();
        $data     = [
            [
                'atlet_id'        => $atletIds[0] ?? 1,
                'nama_sertifikat' => 'Sertifikat Nasional',
                'penyelenggara'   => 'PBSI',
                'tanggal_terbit'  => '2022-01-01',
            ],
            [
                'atlet_id'        => $atletIds[1] ?? 2,
                'nama_sertifikat' => 'Sertifikat Provinsi',
                'penyelenggara'   => 'PSSI',
                'tanggal_terbit'  => '2022-02-01',
            ],
            [
                'atlet_id'        => $atletIds[2] ?? 3,
                'nama_sertifikat' => 'Sertifikat Kota',
                'penyelenggara'   => 'KONI',
                'tanggal_terbit'  => '2022-03-01',
            ],
            [
                'atlet_id'        => $atletIds[3] ?? 4,
                'nama_sertifikat' => 'Sertifikat Sekolah',
                'penyelenggara'   => 'Sekolah',
                'tanggal_terbit'  => '2022-04-01',
            ],
            [
                'atlet_id'        => $atletIds[4] ?? 5,
                'nama_sertifikat' => 'Sertifikat Kejuaraan',
                'penyelenggara'   => 'Dispora',
                'tanggal_terbit'  => '2022-05-01',
            ],
        ];
        foreach ($data as $item) {
            AtletSertifikat::create($item);
        }
    }
}
