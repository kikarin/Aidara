<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\AtletKesehatan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletKesehatanSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        AtletKesehatan::truncate();
        Schema::enableForeignKeyConstraints();

        $atletIds = Atlet::pluck('id')->toArray();
        $data     = [
            [
                'atlet_id'     => $atletIds[0] ?? 1,
                'tinggi_badan' => '170',
                'berat_badan'  => '60',
            ],
            [
                'atlet_id'     => $atletIds[1] ?? 2,
                'tinggi_badan' => '165',
                'berat_badan'  => '55',
            ],
            [
                'atlet_id'     => $atletIds[2] ?? 3,
                'tinggi_badan' => '172',
                'berat_badan'  => '62',
            ],
            [
                'atlet_id'     => $atletIds[3] ?? 4,
                'tinggi_badan' => '168',
                'berat_badan'  => '58',
            ],
            [
                'atlet_id'     => $atletIds[4] ?? 5,
                'tinggi_badan' => '175',
                'berat_badan'  => '65',
            ],
        ];
        foreach ($data as $item) {
            AtletKesehatan::create($item);
        }
    }
}
