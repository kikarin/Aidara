<?php

namespace Database\Seeders;

use App\Models\Cabor;
use App\Models\CaborKategori;
use Illuminate\Database\Seeder;

class CaborKategoriSeeder extends Seeder
{
    public function run(): void
    {
        $cabors = Cabor::pluck('id', 'nama');
        $data   = [
            // Sepak Bola
            ['cabor_id' => $cabors['Sepak Bola'] ?? null, 'nama' => 'Putra', 'deskripsi' => 'Sepak bola putra', 'jenis_kelamin' => 'L'],
            ['cabor_id' => $cabors['Sepak Bola'] ?? null, 'nama' => 'Putri', 'deskripsi' => 'Sepak bola putri', 'jenis_kelamin' => 'P'],
            // Bulu Tangkis
            ['cabor_id' => $cabors['Bulu Tangkis'] ?? null, 'nama' => 'Tunggal', 'deskripsi' => 'Bulu tangkis tunggal', 'jenis_kelamin' => 'C'],
            ['cabor_id' => $cabors['Bulu Tangkis'] ?? null, 'nama' => 'Ganda', 'deskripsi' => 'Bulu tangkis ganda', 'jenis_kelamin' => 'C'],
            // Basket
            ['cabor_id' => $cabors['Basket'] ?? null, 'nama' => '3x3', 'deskripsi' => 'Basket 3 lawan 3', 'jenis_kelamin' => 'C'],
            ['cabor_id' => $cabors['Basket'] ?? null, 'nama' => '5x5', 'deskripsi' => 'Basket 5 lawan 5', 'jenis_kelamin' => 'C'],
        ];
        foreach ($data as $item) {
            if ($item['cabor_id']) {
                CaborKategori::firstOrCreate([
                    'cabor_id' => $item['cabor_id'],
                    'nama'     => $item['nama'],
                ], $item);
            }
        }
    }
}
