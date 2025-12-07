<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\MstJenisTenagaPendukung;
use App\Models\TenagaPendukung;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaborKategoriTenagaPendukungSeeder extends Seeder
{
    public function run(): void
    {
        $caborList  = Cabor::all();
        $tenagaList = TenagaPendukung::all();
        $jenisList  = MstJenisTenagaPendukung::pluck('nama')->toArray();

        // Tenaga pendukung bisa di beberapa cabor
        foreach ($tenagaList as $tenaga) {
            // Random pilih 1-3 cabor untuk setiap tenaga pendukung
            $randomCabors = $caborList->random(rand(1, min(3, $caborList->count())));
            
            foreach ($randomCabors as $cabor) {
                // Random pilih kategori dari cabor ini (atau null untuk langsung ke cabor)
                $kategoriList = CaborKategori::where('cabor_id', $cabor->id)->get();
                $kategori = $kategoriList->isNotEmpty() && rand(0, 1) ? $kategoriList->random() : null;
                
                DB::table('cabor_kategori_tenaga_pendukung')->updateOrInsert([
                    'cabor_id'            => $cabor->id,
                    'cabor_kategori_id'   => $kategori?->id,
                    'tenaga_pendukung_id' => $tenaga->id,
                ], [
                    'is_active'              => rand(0, 1),
                    'jenis_tenaga_pendukung' => $jenisList ? $jenisList[array_rand($jenisList)] : null,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }
        }
    }
}
