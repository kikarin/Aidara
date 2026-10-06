<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingFacility;
use Illuminate\Database\Seeder;

class BookingFacilitySeeder extends Seeder
{
    public function run(): void
    {
        $facilities = [
            ['code' => 'toilet', 'name' => 'Toilet', 'icon' => 'shower-head', 'description' => null],
            ['code' => 'ruang_ganti', 'name' => 'Ruang Ganti', 'icon' => 'shower-head', 'description' => null],
            ['code' => 'mushola', 'name' => 'Mushola', 'icon' => 'landmark', 'description' => null],
            ['code' => 'tribun', 'name' => 'Tribun Penonton', 'icon' => 'armchair', 'description' => null],
            ['code' => 'kantin', 'name' => 'Kantin', 'icon' => 'utensils', 'description' => null],
            ['code' => 'wifi', 'name' => 'Wifi', 'icon' => 'wifi', 'description' => null],
            ['code' => 'keamanan', 'name' => 'Pos Keamanan', 'icon' => 'shield', 'description' => null],
            ['code' => 'listrik', 'name' => 'Sumber Listrik', 'icon' => 'zap', 'description' => null],
            ['code' => 'papan_skor', 'name' => 'Papan Skor', 'icon' => 'monitor', 'description' => 'Papan skor elektronik/manual'],
            ['code' => 'net', 'name' => 'Net / Jarring', 'icon' => 'grid-3x3', 'description' => 'Net untuk lapangan (voli, tennis, bulu tangkis, dll)'],
            ['code' => 'gawang', 'name' => 'Gawang', 'icon' => 'target', 'description' => 'Gawang sepak bola / futsal'],
            ['code' => 'ring_basket', 'name' => 'Ring Basket', 'icon' => 'circle', 'description' => null],
            ['code' => 'lampu', 'name' => 'Lampu Penerangan', 'icon' => 'lightbulb', 'description' => 'Lampu lapangan / floodlight'],
            ['code' => 'pengeras_suara', 'name' => 'Pengeras Suara', 'icon' => 'megaphone', 'description' => 'Sound system / pengeras suara'],
            ['code' => 'papan_waktu', 'name' => 'Papan Waktu', 'icon' => 'timer', 'description' => null],
            ['code' => 'garis_lapangan', 'name' => 'Garis Lapangan', 'icon' => 'flag', 'description' => null],
            ['code' => 'peralatan', 'name' => 'Peralatan Olahraga', 'icon' => 'wrench', 'description' => 'Peralatan pendukung kegiatan'],
        ];

        foreach ($facilities as $i => $row) {
            BookingFacility::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'icon' => $row['icon'],
                    'description' => $row['description'],
                    'is_active' => true,
                    'sort_order' => $i + 1,
                ]
            );
        }
    }
}
