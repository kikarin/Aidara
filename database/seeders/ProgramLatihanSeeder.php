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
        $tahapOptions = ['persiapan umum', 'persiapan khusus', 'prapertandingan', 'pertandingan', 'transisi'];
        
        $data = [
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Fisik Dasar',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-08-01',
                'periode_selesai'   => '2025-08-31',
                'tahap'            => $tahapOptions[0], // persiapan umum
                'keterangan'        => 'Fokus pada penguatan fisik dan stamina.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Teknik Lanjutan',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-09-01',
                'periode_selesai'   => '2025-09-30',
                'tahap'            => $tahapOptions[1], // persiapan khusus
                'keterangan'        => 'Pendalaman teknik dan strategi.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Latihan Persiapan Kejuaraan',
                'cabor_kategori_id' => $kategoriIds[0],
                'periode_mulai'     => '2025-10-01',
                'periode_selesai'   => '2025-10-15',
                'tahap'            => $tahapOptions[2], // prapertandingan
                'keterangan'        => 'Simulasi pertandingan dan evaluasi akhir.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Program Pertandingan Nasional',
                'cabor_kategori_id' => $kategoriIds[0] ?? null,
                'periode_mulai'     => '2025-11-01',
                'periode_selesai'   => '2025-11-30',
                'tahap'            => $tahapOptions[3], // pertandingan
                'keterangan'        => 'Program latihan selama periode pertandingan.',
            ],
            [
                'cabor_id'          => $caborId,
                'nama_program'      => 'Program Transisi Pasca Pertandingan',
                'cabor_kategori_id' => $kategoriIds[0] ?? null,
                'periode_mulai'     => '2025-12-01',
                'periode_selesai'   => '2025-12-31',
                'tahap'            => $tahapOptions[4], // transisi
                'keterangan'        => 'Program pemulihan dan evaluasi pasca pertandingan.',
            ],
        ];
        foreach ($data as $item) {
            ProgramLatihan::create($item);
        }
    }
}
