<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\AtletOrangTua;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletOrangTuaSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        AtletOrangTua::truncate();
        Schema::enableForeignKeyConstraints();

        $atletIds = Atlet::pluck('id')->toArray();
        $data     = [
            [
                'atlet_id'          => $atletIds[0] ?? 1,
                'nama_ibu_kandung'  => 'Siti Fatimah',
                'nama_ayah_kandung' => 'Slamet Riyadi',
            ],
            [
                'atlet_id'          => $atletIds[1] ?? 2,
                'nama_ibu_kandung'  => 'Dewi Sartika',
                'nama_ayah_kandung' => 'Bambang Pamungkas',
            ],
            [
                'atlet_id'          => $atletIds[2] ?? 3,
                'nama_ibu_kandung'  => 'Sri Mulyani',
                'nama_ayah_kandung' => 'Joko Widodo',
            ],
            [
                'atlet_id'          => $atletIds[3] ?? 4,
                'nama_ibu_kandung'  => 'Rina Marlina',
                'nama_ayah_kandung' => 'Agus Salim',
            ],
            [
                'atlet_id'          => $atletIds[4] ?? 5,
                'nama_ibu_kandung'  => 'Yuni Astuti',
                'nama_ayah_kandung' => 'Dedi Mulyadi',
            ],
        ];
        foreach ($data as $item) {
            AtletOrangTua::create($item);
        }
    }
}
