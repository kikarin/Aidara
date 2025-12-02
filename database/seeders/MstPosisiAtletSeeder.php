<?php

namespace Database\Seeders;

use App\Models\MstPosisiAtlet;
use Illuminate\Database\Seeder;

class MstPosisiAtletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // Sepak Bola
            ['nama' => 'Penjaga Gawang'],
            ['nama' => 'Bek Tengah'],
            ['nama' => 'Bek Sayap'],
            ['nama' => 'Gelandang Bertahan'],
            ['nama' => 'Gelandang Tengah'],
            ['nama' => 'Gelandang Serang'],
            ['nama' => 'Sayap Kanan'],
            ['nama' => 'Sayap Kiri'],
            ['nama' => 'Striker'],

            // Basket
            ['nama' => 'Point Guard'],
            ['nama' => 'Shooting Guard'],
            ['nama' => 'Small Forward'],
            ['nama' => 'Power Forward'],
            ['nama' => 'Center'],
        ];

        foreach ($data as $item) {
            MstPosisiAtlet::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
