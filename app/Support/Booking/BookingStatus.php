<?php

namespace App\Support\Booking;

final class BookingStatus
{
    public const DRAFT = 'draft';
    public const MENUNGGU_APPROVAL = 'menunggu_approval';
    public const PERLU_KLARIFIKASI = 'perlu_klarifikasi';
    public const APPROVED = 'approved';
    public const AWAITING_PAYMENT = 'awaiting_payment';
    public const PAID = 'paid';
    public const CONFIRMED = 'confirmed';
    public const COMPLETED = 'completed';
    public const REJECTED = 'rejected';
    public const CANCELLED = 'cancelled';
    public const FORFEITED = 'forfeited';
    public const EXPIRED = 'expired';
    public const RESCHEDULE_PENDING = 'reschedule_pending';
    public const NO_COMPENSATION = 'no_compensation';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::DRAFT,
            self::MENUNGGU_APPROVAL,
            self::PERLU_KLARIFIKASI,
            self::APPROVED,
            self::AWAITING_PAYMENT,
            self::PAID,
            self::CONFIRMED,
            self::COMPLETED,
            self::REJECTED,
            self::CANCELLED,
            self::FORFEITED,
            self::EXPIRED,
            self::RESCHEDULE_PENDING,
            self::NO_COMPENSATION,
        ];
    }

    /** Soft lock — kuning. */
    /** @return list<string> */
    public static function softLock(): array
    {
        return [self::MENUNGGU_APPROVAL, self::PERLU_KLARIFIKASI];
    }

    /** Hold — kuning (menunggu bayar). */
    /** @return list<string> */
    public static function holdLock(): array
    {
        return [self::APPROVED, self::AWAITING_PAYMENT];
    }

    /** Hard lock — merah. */
    /** @return list<string> */
    public static function hardLock(): array
    {
        return [self::PAID, self::CONFIRMED, self::RESCHEDULE_PENDING];
    }

    /** @return list<string> */
    public static function locking(): array
    {
        return array_values(array_unique(array_merge(
            self::softLock(),
            self::holdLock(),
            self::hardLock(),
        )));
    }
}
