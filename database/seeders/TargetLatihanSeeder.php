<?php

namespace Database\Seeders;

use App\Models\TargetLatihan;
use Illuminate\Database\Seeder;

class TargetLatihanSeeder extends Seeder
{
    public function run(): void
    {
        // Target latihan untuk atlet
        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'atlet',
            'deskripsi'          => 'Kecepatan Lari 100m',
            'satuan'             => 'detik',
            'nilai_target'       => '12.5',
        ]);

        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'atlet',
            'deskripsi'          => 'Kekuatan Angkat Berat',
            'satuan'             => 'kg',
            'nilai_target'       => '80',
        ]);

        // Target latihan untuk pelatih
        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'pelatih',
            'deskripsi'          => 'Kemampuan Analisis Teknik',
            'satuan'             => 'skala 1-10',
            'nilai_target'       => '8',
        ]);

        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'pelatih',
            'deskripsi'          => 'Kemampuan Komunikasi',
            'satuan'             => 'skala 1-10',
            'nilai_target'       => '9',
        ]);

        // Target latihan untuk tenaga pendukung
        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'tenaga-pendukung',
            'deskripsi'          => 'Kemampuan Fisioterapi',
            'satuan'             => 'skala 1-10',
            'nilai_target'       => '8.5',
        ]);

        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'individu',
            'peruntukan'         => 'tenaga-pendukung',
            'deskripsi'          => 'Pengetahuan Nutrisi',
            'satuan'             => 'skala 1-10',
            'nilai_target'       => '9',
        ]);

        // Target latihan kelompok
        TargetLatihan::create([
            'program_latihan_id' => 1,
            'jenis_target'       => 'kelompok',
            'peruntukan'         => null,
            'deskripsi'          => 'Kerjasama Tim',
            'satuan'             => 'skala 1-10',
            'nilai_target'       => '8',
        ]);
    }
}
