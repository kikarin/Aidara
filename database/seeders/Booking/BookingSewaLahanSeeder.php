<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Support\Booking\BookingSatuan;
use Illuminate\Database\Seeder;

class BookingSewaLahanSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPakansari();
        $this->seedLaga();
        $this->seedTennis();
        $this->seedRoadRace();
        $this->seedAquatic();
        $this->seedSaranaLainnya();
    }

    private function seedPakansari(): void
    {
        $v = $this->venueId('pakansari');

        $parkirAreas = [
            'parkir_blok_a', 'parkir_blok_b', 'parkir_blok_c', 'parkir_blok_d_e',
            'parkir_blok_f', 'parkir_blok_g', 'parkir_blok_h', 'parkir_blok_i', 'parkir_blok_j',
            'plaza_utara', 'plaza_selatan',
        ];

        foreach ($parkirAreas as $areaCode) {
            $this->tarif($v, $areaCode, "pakansari_sewa_{$areaCode}", 'Sewa lahan parkir/plaza (M²/Hari)', BookingSatuan::PER_M2_DAY, 2500, 5000, [
                'category' => 'sewa_lahan',
            ]);
        }

        $this->tarif($v, 'lapangan_non_olahraga', 'pakansari_non_lapangan', 'Kegiatan Non Olahraga – Lapangan Stadion Pakansari', BookingSatuan::PER_ACTIVITY_DAY, 67900000, 97000000, [
            'category' => 'non_olahraga',
        ]);
        $this->tarif($v, 'tribun', 'pakansari_non_tribun', 'Kegiatan Non Olahraga – Tribun Stadion', BookingSatuan::PER_ACTIVITY_DAY, 25060000, 35800000, [
            'category' => 'non_olahraga',
        ]);
        $this->tarif($v, 'lahan_ring_1', 'pakansari_ring1', 'Lahan Ring 1', BookingSatuan::PER_M2_DAY, 2000, 3000, [
            'category' => 'sewa_lahan',
        ]);
        $this->tarif($v, 'lahan_ring_2', 'pakansari_ring2', 'Lahan Ring 2', BookingSatuan::PER_M2_DAY, 1820, 2600, [
            'category' => 'sewa_lahan',
        ]);
        $this->tarif($v, 'lapangan_luar_b', 'pakansari_non_llb', 'Kegiatan Non Olahraga – Lapangan Luar B', BookingSatuan::PER_DAY, 28802000, 41147000, [
            'category' => 'non_olahraga',
        ]);
        $this->tarif($v, 'ruang_kantor', 'pakansari_ruang_kantor', 'Ruang Kantor', BookingSatuan::PER_M2_MONTH, 55500, 111000, [
            'category' => 'ruang',
        ]);
    }

    private function seedLaga(): void
    {
        $tangkas = $this->venueId('laga_tangkas');
        $satria = $this->venueId('laga_satria');

        $this->tarif($tangkas, 'utama', 'lt_non_olahraga', 'Kegiatan Non Olahraga – Laga Tangkas', BookingSatuan::PER_HOUR, 1000000, 1500000, [
            'category' => 'non_olahraga',
        ]);
        $this->tarif($satria, 'utama', 'ls_non_olahraga', 'Kegiatan Non Olahraga – Laga Satria', BookingSatuan::PER_HOUR, 500000, 1000000, [
            'category' => 'non_olahraga',
        ]);

        foreach ([$tangkas, $satria] as $v) {
            $prefix = $v === $tangkas ? 'lt' : 'ls';
            $this->tarif($v, 'parkir', "{$prefix}_parkir_m2", 'Area Lahan Parkir', BookingSatuan::PER_M2_DAY, 2500, 5000, [
                'category' => 'sewa_lahan',
            ]);
            $this->tarif($v, 'ruang_kantor', "{$prefix}_ruang_kantor", 'Ruang Kantor', BookingSatuan::PER_M2_MONTH, 27778, 72222, [
                'category' => 'ruang',
            ]);
        }
    }

    private function seedTennis(): void
    {
        $v = $this->venueId('tennis_kapten_muslihat');
        $this->tarif($v, null, 'tennis_non_olahraga', 'Kegiatan Non Olahraga', BookingSatuan::PER_HOUR, 1500000, 2000000, [
            'category' => 'non_olahraga',
        ]);
    }

    private function seedRoadRace(): void
    {
        $v = $this->venueId('sirkuit_road_race');
        $this->tarif($v, 'sirkuit', 'srr_non_olahraga', 'Kegiatan Non Olahraga', BookingSatuan::PER_DAY, 15000000, 20000000, [
            'category' => 'non_olahraga',
        ]);
    }

    private function seedAquatic(): void
    {
        $v = $this->venueId('aquatic');
        $this->tarif($v, 'kolam', 'aquatic_non_olahraga', 'Kegiatan Non Olahraga', BookingSatuan::PER_DAY, 6000000, 12000000, [
            'category' => 'non_olahraga',
        ]);
    }

    private function seedSaranaLainnya(): void
    {
        $v = $this->venueId('sarana_lainnya');

        $this->tarif($v, 'ruang_stadion_mini_cibinong', 'sl_ruang_cibinong', 'Ruangan sekitar Stadion Mini Cibinong (Persikabo)', BookingSatuan::PER_M2_MONTH, 23000, 46000, [
            'category' => 'ruang',
        ]);
        $this->tarif($v, 'ruang_gelanggang_kecamatan', 'sl_ruang_gelanggang', 'Ruangan Gelanggang Olahraga Masyarakat di Kecamatan', BookingSatuan::PER_M2_MONTH, 20000, 36000, [
            'category' => 'ruang',
        ]);
        $this->tarif($v, 'pemanfaatan_non_olahraga', 'sl_non_siang', 'Pemanfaatan Kegiatan Non Olahraga – Siang', BookingSatuan::PER_HOUR, null, 300000, [
            'time_slot' => 'siang',
            'category' => 'non_olahraga',
        ]);
        $this->tarif($v, 'pemanfaatan_non_olahraga', 'sl_non_malam', 'Pemanfaatan Kegiatan Non Olahraga – Malam', BookingSatuan::PER_HOUR, null, 400000, [
            'time_slot' => 'malam',
            'category' => 'non_olahraga',
        ]);
        $this->tarif($v, 'ruang_prasarana_kecamatan', 'sl_ruang_prasarana', 'Ruangan Prasarana Publik & Olahraga di Kecamatan', BookingSatuan::PER_M2_MONTH, 20000, 36000, [
            'category' => 'ruang',
        ]);
        $this->tarif($v, 'kegiatan_non_olahraga_kecamatan', 'sl_non_kecamatan_hari', 'Kegiatan Non Olahraga pada Ruangan Prasarana Kecamatan', BookingSatuan::PER_DAY, null, 3500000, [
            'category' => 'non_olahraga',
        ]);
    }

    private function venueId(string $code): int
    {
        return (int) BookingVenue::query()->where('code', $code)->value('id');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function tarif(
        int $venueId,
        ?string $areaCode,
        string $code,
        string $uraian,
        string $satuan,
        ?int $pemerintah,
        ?int $nonPemerintah,
        array $extra = []
    ): void {
        $areaId = null;
        if ($areaCode !== null) {
            $areaId = BookingArea::query()
                ->where('venue_id', $venueId)
                ->where('code', $areaCode)
                ->value('id');
        }

        BookingTarif::query()->updateOrCreate(
            ['code' => $code],
            [
                'venue_id' => $venueId,
                'area_id' => $areaId,
                'uraian' => $uraian,
                'satuan' => $satuan,
                'tarif_pemerintah' => $pemerintah,
                'tarif_non_pemerintah' => $nonPemerintah,
                'time_slot' => $extra['time_slot'] ?? null,
                'audience_type' => $extra['audience_type'] ?? null,
                'day_type' => $extra['day_type'] ?? null,
                'vehicle_class' => $extra['vehicle_class'] ?? null,
                'event_level' => $extra['event_level'] ?? null,
                'category' => $extra['category'] ?? 'sewa_lahan',
                'is_active' => true,
            ]
        );
    }
}
