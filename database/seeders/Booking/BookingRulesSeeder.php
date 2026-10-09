<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingVenue;
use Illuminate\Database\Seeder;

class BookingRulesSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsertRule(null, 'buffer_before_days', 1, 'Default buffer hari sebelum booking (seluruh venue)');
        $this->upsertRule(null, 'buffer_after_days', 1, 'Default buffer hari setelah booking (seluruh venue)');
        $this->upsertRule(null, 'advance_payment_required', true, 'Pembayaran di muka disarankan secara global');
        $this->upsertRule(null, 'booking_horizon_days', null, 'Horizon booking global (null = tidak dibatasi; override per venue)');

        $tennisId = (int) BookingVenue::query()->where('code', 'tennis_kapten_muslihat')->value('id');

        $tennisRules = [
            'operating_days' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'],
            'operating_hours' => ['start' => '06:00', 'end' => '21:00', 'timezone' => 'Asia/Jakarta'],
            'holiday_override' => true,
            'advance_payment_required' => true,
            'booking_horizon_days' => 7,
            'prefer_booking_on_weekday' => 'monday',
            'cancel_deadline' => 'H-1',
            'cancel_on_day_h' => 'forfeited',
            'allow_same_day_reschedule' => false,
            'allow_same_day_court_change' => false,
            'early_arrival_minutes_min' => 15,
            'early_arrival_minutes_max' => 30,
            'leave_court_after_minutes' => 15,
            'adjacent_empty_court_counts_as_rental' => true,
            'force_majeure_rain_before_play' => 'reschedule',
            'force_majeure_rain_after_play_minutes' => 20,
            'force_majeure_rain_after_play_decision' => 'no_compensation',
            'tentative_areas' => ['indoor_a', 'outdoor_c'],
            'tentative_priority' => 'kegiatan_pemerintah_daerah',
            'terms_key' => 'terms_tennis',
        ];

        foreach ($tennisRules as $key => $value) {
            $this->upsertRule($tennisId, $key, $value, 'Rules Tennis Kapten Muslihat');
        }
    }

    private function upsertRule(?int $venueId, string $key, mixed $value, ?string $description = null): void
    {
        $query = BookingRule::query()->where('key', $key);
        if ($venueId === null) {
            $query->whereNull('venue_id');
        } else {
            $query->where('venue_id', $venueId);
        }

        $rule = $query->first();

        $payload = [
            'venue_id' => $venueId,
            'key' => $key,
            'value' => $value,
            'description' => $description,
            'is_active' => true,
        ];

        if ($rule) {
            $rule->update($payload);
        } else {
            BookingRule::query()->create($payload);
        }
    }
}
