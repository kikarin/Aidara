<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\AtletPrestasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AtletPrestasiSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        AtletPrestasi::truncate();
        Schema::enableForeignKeyConstraints();

        $atletIds = Atlet::pluck('id')->toArray();
        $data     = [
            [
                'atlet_id'   => $atletIds[0] ?? 1,
                'nama_event' => 'Kejuaraan Nasional',
                'tingkat_id' => 1,
                'tanggal'    => '2023-01-01',
                'peringkat'  => '1',
                'keterangan' => 'Juara 1 Nasional',
            ],
            [
                'atlet_id'   => $atletIds[1] ?? 2,
                'nama_event' => 'Kejuaraan Provinsi',
                'tingkat_id' => 2,
                'tanggal'    => '2023-02-01',
                'peringkat'  => '2',
                'keterangan' => 'Juara 2 Provinsi',
            ],
            [
                'atlet_id'   => $atletIds[2] ?? 3,
                'nama_event' => 'Kejuaraan Kota',
                'tingkat_id' => 3,
                'tanggal'    => '2023-03-01',
                'peringkat'  => '3',
                'keterangan' => 'Juara 3 Kota',
            ],
            [
                'atlet_id'   => $atletIds[3] ?? 4,
                'nama_event' => 'Kejuaraan Sekolah',
                'tingkat_id' => 4,
                'tanggal'    => '2023-04-01',
                'peringkat'  => '1',
                'keterangan' => 'Juara 1 Sekolah',
            ],
            [
                'atlet_id'   => $atletIds[4] ?? 5,
                'nama_event' => 'Kejuaraan Antar Club',
                'tingkat_id' => 5,
                'tanggal'    => '2023-05-01',
                'peringkat'  => '2',
                'keterangan' => 'Juara 2 Club',
            ],
        ];
        foreach ($data as $item) {
            AtletPrestasi::create($item);
        }
    }
}
