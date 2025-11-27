<?php

namespace Database\Seeders;

use App\Models\MstJuara;
use Illuminate\Database\Seeder;

class MstJuaraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama' => 'Juara 1'],
            ['nama' => 'Juara 2'],
            ['nama' => 'Juara 3'],
            ['nama' => 'Juara Harapan'],
            ['nama' => 'Juara Umum'],
            ['nama' => 'Juara Favorit'],
            ['nama' => 'Juara Terbaik'],
        ];

        foreach ($data as $item) {
            MstJuara::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
