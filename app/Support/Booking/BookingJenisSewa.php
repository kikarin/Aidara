<?php

namespace App\Support\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingItem;

/**
 * Membedakan sewa per jam (reguler) dari sewa per hari (event), murni berdasarkan
 * satuan tarif item.
 *
 * - Sewa per jam  → langsung booking: tanpa surat permohonan, tanpa meeting.
 * - Sewa per hari → wajib surat permohonan, lalu meeting dan surat balasan.
 *
 * Campuran (ada minimal satu item per hari) diperlakukan sebagai per hari karena
 * aturannya lebih ketat.
 */
final class BookingJenisSewa
{
    /** Sewa per jam — langsung booking. */
    public const REGULER = 'reguler';

    /** Sewa per hari — wajib surat permohonan + meeting. */
    public const EVENT = 'event';

    /** Satuan yang dihitung sebagai sewa per jam. */
    private const SATUAN_PER_JAM = [
        BookingSatuan::PER_HOUR,
        BookingSatuan::PER_COURT_HOUR,
        BookingSatuan::PER_UNIT_3HOUR,
        BookingSatuan::PER_MATCH,
        BookingSatuan::PER_PERSON,
    ];

    public static function of(Booking $booking): string
    {
        return self::isPerHari($booking) ? self::EVENT : self::REGULER;
    }

    public static function isReguler(Booking $booking): bool
    {
        return self::of($booking) === self::REGULER;
    }

    /**
     * True bila pengajuan dianggap sewa per hari: ada item bersatuan per hari,
     * satuan tak dikenal, atau items kosong (default aman = per hari).
     */
    public static function isPerHari(Booking $booking): bool
    {
        $items = $booking->relationLoaded('items') ? $booking->items : $booking->items()->get();

        if ($items->isEmpty()) {
            return true;
        }

        return $items->contains(
            fn (BookingItem $item) => ! in_array($item->satuan, self::SATUAN_PER_JAM, true)
        );
    }

    /** True bila sekumpulan satuan (mis. dari input form) termasuk per hari. */
    public static function satuanPerHari(array $satuans): bool
    {
        if ($satuans === []) {
            return true;
        }

        foreach ($satuans as $satuan) {
            if (! in_array($satuan, self::SATUAN_PER_JAM, true)) {
                return true;
            }
        }

        return false;
    }
}
