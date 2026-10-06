<?php

namespace App\Support\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;

/**
 * Membedakan sewa reguler (latihan/rekreasi per jam) dari sewa event (pertandingan, kegiatan, per hari/M²).
 * Sewa reguler cukup disetujui lalu dibayar; sewa event melewati meeting dan surat balasan.
 */
final class BookingJenisSewa
{
    public const REGULER = 'reguler';

    public const EVENT = 'event';

    private const SATUAN_REGULER = [
        BookingSatuan::PER_HOUR,
        BookingSatuan::PER_COURT_HOUR,
        BookingSatuan::PER_UNIT_3HOUR,
        BookingSatuan::PER_PERSON,
    ];

    private const POLA_REGULER = '/latihan|rekreasi/i';

    private const POLA_EVENT = '/pertandingan|kompetisi|turnamen|event|kegiatan|non olahraga|komersil/i';

    public static function of(Booking $booking): string
    {
        $items = $booking->relationLoaded('items') ? $booking->items : $booking->items()->get();

        if ($items->isEmpty()) {
            return self::EVENT;
        }

        return $items->every(fn (BookingItem $item) => self::isRegulerItem($item)) ? self::REGULER : self::EVENT;
    }

    public static function isReguler(Booking $booking): bool
    {
        return self::of($booking) === self::REGULER;
    }

    private static function isRegulerItem(BookingItem $item): bool
    {
        $snapshot = is_array($item->snapshot) ? $item->snapshot : [];

        if (! in_array($item->satuan, self::SATUAN_REGULER, true)) {
            return false;
        }

        if (($snapshot['category'] ?? 'olahraga') !== 'olahraga' || ! empty($snapshot['event_level'])) {
            return false;
        }

        $uraian = (string) $item->uraian;

        if (preg_match(self::POLA_REGULER, $uraian)) {
            return true;
        }

        return ! preg_match(self::POLA_EVENT, $uraian);
    }
}
