<?php

namespace App\Support\Booking;

final class BookingStatus
{
    public const DRAFT = 'draft';

    public const MENUNGGU_APPROVAL = 'menunggu_approval';

    public const PERLU_KLARIFIKASI = 'perlu_klarifikasi';

    /** Surat balasan lanjut dikirim; menunggu pelaksanaan/keputusan setelah meeting. */
    public const MENUNGGU_MEETING = 'menunggu_meeting';

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
            self::MENUNGGU_MEETING,
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

    /** Label status untuk tampilan admin (Bahasa Indonesia). */
    public static function adminLabel(string $status): string
    {
        return match ($status) {
            self::DRAFT              => 'Draf',
            self::MENUNGGU_APPROVAL  => 'Perlu ditinjau',
            self::PERLU_KLARIFIKASI  => 'Perlu klarifikasi',
            self::MENUNGGU_MEETING   => 'Menunggu keputusan akhir',
            self::APPROVED           => 'Disetujui',
            self::AWAITING_PAYMENT   => 'Menunggu bayar',
            self::PAID               => 'Sudah bayar',
            self::CONFIRMED          => 'Dikonfirmasi',
            self::COMPLETED          => 'Selesai',
            self::REJECTED           => 'Ditolak',
            self::CANCELLED          => 'Dibatalkan',
            self::FORFEITED          => 'Hangus',
            self::EXPIRED            => 'Kedaluwarsa',
            self::RESCHEDULE_PENDING => 'Menunggu jadwal ulang',
            self::NO_COMPENSATION    => 'Tanpa kompensasi',
            default                  => $status,
        };
    }

    /**
     * Pengajuan (menunggu_approval / perlu_klarifikasi / menunggu_meeting) — NON-BLOCKING.
     * Tidak masuk locking(): kalender tetap hijau, hanya diberi flag "ada pengajuan".
     * Admin yang memutuskan pengajuan mana yang diloloskan.
     *
     * @return list<string>
     */
    public static function pengajuan(): array
    {
        return [self::MENUNGGU_APPROVAL, self::PERLU_KLARIFIKASI, self::MENUNGGU_MEETING];
    }

    /** Hold — tanggal terblokir (merah) selama window pembayaran. */
    /** @return list<string> */
    public static function holdLock(): array
    {
        return [self::APPROVED, self::AWAITING_PAYMENT];
    }

    /** Hard lock — merah. */
    /** @return list<string> */
    public static function hardLock(): array
    {
        return [self::APPROVED, self::AWAITING_PAYMENT, self::PAID, self::CONFIRMED, self::RESCHEDULE_PENDING];
    }

    /**
     * Semua status yang MEMBLOKIR tanggal (merah).
     * Pengajuan tidak termasuk — hanya menambah flag informasi.
     *
     * @return list<string>
     */
    public static function locking(): array
    {
        return array_values(array_unique(array_merge(
            self::holdLock(),
            self::hardLock(),
        )));
    }
}
