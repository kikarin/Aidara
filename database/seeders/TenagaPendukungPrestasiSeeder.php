<?php

namespace Database\Seeders;

use App\Models\TenagaPendukung;
use App\Models\TenagaPendukungPrestasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TenagaPendukungPrestasiSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        TenagaPendukungPrestasi::truncate();
        Schema::enableForeignKeyConstraints();

        $tpIds = TenagaPendukung::pluck('id')->toArray();
        $data  = [
            [
                'tenaga_pendukung_id' => $tpIds[0] ?? 1,
                'nama_event'          => 'Pendukung Nasional',
                'tingkat_id'          => 1,
                'tanggal'             => '2022-01-01',
                'peringkat'           => 'A',
                'keterangan'          => 'Lulus Nasional',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[1] ?? 2,
                'nama_event'          => 'Pendukung Provinsi',
                'tingkat_id'          => 2,
                'tanggal'             => '2022-02-01',
                'peringkat'           => 'B',
                'keterangan'          => 'Lulus Provinsi',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[2] ?? 3,
                'nama_event'          => 'Pendukung Kota',
                'tingkat_id'          => 3,
                'tanggal'             => '2022-03-01',
                'peringkat'           => 'A',
                'keterangan'          => 'Lulus Kota',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[3] ?? 4,
                'nama_event'          => 'Pendukung Sekolah',
                'tingkat_id'          => 4,
                'tanggal'             => '2022-04-01',
                'peringkat'           => 'A',
                'keterangan'          => 'Lulus Sekolah',
            ],
            [
                'tenaga_pendukung_id' => $tpIds[4] ?? 5,
                'nama_event'          => 'Pendukung Kejuaraan',
                'tingkat_id'          => 5,
                'tanggal'             => '2022-05-01',
                'peringkat'           => 'B',
                'keterangan'          => 'Lulus Kejuaraan',
            ],
        ];
        foreach ($data as $item) {
            TenagaPendukungPrestasi::create($item);
        }
    }
}
