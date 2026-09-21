<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingRule;

/**
 * Baca policy per venue / global dari booking_rules.
 * Implementasi penuh di Step 3–5.
 */
class RulesEngine
{
    public function get(string $key, ?int $venueId = null, mixed $default = null): mixed
    {
        if ($venueId !== null) {
            $venueRule = BookingRule::query()
                ->where('venue_id', $venueId)
                ->where('key', $key)
                ->where('is_active', true)
                ->first();

            if ($venueRule) {
                return $venueRule->value ?? $default;
            }
        }

        $global = BookingRule::query()
            ->whereNull('venue_id')
            ->where('key', $key)
            ->where('is_active', true)
            ->first();

        return $global?->value ?? $default;
    }
}
