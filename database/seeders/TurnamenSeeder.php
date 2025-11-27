<?php

namespace Database\Seeders;

use App\Models\Turnamen;
use App\Models\CaborKategori;
use App\Models\Atlet;
use App\Models\Pelatih;
use App\Models\TenagaPendukung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TurnamenSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = CaborKategori::first();
        if (! $kategori) {
            return;
        }

        $turnamens = [
            [
                'nama'              => 'Turnamen Pra Musim',
                'cabor_kategori_id' => $kategori->id,
                'tanggal_mulai'     => '2025-09-10',
                'tanggal_selesai'   => '2025-09-15',
                'tingkat_id'        => null,
                'lokasi'            => 'Stadion Utama',
                'juara_id'          => null,
                'hasil'             => null,
                'evaluasi'          => 'Evaluasi performa awal musim',
            ],
            [
                'nama'              => 'Piala Daerah',
                'cabor_kategori_id' => $kategori->id,
                'tanggal_mulai'     => '2025-10-05',
                'tanggal_selesai'   => '2025-10-12',
                'tingkat_id'        => null,
                'lokasi'            => 'Gelanggang Remaja',
                'juara_id'          => null,
                'hasil'             => null,
                'evaluasi'          => 'Catatan teknis & strategi',
            ],
        ];

        foreach ($turnamens as $data) {
            $t = Turnamen::create($data);

            // Peserta (ambil sebagian contoh)
            $atletIds   = Atlet::inRandomOrder()->limit(5)->pluck('id');
            $pelatihIds = Pelatih::inRandomOrder()->limit(2)->pluck('id');
            $tpIds      = TenagaPendukung::inRandomOrder()->limit(2)->pluck('id');

            $rows = [];
            foreach ($atletIds as $id) {
                $rows[] = [
                    'turnamen_id' => $t->id,
                    'peserta_type'=> 'App\\Models\\Atlet',
                    'peserta_id'  => $id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
            foreach ($pelatihIds as $id) {
                $rows[] = [
                    'turnamen_id' => $t->id,
                    'peserta_type'=> 'App\\Models\\Pelatih',
                    'peserta_id'  => $id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
            foreach ($tpIds as $id) {
                $rows[] = [
                    'turnamen_id' => $t->id,
                    'peserta_type'=> 'App\\Models\\TenagaPendukung',
                    'peserta_id'  => $id,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (! empty($rows)) {
                DB::table('turnamen_peserta')->insert($rows);
            }
        }
    }
}
