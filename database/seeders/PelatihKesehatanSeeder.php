<?php

namespace Database\Seeders;

use App\Models\Pelatih;
use App\Models\PelatihKesehatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PelatihKesehatanSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PelatihKesehatan::truncate();
        Schema::enableForeignKeyConstraints();

        $pelatihIds = Pelatih::pluck('id')->toArray();
        $data       = [
            [
                'pelatih_id'   => $pelatihIds[0] ?? 1,
                'tinggi_badan' => '172',
                'berat_badan'  => '68',
            ],
            [
                'pelatih_id'   => $pelatihIds[1] ?? 2,
                'tinggi_badan' => '169',
                'berat_badan'  => '65',
            ],
            [
                'pelatih_id'   => $pelatihIds[2] ?? 3,
                'tinggi_badan' => '175',
                'berat_badan'  => '70',
            ],
            [
                'pelatih_id'   => $pelatihIds[3] ?? 4,
                'tinggi_badan' => '168',
                'berat_badan'  => '64',
            ],
            [
                'pelatih_id'   => $pelatihIds[4] ?? 5,
                'tinggi_badan' => '178',
                'berat_badan'  => '75',
            ],
        ];
        foreach ($data as $item) {
            PelatihKesehatan::create($item);
        }
    }
}
