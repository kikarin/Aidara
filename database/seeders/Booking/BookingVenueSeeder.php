<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingVenue;
use Illuminate\Database\Seeder;

class BookingVenueSeeder extends Seeder
{
    public function run(): void
    {
        $venues = [
            ['code' => 'pakansari', 'name' => 'Stadion Pakansari', 'sort_order' => 1],
            ['code' => 'laga_tangkas', 'name' => 'Gedung Laga Tangkas', 'sort_order' => 2],
            ['code' => 'laga_satria', 'name' => 'Gedung Laga Satria', 'sort_order' => 3],
            ['code' => 'tennis_kapten_muslihat', 'name' => 'Lapangan Tennis Kapten Muslihat', 'sort_order' => 4],
            ['code' => 'sirkuit_road_race', 'name' => 'Sirkuit Road Race', 'sort_order' => 5],
            ['code' => 'aquatic', 'name' => 'Aquatic Kabupaten Bogor', 'sort_order' => 6],
            ['code' => 'sarana_lainnya', 'name' => 'Sarana Olahraga Lainnya', 'sort_order' => 7],
        ];

        foreach ($venues as $row) {
            BookingVenue::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $row['sort_order'],
                ]
            );
        }

        $this->seedPakansariAreas();
        $this->seedTennisAreas();
        $this->seedLagaAreas();
        $this->seedRoadRaceAreas();
        $this->seedAquaticAreas();
        $this->seedSaranaLainnyaAreas();
    }

    private function seedPakansariAreas(): void
    {
        $venue = $this->venue('pakansari');

        $olahraga = [
            ['code' => 'lapangan_utama', 'name' => 'Lapangan Utama Stadion'],
            ['code' => 'lintasan_atletik', 'name' => 'Lintasan Atletik'],
            ['code' => 'lapangan_luar_a', 'name' => 'Lapangan Luar A'],
            ['code' => 'lintasan_atletik_luar', 'name' => 'Lintasan Atletik Luar'],
            ['code' => 'lapangan_luar_b', 'name' => 'Lapangan Luar B'],
            ['code' => 'panjat_tebing', 'name' => 'Panjat Tebing'],
        ];

        foreach ($olahraga as $i => $area) {
            $this->upsertArea($venue->id, $area['code'], $area['name'], false, $i + 1);
        }

        $parkir = [
            'parkir_blok_a' => 'Area Lahan Parkir Blok A',
            'parkir_blok_b' => 'Area Lahan Parkir Blok B',
            'parkir_blok_c' => 'Area Lahan Parkir Blok C',
            'parkir_blok_d_e' => 'Area Lahan Parkir Blok D-E',
            'parkir_blok_f' => 'Area Lahan Parkir Blok F',
            'parkir_blok_g' => 'Area Lahan Parkir Blok G',
            'parkir_blok_h' => 'Area Lahan Parkir Blok H',
            'parkir_blok_i' => 'Area Lahan Parkir Blok I',
            'parkir_blok_j' => 'Area Lahan Parkir Blok J',
            'plaza_utara' => 'Plaza Utara',
            'plaza_selatan' => 'Plaza Selatan',
        ];

        $sort = 20;
        foreach ($parkir as $code => $name) {
            $this->upsertArea($venue->id, $code, $name, false, $sort++);
        }

        $nonOlahraga = [
            ['code' => 'lapangan_non_olahraga', 'name' => 'Lapangan Stadion (Non Olahraga)'],
            ['code' => 'tribun', 'name' => 'Tribun Stadion'],
            ['code' => 'lahan_ring_1', 'name' => 'Lahan Ring 1'],
            ['code' => 'lahan_ring_2', 'name' => 'Lahan Ring 2'],
            ['code' => 'ruang_kantor', 'name' => 'Ruang Kantor'],
        ];

        foreach ($nonOlahraga as $area) {
            $this->upsertArea($venue->id, $area['code'], $area['name'], false, $sort++);
        }
    }

    private function seedTennisAreas(): void
    {
        $venue = $this->venue('tennis_kapten_muslihat');

        $this->upsertArea($venue->id, 'indoor_a', 'Lapangan Indoor A', true, 1);
        $this->upsertArea($venue->id, 'outdoor_c', 'Lapangan Outdoor C', true, 2);
        $this->upsertArea($venue->id, 'indoor', 'Lapangan Indoor (umum)', false, 3);
        $this->upsertArea($venue->id, 'outdoor', 'Lapangan Outdoor (umum)', false, 4);
    }

    private function seedLagaAreas(): void
    {
        foreach (['laga_tangkas', 'laga_satria'] as $code) {
            $venue = $this->venue($code);
            $this->upsertArea($venue->id, 'utama', 'Area Utama Gedung', false, 1);
            $this->upsertArea($venue->id, 'parkir', 'Area Lahan Parkir', false, 2);
            $this->upsertArea($venue->id, 'ruang_kantor', 'Ruang Kantor', false, 3);
        }
    }

    private function seedRoadRaceAreas(): void
    {
        $venue = $this->venue('sirkuit_road_race');
        $this->upsertArea($venue->id, 'sirkuit', 'Sirkuit', false, 1);
        $this->upsertArea($venue->id, 'paddock', 'Paddock', false, 2);
    }

    private function seedAquaticAreas(): void
    {
        $venue = $this->venue('aquatic');
        $this->upsertArea($venue->id, 'kolam', 'Kolam Aquatic', false, 1);
    }

    private function seedSaranaLainnyaAreas(): void
    {
        $venue = $this->venue('sarana_lainnya');

        $areas = [
            ['code' => 'ruang_stadion_mini_cibinong', 'name' => 'Ruangan sekitar Stadion Mini Cibinong (Persikabo)'],
            ['code' => 'ruang_gelanggang_kecamatan', 'name' => 'Ruangan Gelanggang Olahraga Masyarakat di Kecamatan'],
            ['code' => 'pemanfaatan_non_olahraga', 'name' => 'Pemanfaatan Kegiatan Non Olahraga'],
            ['code' => 'ruang_prasarana_kecamatan', 'name' => 'Ruangan Prasarana Publik & Olahraga di Kecamatan'],
            ['code' => 'kegiatan_non_olahraga_kecamatan', 'name' => 'Kegiatan Non Olahraga Prasarana Kecamatan'],
        ];

        foreach ($areas as $i => $area) {
            $this->upsertArea($venue->id, $area['code'], $area['name'], false, $i + 1);
        }
    }

    private function venue(string $code): BookingVenue
    {
        return BookingVenue::query()->where('code', $code)->firstOrFail();
    }

    private function upsertArea(int $venueId, string $code, string $name, bool $tentative, int $sort): void
    {
        BookingArea::query()->updateOrCreate(
            ['venue_id' => $venueId, 'code' => $code],
            [
                'name' => $name,
                'is_tentative' => $tentative,
                'is_active' => true,
                'sort_order' => $sort,
            ]
        );
    }
}
