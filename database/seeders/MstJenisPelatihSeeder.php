<?php

namespace Database\Seeders;

use App\Models\MstJenisPelatih;
use Illuminate\Database\Seeder;

class MstJenisPelatihSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama' => 'Pelatih Fisik'],
            ['nama' => 'Pelatih Teknik'],
            ['nama' => 'Pelatih Mental'],
        ];

        foreach ($data as $item) {
            MstJenisPelatih::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
