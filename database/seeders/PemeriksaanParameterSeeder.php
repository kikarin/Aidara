<?php

namespace Database\Seeders;

use App\Models\MstParameter;
use App\Models\Pemeriksaan;
use App\Models\PemeriksaanParameter;
use Illuminate\Database\Seeder;

class PemeriksaanParameterSeeder extends Seeder
{
    public function run(): void
    {
        $pemeriksaan = Pemeriksaan::first();
        if (! $pemeriksaan) {
            return;
        }

        // Ambil semua master parameter yang tersedia
        $mstParameters = MstParameter::all();

        if ($mstParameters->isEmpty()) {
            return;
        }

        // Buat pemeriksaan parameter untuk setiap master parameter
        foreach ($mstParameters as $mstParameter) {
            PemeriksaanParameter::create([
                'pemeriksaan_id'   => $pemeriksaan->id,
                'mst_parameter_id' => $mstParameter->id,
            ]);
        }
    }
}
