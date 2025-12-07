<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\MstJenisPelatih;
use App\Models\Pelatih;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaborKategoriPelatihSeeder extends Seeder
{
    public function run(): void
    {
        $caborList   = Cabor::all();
        $pelatihList = Pelatih::all();
        $jenisList   = MstJenisPelatih::pluck('nama')->toArray();

        // Pelatih bisa di beberapa cabor
        foreach ($pelatihList as $pelatih) {
            // Random pilih 1-3 cabor untuk setiap pelatih
            $randomCabors = $caborList->random(rand(1, min(3, $caborList->count())));
            
            foreach ($randomCabors as $cabor) {
                // Random pilih kategori dari cabor ini (atau null untuk langsung ke cabor)
                $kategoriList = CaborKategori::where('cabor_id', $cabor->id)->get();
                $kategori = $kategoriList->isNotEmpty() && rand(0, 1) ? $kategoriList->random() : null;
                
                DB::table('cabor_kategori_pelatih')->updateOrInsert([
                    'cabor_id'          => $cabor->id,
                    'cabor_kategori_id' => $kategori?->id,
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
