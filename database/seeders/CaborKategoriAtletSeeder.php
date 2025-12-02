<?php

namespace Database\Seeders;

use App\Models\Atlet;
use App\Models\CaborKategori;
use App\Models\MstPosisiAtlet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CaborKategoriAtletSeeder extends Seeder
{
    public function run(): void
    {
        $kategoriList = CaborKategori::with('cabor')->get();
        $atletList    = Atlet::all();
        $posisiList   = MstPosisiAtlet::pluck('nama')->toArray();

        // track cabor yang sudah dimiliki setiap atlet
        $atletCaborMap = [];

        foreach ($kategoriList as $kategori) {
            // filter atlet sesuai jenis kelamin kategori
            $eligibleAtlets = $atletList->filter(function ($atlet) use ($kategori, $atletCaborMap) {
                // cek gender
                if ($kategori->jenis_kelamin === 'L' && $atlet->jenis_kelamin !== 'L') {
                    return false;
                }
                if ($kategori->jenis_kelamin === 'P' && $atlet->jenis_kelamin !== 'P') {
                    return false;
                }
                // 'C' berarti campuran → boleh semua

                // cek apakah atlet sudah punya cabor lain
                if (isset($atletCaborMap[$atlet->id]) && $atletCaborMap[$atlet->id] != $kategori->cabor_id) {
                    return false;
                }

                return true;
            });

            if ($eligibleAtlets->isEmpty()) {
                continue;
            }

            // pilih beberapa atlet untuk kategori ini
            $selectedAtlets = $eligibleAtlets->random(min(3, $eligibleAtlets->count())); // ambil max 3 biar rapi

            foreach ($selectedAtlets as $atlet) {
                // set cabor utk atlet ini
                $atletCaborMap[$atlet->id] = $kategori->cabor_id;

                DB::table('cabor_kategori_atlet')->updateOrInsert([
                    'cabor_id'          => $kategori->cabor_id,
                    'cabor_kategori_id' => $kategori->id,
                    'atlet_id'          => $atlet->id,
                ], [
                    'is_active'    => 1,
                    'posisi_atlet' => $posisiList ? $posisiList[array_rand($posisiList)] : null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
