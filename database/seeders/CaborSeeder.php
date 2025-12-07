<?php

namespace Database\Seeders;

use App\Models\Cabor;
use Illuminate\Database\Seeder;

class CaborSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Atletik', 'deskripsi' => 'Cabang olahraga tertua yang mencakup lari, lompat, dan lempar.'],
            ['nama' => 'Anggar', 'deskripsi' => 'Olahraga bela diri menggunakan pedang.'],
            ['nama' => 'Angkat Besi', 'deskripsi' => 'Olahraga mengangkat beban dengan teknik snatch dan clean & jerk.'],
            ['nama' => 'Bola Basket', 'deskripsi' => 'Olahraga bola berkelompok dengan ring.'],
            ['nama' => 'Bulu Tangkis', 'deskripsi' => 'Olahraga raket cepat dan dinamis.'],
            ['nama' => 'Dayung', 'deskripsi' => 'Olahraga air menggunakan dayung dan perahu.'],
            ['nama' => 'Karate', 'deskripsi' => 'Seni bela diri asal Jepang.'],
            ['nama' => 'Panahan', 'deskripsi' => 'Olahraga memanah target dengan busur.'],
            ['nama' => 'Panjat Tebing', 'deskripsi' => 'Olahraga memanjat dinding atau tebing.'],
            ['nama' => 'Pencak Silat', 'deskripsi' => 'Seni bela diri tradisional Indonesia.'],
            ['nama' => 'Sepak Bola', 'deskripsi' => 'Olahraga tim populer di dunia.'],
            ['nama' => 'Taekwondo', 'deskripsi' => 'Seni bela diri asal Korea yang fokus pada tendangan.'],
            ['nama' => 'Taekwondo Poomsae', 'deskripsi' => 'Kategori Taekwondo dengan gerakan jurus.'],
            ['nama' => 'Tinju', 'deskripsi' => 'Olahraga bela diri dengan pukulan tangan.'],
            ['nama' => 'Voli Indoor', 'deskripsi' => 'Olahraga bola voli dalam ruangan.'],
            ['nama' => 'Voli Pasir', 'deskripsi' => 'Olahraga bola voli di pantai/pasir.'],
        ];

        foreach ($data as $item) {
            Cabor::firstOrCreate(['nama' => $item['nama']], $item);
        }
    }
}
