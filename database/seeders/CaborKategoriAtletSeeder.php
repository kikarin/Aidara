<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\Cabor;
use App\Models\CaborKategori;
use App\Models\MstPosisiAtlet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaborKategoriAtletSeeder extends Seeder
{
    public function run(): void
    {
        $caborList  = Cabor::all();
        $atletList  = Atlet::all();
        $posisiList = MstPosisiAtlet::pluck('nama')->toArray();

        // Assign setiap atlet ke satu cabor saja (sesuai unique constraint baru)
        foreach ($atletList as $atlet) {
            // Random pilih satu cabor
            $cabor = $caborList->random();

            // Filter kategori berdasarkan jenis kelamin atlet
            $kategoriList = CaborKategori::where('cabor_id', $cabor->id)
                ->where(function ($q) use ($atlet) {
                    $q->where('jenis_kelamin', $atlet->jenis_kelamin)
                      ->orWhere('jenis_kelamin', 'C'); // Campuran
                })
                ->get();

            // Random pilih kategori dari cabor ini (atau null untuk langsung ke cabor)
            $kategori = $kategoriList->isNotEmpty() && rand(0, 1) ? $kategoriList->random() : null;

            DB::table('cabor_kategori_atlet')->updateOrInsert([
                'cabor_id' => $cabor->id,
                'atlet_id' => $atlet->id,
            ], [
                'cabor_kategori_id' => $kategori?->id,
                'is_active'         => 1,
                'posisi_atlet'      => $posisiList ? $posisiList[array_rand($posisiList)] : null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
