<?php

namespace Database\Seeders;

use App\Models\RefStatusPemeriksaan;
use Illuminate\Database\Seeder;

class RefStatusPemeriksaanSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['nama' => 'Normal'],
            ['nama' => 'Tidak Normal'],
            ['nama' => 'Cedera Ringan'],
            ['nama' => 'Cedera Berat'],
            ['nama' => 'Perlu Tindak Lanjut'],
        ];

        foreach ($statuses as $status) {
            RefStatusPemeriksaan::create($status);
        }
    }
}
