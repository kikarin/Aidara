<?php

namespace Database\Seeders;

use App\Models\MstKategoriPeserta;
use Illuminate\Database\Seeder;

class MstKategoriPesertaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'PPOPM', ],
            ['nama' => 'KONI', ],
            ['nama' => 'NPCI', ],
        ];
        foreach ($data as $item) {
            MstKategoriPeserta::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
