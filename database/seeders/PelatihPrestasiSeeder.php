<?php

namespace Database\Seeders;

use App\Models\Pelatih;
use App\Models\PelatihPrestasi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PelatihPrestasiSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        PelatihPrestasi::truncate();
        Schema::enableForeignKeyConstraints();

        $pelatihIds = Pelatih::pluck('id')->toArray();
        $data       = [
            [
                'pelatih_id' => $pelatihIds[0] ?? 1,
                'nama_event' => 'Pelatihan Nasional',
                'tingkat_id' => 1,
                'tanggal'    => '2022-01-01',
                'peringkat'  => 'A',
                'keterangan' => 'Lulus Nasional',
            ],
            [
                'pelatih_id' => $pelatihIds[1] ?? 2,
                'nama_event' => 'Pelatihan Provinsi',
                'tingkat_id' => 2,
                'tanggal'    => '2022-02-01',
                'peringkat'  => 'B',
                'keterangan' => 'Lulus Provinsi',
            ],
            [
                'pelatih_id' => $pelatihIds[2] ?? 3,
                'nama_event' => 'Pelatihan Kota',
                'tingkat_id' => 3,
                'tanggal'    => '2022-03-01',
                'peringkat'  => 'A',
                'keterangan' => 'Lulus Kota',
            ],
            [
                'pelatih_id' => $pelatihIds[3] ?? 4,
                'nama_event' => 'Pelatihan Sekolah',
                'tingkat_id' => 4,
                'tanggal'    => '2022-04-01',
                'peringkat'  => 'A',
                'keterangan' => 'Lulus Sekolah',
            ],
            [
                'pelatih_id' => $pelatihIds[4] ?? 5,
                'nama_event' => 'Pelatihan Antar Club',
                'tingkat_id' => 5,
                'tanggal'    => '2022-05-01',
                'peringkat'  => 'B',
                'keterangan' => 'Lulus Club',
            ],
        ];
        foreach ($data as $item) {
            PelatihPrestasi::create($item);
        }
    }
}
