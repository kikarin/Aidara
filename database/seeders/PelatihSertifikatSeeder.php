<?php

namespace Database\Seeders;

use App\Models\Pelatih;
use App\Models\PelatihSertifikat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PelatihSertifikatSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PelatihSertifikat::truncate();
        Schema::enableForeignKeyConstraints();

        $pelatihIds = Pelatih::pluck('id')->toArray();
        $data       = [
            [
                'pelatih_id'      => $pelatihIds[0] ?? 1,
                'nama_sertifikat' => 'Sertifikat Pelatih Nasional',
                'penyelenggara'   => 'PBSI',
                'tanggal_terbit'  => '2021-01-01',
            ],
            [
                'pelatih_id'      => $pelatihIds[1] ?? 2,
                'nama_sertifikat' => 'Sertifikat Pelatih Provinsi',
                'penyelenggara'   => 'PSSI',
                'tanggal_terbit'  => '2021-02-01',
            ],
            [
                'pelatih_id'      => $pelatihIds[2] ?? 3,
                'nama_sertifikat' => 'Sertifikat Pelatih Kota',
                'penyelenggara'   => 'KONI',
                'tanggal_terbit'  => '2021-03-01',
            ],
            [
                'pelatih_id'      => $pelatihIds[3] ?? 4,
                'nama_sertifikat' => 'Sertifikat Pelatih Sekolah',
                'penyelenggara'   => 'Sekolah',
                'tanggal_terbit'  => '2021-04-01',
            ],
            [
                'pelatih_id'      => $pelatihIds[4] ?? 5,
                'nama_sertifikat' => 'Sertifikat Pelatih Kejuaraan',
                'penyelenggara'   => 'Dispora',
                'tanggal_terbit'  => '2021-05-01',
            ],
        ];
        foreach ($data as $item) {
            PelatihSertifikat::create($item);
        }
    }
}
