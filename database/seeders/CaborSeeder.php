<?php

namespace Database\Seeders;

use App\Models\Cabor;
use Illuminate\Database\Seeder;

class CaborSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Sepak Bola', 'deskripsi' => 'Olahraga tim populer di dunia.'],
            ['nama' => 'Bulu Tangkis', 'deskripsi' => 'Olahraga raket cepat dan dinamis.'],
            ['nama' => 'Basket', 'deskripsi' => 'Olahraga bola berkelompok dengan ring.'],
        ];
        foreach ($data as $item) {
            Cabor::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
