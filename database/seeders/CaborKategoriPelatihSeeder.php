<?php

namespace Database\Seeders;

use App\Models\CaborKategori;
use App\Models\MstJenisPelatih;
use App\Models\Pelatih;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaborKategoriPelatihSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = CaborKategori::all();
        $pelatihList  = Pelatih::all();
        $jenisList    = MstJenisPelatih::pluck('nama')->toArray();

        foreach ($kategoriList as $kategori) {
            $randomPelatihs = $pelatihList->random(rand(2, min(4, $pelatihList->count())));
            foreach ($randomPelatihs as $pelatih) {
                DB::table('cabor_kategori_pelatih')->updateOrInsert([
                    'cabor_id'          => $kategori->cabor_id,
                    'cabor_kategori_id' => $kategori->id,
                    'pelatih_id'        => $pelatih->id,
                ], [
                    'is_active'     => rand(0, 1),
                    'jenis_pelatih' => $jenisList ? $jenisList[array_rand($jenisList)] : null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
