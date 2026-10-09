<?php

namespace App\Support\Booking;

final class BookingSatuan
{
    public const PER_MATCH = 'per_match';
    public const PER_DAY = 'per_day';
    public const PER_HOUR = 'per_hour';
    public const PER_COURT_HOUR = 'per_court_hour';
    public const PER_UNIT_3HOUR = 'per_unit_3hour';
    public const PER_PERSON = 'per_person';
    public const PER_M2_DAY = 'per_m2_day';
    public const PER_M2_MONTH = 'per_m2_month';
    public const PER_ACTIVITY_DAY = 'per_activity_day';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::PER_MATCH,
            self::PER_DAY,
            self::PER_HOUR,
            self::PER_COURT_HOUR,
            self::PER_UNIT_3HOUR,
            self::PER_PERSON,
            self::PER_M2_DAY,
            self::PER_M2_MONTH,
            self::PER_ACTIVITY_DAY,
        ];
    }
}
