<?php

namespace Database\Seeders;

use App\Models\MstJenisUnitPendukung;
use Illuminate\Database\Seeder;

class MstJenisUnitPendukungSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['nama' => 'Hewan'],
            ['nama' => 'Alat'],
            ['nama' => 'Kendaraan'],
            ['nama' => 'Robot'],
        ];

        foreach ($statuses as $status) {
            MstJenisUnitPendukung::create($status);
        }
    }
}
