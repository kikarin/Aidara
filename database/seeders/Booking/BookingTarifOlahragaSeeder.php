<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Support\Booking\BookingSatuan;
use Illuminate\Database\Seeder;

class BookingTarifOlahragaSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPakansari();
        $this->seedLagaTangkas();
        $this->seedLagaSatria();
        $this->seedTennis();
        $this->seedRoadRace();
        $this->seedAquatic();
    }

    private function seedPakansari(): void
    {
        $v = $this->venueId('pakansari');

        $this->tarif($v, 'lapangan_utama', 'pakansari_lu_intl', 'Pertandingan Resmi – Internasional', BookingSatuan::PER_MATCH, 80850000, 115500000, [
            'event_level' => 'internasional',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lapangan_utama', 'pakansari_lu_nas', 'Pertandingan Resmi – Nasional', BookingSatuan::PER_MATCH, 56560000, 80800000, [
            'event_level' => 'nasional',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lapangan_utama', 'pakansari_lu_prov', 'Pertandingan Resmi – Provinsi/Kabupaten', BookingSatuan::PER_MATCH, 40390000, 57700000, [
            'event_level' => 'provinsi_kabupaten',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lapangan_utama', 'pakansari_lu_non_liga', 'Pertandingan Non Liga', BookingSatuan::PER_HOUR, 1774000, 2534000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lapangan_utama', 'pakansari_lu_latihan', 'Latihan', BookingSatuan::PER_HOUR, 1100000, 1540000, [
            'category' => 'olahraga',
        ]);

        $this->tarif($v, 'lintasan_atletik', 'pakansari_la_intl', 'Pertandingan Resmi – Internasional', BookingSatuan::PER_DAY, 10738000, 15340000, [
            'event_level' => 'internasional',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lintasan_atletik', 'pakansari_la_nas', 'Pertandingan Resmi – Nasional', BookingSatuan::PER_DAY, 7462000, 10660000, [
            'event_level' => 'nasional',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lintasan_atletik', 'pakansari_la_prov', 'Pertandingan Resmi – Provinsi/Kabupaten', BookingSatuan::PER_DAY, 5369000, 7670000, [
            'event_level' => 'provinsi_kabupaten',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lintasan_atletik', 'pakansari_la_latihan', 'Latihan', BookingSatuan::PER_HOUR, 80000, 114000, [
            'category' => 'olahraga',
        ]);

        $this->tarif($v, 'lapangan_luar_a', 'pakansari_lla_event', 'Event/Pertandingan', BookingSatuan::PER_DAY, 3430000, 4900000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lapangan_luar_a', 'pakansari_lla_latihan', 'Latihan', BookingSatuan::PER_HOUR, 247000, 353000, [
            'category' => 'olahraga',
        ]);

        $this->tarif($v, 'lintasan_atletik_luar', 'pakansari_lal_resmi', 'Pertandingan Resmi', BookingSatuan::PER_DAY, 4130000, 5900000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'lintasan_atletik_luar', 'pakansari_lal_latihan', 'Latihan', BookingSatuan::PER_HOUR, 60000, 87000, [
            'category' => 'olahraga',
        ]);

        $this->tarif($v, 'lapangan_luar_b', 'pakansari_llb_event', 'Kegiatan Event Olahraga', BookingSatuan::PER_DAY, 1482000, 2118000, [
            'category' => 'olahraga',
        ]);

        $this->tarif($v, 'panjat_tebing', 'pakansari_pt', 'Panjat Tebing', BookingSatuan::PER_HOUR, 150000, 215000, [
            'category' => 'olahraga',
        ]);
    }

    private function seedLagaTangkas(): void
    {
        $v = $this->venueId('laga_tangkas');

        $this->tarif($v, 'utama', 'lt_pertandingan', 'Pertandingan (Kompetisi, Turnamen)', BookingSatuan::PER_HOUR, 600000, 1000000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'lt_beregu_siang', 'Latihan beregu (Futsal, Basket, Voli, dll.) – Siang', BookingSatuan::PER_HOUR, 70000, 100000, [
            'time_slot' => 'siang',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'lt_beregu_malam', 'Latihan beregu (Futsal, Basket, Voli, dll.) – Malam', BookingSatuan::PER_HOUR, 80000, 120000, [
            'time_slot' => 'malam',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'lt_bulutangkis_siang', 'Latihan Bulu Tangkis – Siang', BookingSatuan::PER_COURT_HOUR, 40000, 60000, [
            'time_slot' => 'siang',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'lt_bulutangkis_malam', 'Latihan Bulu Tangkis – Malam', BookingSatuan::PER_COURT_HOUR, 50000, 70000, [
            'time_slot' => 'malam',
            'category' => 'olahraga',
        ]);
    }

    private function seedLagaSatria(): void
    {
        $v = $this->venueId('laga_satria');

        $this->tarif($v, 'utama', 'ls_pertandingan', 'Pertandingan (Kompetisi, Turnamen)', BookingSatuan::PER_HOUR, 400000, 600000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'ls_latihan_siang', 'Latihan – Siang', BookingSatuan::PER_HOUR, 30000, 40000, [
            'time_slot' => 'siang',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'utama', 'ls_latihan_malam', 'Latihan – Malam', BookingSatuan::PER_HOUR, 40000, 50000, [
            'time_slot' => 'malam',
            'category' => 'olahraga',
        ]);
    }

    private function seedTennis(): void
    {
        $v = $this->venueId('tennis_kapten_muslihat');

        $this->tarif($v, null, 'tennis_pertandingan', 'Pertandingan (Kompetisi/Turnamen)', BookingSatuan::PER_HOUR, 1000000, 1300000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'indoor', 'tennis_indoor_siang', 'Latihan Lapangan Indoor – Siang', BookingSatuan::PER_HOUR, 100000, 130000, [
            'time_slot' => 'siang',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'indoor', 'tennis_indoor_malam', 'Latihan Lapangan Indoor – Malam', BookingSatuan::PER_HOUR, 120000, 150000, [
            'time_slot' => 'malam',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'outdoor', 'tennis_outdoor_siang', 'Latihan Lapangan Outdoor – Siang', BookingSatuan::PER_HOUR, 60000, 80000, [
            'time_slot' => 'siang',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'outdoor', 'tennis_outdoor_malam', 'Latihan Lapangan Outdoor – Malam', BookingSatuan::PER_HOUR, 70000, 100000, [
            'time_slot' => 'malam',
            'category' => 'olahraga',
        ]);
    }

    private function seedRoadRace(): void
    {
        $v = $this->venueId('sirkuit_road_race');

        $this->tarif($v, 'sirkuit', 'srr_kompetisi_roda2', 'Pertandingan (Kompetisi/Turnamen) – Roda 2', BookingSatuan::PER_DAY, 10000000, 15000000, [
            'vehicle_class' => 'roda_2',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'sirkuit', 'srr_kompetisi_gokart_mobil', 'Pertandingan (Kompetisi/Turnamen) – Gokart, Mobil', BookingSatuan::PER_DAY, 15000000, 20000000, [
            'vehicle_class' => 'gokart_mobil',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'sirkuit', 'srr_latihan_roda2_lt250', 'Latihan Road Race – Roda 2, di bawah 250cc', BookingSatuan::PER_UNIT_3HOUR, 50000, 100000, [
            'vehicle_class' => 'roda_2_lt_250cc',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'sirkuit', 'srr_latihan_roda2_gt250', 'Latihan Road Race – Roda 2, di atas 250cc', BookingSatuan::PER_UNIT_3HOUR, 70000, 150000, [
            'vehicle_class' => 'roda_2_gt_250cc',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'sirkuit', 'srr_latihan_gokart_mobil', 'Latihan Road Race – Gokart, Mobil', BookingSatuan::PER_UNIT_3HOUR, 150000, 300000, [
            'vehicle_class' => 'gokart_mobil',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'paddock', 'srr_paddock', 'Sewa Paddock', BookingSatuan::PER_DAY, 150000, 200000, [
            'category' => 'olahraga',
        ]);
    }

    private function seedAquatic(): void
    {
        $v = $this->venueId('aquatic');

        $this->tarif($v, 'kolam', 'aquatic_pertandingan', 'Pertandingan (Kompetisi/Turnamen), 06.00–18.00', BookingSatuan::PER_DAY, 5000000, 10000000, [
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'kolam', 'aquatic_dewasa_weekday', 'Latihan/Rekreasi – Dewasa, Senin–Jumat', BookingSatuan::PER_PERSON, 10000, 20000, [
            'audience_type' => 'dewasa',
            'day_type' => 'weekday',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'kolam', 'aquatic_anak_weekday', 'Latihan/Rekreasi – Anak-anak, Senin–Jumat', BookingSatuan::PER_PERSON, 5000, 10000, [
            'audience_type' => 'anak',
            'day_type' => 'weekday',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'kolam', 'aquatic_dewasa_weekend', 'Latihan/Rekreasi – Dewasa, Sabtu–Minggu/Libur Nasional', BookingSatuan::PER_PERSON, 15000, 25000, [
            'audience_type' => 'dewasa',
            'day_type' => 'weekend_holiday',
            'category' => 'olahraga',
        ]);
        $this->tarif($v, 'kolam', 'aquatic_anak_weekend', 'Latihan/Rekreasi – Anak-anak, Sabtu–Minggu/Libur Nasional', BookingSatuan::PER_PERSON, 10000, 15000, [
            'audience_type' => 'anak',
            'day_type' => 'weekend_holiday',
            'category' => 'olahraga',
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
                'category' => $extra['category'] ?? 'olahraga',
                'is_active' => true,
            ]
        );
    }
}
