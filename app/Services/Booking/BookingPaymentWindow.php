<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingSetting;
use App\Support\Booking\BookingJenisSewa;
use Carbon\Carbon;

/**
 * Menentukan kapan pembayaran dibuka dan kapan tenggatnya, dibedakan per
 * kategori sewa: event (per hari) dan latihan (per jam).
 *
 * - after_approval: pembayaran langsung dibuka begitu booking disetujui /
 *   masuk menunggu pembayaran. Tenggat = dibuka + payment_expire_hours.
 * - before_event: pembayaran baru dibuka H-N (awal hari) sebelum kegiatan.
 *   Tenggat = dibuka + payment_expire_hours, tidak melewati jam mulai kegiatan.
 */
class BookingPaymentWindow
{
    public const SETTING_KEY = 'payment_open_rules';

    public const MODE_AFTER_APPROVAL = 'after_approval';

    public const MODE_BEFORE_EVENT = 'before_event';

    public const MODES = [self::MODE_AFTER_APPROVAL, self::MODE_BEFORE_EVENT];

    public const PLACEHOLDERS = [
        '{nomor}' => 'Nomor booking',
        '{jenis}' => 'Kategori (Event / Latihan)',
        '{h}' => 'Jumlah hari H-',
        '{tanggal_kegiatan}' => 'Tanggal & jam mulai kegiatan',
        '{tanggal_buka}' => 'Tanggal pembayaran dibuka',
        '{batas_bayar}' => 'Batas akhir pembayaran',
        '{jam_bayar}' => 'Lama waktu bayar (jam)',
        '{kontak}' => 'Kontak klarifikasi',
    ];

    /**
     * @return array<string, array{mode: string, days_before: int, message_waiting: string, message_open: string}>
     */
    public static function defaults(): array
    {
        $messageOpen = 'Pembayaran {jenis} untuk booking {nomor} sudah dibuka. Silakan transfer paling lambat {batas_bayar}, lalu unggah bukti pembayaran.';
        $messageWaiting = 'Jadwal {jenis} Anda sudah kami tahan. Pembayaran baru dibuka H-{h} sebelum kegiatan, yaitu mulai {tanggal_buka}, dengan batas bayar {batas_bayar}.';

        return [
            BookingJenisSewa::EVENT => [
                'mode' => self::MODE_AFTER_APPROVAL,
                'days_before' => 5,
                'message_waiting' => $messageWaiting,
                'message_open' => $messageOpen,
            ],
            BookingJenisSewa::REGULER => [
                'mode' => self::MODE_AFTER_APPROVAL,
                'days_before' => 5,
                'message_waiting' => $messageWaiting,
                'message_open' => $messageOpen,
            ],
        ];
    }

    public static function jenisLabel(string $jenis): string
    {
        return $jenis === BookingJenisSewa::REGULER ? 'Latihan' : 'Event';
    }

    /**
     * @return array<string, array{mode: string, days_before: int, message_waiting: string, message_open: string}>
     */
    public function rules(): array
    {
        $stored = BookingSetting::getValue(self::SETTING_KEY, []);
        $stored = is_array($stored) ? $stored : [];

        $rules = [];
        foreach (self::defaults() as $jenis => $default) {
            $row = is_array($stored[$jenis] ?? null) ? $stored[$jenis] : [];
            $mode = in_array($row['mode'] ?? null, self::MODES, true) ? $row['mode'] : $default['mode'];

            $rules[$jenis] = [
                'mode' => $mode,
                'days_before' => max(1, (int) ($row['days_before'] ?? $default['days_before'])),
                'message_waiting' => trim((string) ($row['message_waiting'] ?? '')) ?: $default['message_waiting'],
                'message_open' => trim((string) ($row['message_open'] ?? '')) ?: $default['message_open'],
            ];
        }

        return $rules;
    }

    /**
     * @return array{mode: string, days_before: int, message_waiting: string, message_open: string}
     */
    public function ruleFor(Booking $booking): array
    {
        return $this->rules()[BookingJenisSewa::of($booking)];
    }

    public function expireHours(): int
    {
        $hours = (int) (BookingSetting::getValue('payment_expire_hours', 72) ?? 72);

        return $hours < 1 ? 48 : $hours;
    }

    /**
     * Meta yang disimpan pada payment baru.
     *
     * @return array{open_mode: string, open_days_before: int, opens_at: string, expires_at: string, expire_hours: int}
     */
    public function metaForNewPayment(Booking $booking, ?Carbon $now = null): array
    {
        $now = $now ?? now();
        $rule = $this->ruleFor($booking);
        $hours = $this->expireHours();

        $opensAt = $this->computeOpensAt($booking, $rule['mode'], $rule['days_before'], $now);
        $expiresAt = $this->computeExpiresAt($booking, $rule['mode'], $opensAt, $hours);

        return [
            'open_mode' => $rule['mode'],
            'open_days_before' => $rule['days_before'],
            'opens_at' => $opensAt->toDateTimeString(),
            'expires_at' => $expiresAt->toDateTimeString(),
            'expire_hours' => $hours,
        ];
    }

    public function opensAt(Booking $booking, BookingPayment $payment): Carbon
    {
        $meta = $payment->meta ?? [];
        $createdAt = Carbon::parse($payment->created_at ?? now());

        if (($meta['open_mode'] ?? null) !== self::MODE_BEFORE_EVENT) {
            return $createdAt;
        }

        // Dihitung ulang dari starts_at supaya tetap benar setelah reschedule.
        return $this->computeOpensAt($booking, self::MODE_BEFORE_EVENT, (int) ($meta['open_days_before'] ?? 1), $createdAt);
    }

    public function expiresAt(Booking $booking, BookingPayment $payment): Carbon
    {
        $meta = $payment->meta ?? [];
        $hours = max(1, (int) ($meta['expire_hours'] ?? $this->expireHours()));

        if (($meta['open_mode'] ?? null) === self::MODE_BEFORE_EVENT) {
            return $this->computeExpiresAt($booking, self::MODE_BEFORE_EVENT, $this->opensAt($booking, $payment), $hours);
        }

        if (! empty($meta['expires_at'])) {
            return Carbon::parse($meta['expires_at']);
        }

        return Carbon::parse($payment->created_at)->addHours($hours);
    }

    public function isOpen(Booking $booking, BookingPayment $payment, ?Carbon $now = null): bool
    {
        return ($now ?? now())->gte($this->opensAt($booking, $payment));
    }

    /**
     * Pesan dinamis untuk penyewa sesuai kondisi pembayaran saat ini.
     */
    public function message(Booking $booking, BookingPayment $payment, ?Carbon $now = null): string
    {
        $jenis = BookingJenisSewa::of($booking);
        $rule = $this->rules()[$jenis];
        $template = $this->isOpen($booking, $payment, $now) ? $rule['message_open'] : $rule['message_waiting'];
        $meta = $payment->meta ?? [];

        return $this->render($template, [
            '{nomor}' => (string) $booking->nomor,
            '{jenis}' => self::jenisLabel($jenis),
            '{h}' => (string) ($meta['open_days_before'] ?? $rule['days_before']),
            '{tanggal_kegiatan}' => $booking->starts_at ? $this->formatDate(Carbon::parse($booking->starts_at)) : '-',
            '{tanggal_buka}' => $this->formatDate($this->opensAt($booking, $payment)),
            '{batas_bayar}' => $this->formatDate($this->expiresAt($booking, $payment)),
            '{jam_bayar}' => (string) ($meta['expire_hours'] ?? $this->expireHours()),
            '{kontak}' => (string) (BookingSetting::getValue('kontak_klarifikasi', '-') ?? '-'),
        ]);
    }

    /**
     * Data pembayaran yang dikirim ke halaman penyewa/admin.
     *
     * @return array{opens_at: string, expires_at: string, is_open: bool, open_mode: string, message: string}
     */
    public function payload(Booking $booking, BookingPayment $payment): array
    {
        return [
            'opens_at' => $this->opensAt($booking, $payment)->toDateTimeString(),
            'expires_at' => $this->expiresAt($booking, $payment)->toDateTimeString(),
            'is_open' => $this->isOpen($booking, $payment),
            'open_mode' => ($payment->meta['open_mode'] ?? null) ?: self::MODE_AFTER_APPROVAL,
            'message' => $this->message($booking, $payment),
        ];
    }

    /**
     * Variabel untuk template email booking.
     *
     * @return array{expiresAt: ?string, opensAt: ?string, paymentOpen: bool, paymentMessage: ?string}
     */
    public static function mailData(Booking $booking, ?BookingPayment $payment): array
    {
        if (! $payment) {
            return ['expiresAt' => null, 'opensAt' => null, 'paymentOpen' => true, 'paymentMessage' => null];
        }

        $data = app(self::class)->payload($booking, $payment);

        return [
            'expiresAt' => $data['expires_at'],
            'opensAt' => $data['opens_at'],
            'paymentOpen' => $data['is_open'],
            'paymentMessage' => $data['message'],
        ];
    }

    /**
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        return strtr($template, $values);
    }

    private function computeOpensAt(Booking $booking, string $mode, int $daysBefore, Carbon $notBefore): Carbon
    {
        if ($mode !== self::MODE_BEFORE_EVENT || ! $booking->starts_at) {
            return $notBefore->copy();
        }

        $opensAt = Carbon::parse($booking->starts_at)->subDays(max(1, $daysBefore))->startOfDay();

        return $opensAt->lt($notBefore) ? $notBefore->copy() : $opensAt;
    }

    private function computeExpiresAt(Booking $booking, string $mode, Carbon $opensAt, int $hours): Carbon
    {
        $expiresAt = $opensAt->copy()->addHours($hours);

        if ($mode === self::MODE_BEFORE_EVENT && $booking->starts_at) {
            $startsAt = Carbon::parse($booking->starts_at);
            if ($expiresAt->gt($startsAt) && $startsAt->gt($opensAt)) {
                $expiresAt = $startsAt;
            }
        }

        return $expiresAt;
    }

    private function formatDate(Carbon $date): string
    {
        return $date->locale('id')->translatedFormat('l, d F Y H:i');
    }
}
