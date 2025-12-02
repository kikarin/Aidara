<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\ProgramLatihan;
use Illuminate\Database\Seeder;

class ProgramLatihanSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriIds = CaborKategori::pluck('id')->toArray();
        $caborId     = Cabor::first()?->id;
        if (empty($kategoriIds) || ! $caborId) {
            return;
        }
        $data = [
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Fisik Dasar',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-08-01',
                'periode_selesai'   => '2025-08-31',
                'keterangan'        => 'Fokus pada penguatan fisik dan stamina.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Teknik Lanjutan',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-09-01',
                'periode_selesai'   => '2025-09-30',
                'keterangan'        => 'Pendalaman teknik dan strategi.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Persiapan Kejuaraan',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-10-01',
                'periode_selesai'   => '2025-10-15',
                'keterangan'        => 'Simulasi pertandingan dan evaluasi akhir.',
            ],
        ];
        foreach ($data as $item) {
            ProgramLatihan::create($item);
        }
    }
}
