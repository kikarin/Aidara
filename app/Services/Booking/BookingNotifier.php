<?php

namespace App\Services\Booking;

use App\Mail\Booking\BookingPaymentProofMail;
use App\Mail\Booking\BookingPaymentReceivedMail;
use App\Mail\Booking\BookingPaymentRejectedMail;
use App\Mail\Booking\BookingPaymentVerifiedMail;
use App\Mail\Booking\BookingSubmittedAdminMail;
use App\Mail\Booking\BookingSubmittedMail;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Titik terpusat pengiriman email terkait booking.
 * Semua pengiriman dibungkus try/catch agar kegagalan mail tidak mematahkan
 * transaksi bisnis (pola sama seperti notifikasi admin sebelumnya).
 */
class BookingNotifier
{
    public function notifySubmitted(Booking $booking): void
    {
        $email = $booking->user?->email;

        if (! $email) {
            return;
        }

        $this->safeSend(fn () => Mail::to($email)->send(
            new BookingSubmittedMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas', 'items']),
                $this->latestPayment($booking),
            )
        ));
    }

    public function notifySubmittedAdmins(Booking $booking): void
    {
        $recipients = $this->adminRecipients();

        if ($recipients === []) {
            return;
        }

        $this->safeSend(fn () => Mail::to($recipients)->send(
            new BookingSubmittedAdminMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas', 'items']),
            )
        ));
    }

    public function notifyBuktiAdmins(Booking $booking, BookingPayment $payment): void
    {
        $recipients = $this->adminRecipients();

        if ($recipients === []) {
            return;
        }

        $this->safeSend(fn () => Mail::to($recipients)->send(
            new BookingPaymentProofMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas']),
                $payment,
            )
        ));
    }

    public function notifyBuktiReceived(Booking $booking, BookingPayment $payment): void
    {
        $email = $booking->user?->email;

        if (! $email) {
            return;
        }

        $this->safeSend(fn () => Mail::to($email)->send(
            new BookingPaymentReceivedMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas']),
                $payment,
            )
        ));
    }

    public function notifyPaymentVerified(Booking $booking): void
    {
        $email = $booking->user?->email;

        if (! $email) {
            return;
        }

        $this->safeSend(fn () => Mail::to($email)->send(
            new BookingPaymentVerifiedMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas']),
                $this->latestPayment($booking),
            )
        ));
    }

    public function notifyPaymentRejected(Booking $booking, BookingPayment $payment, string $reason): void
    {
        $email = $booking->user?->email;

        if (! $email) {
            return;
        }

        $this->safeSend(fn () => Mail::to($email)->send(
            new BookingPaymentRejectedMail(
                $booking->loadMissing(['penyewaProfile', 'user', 'venue', 'areas']),
                $payment,
                $reason,
            )
        ));
    }

    /**
     * Email tujuan notifikasi admin: prioritas setting `email_notifikasi_upt`,
     * jika kosong fallback ke seluruh user aktif dengan role admin_upt.
     *
     * @return list<string>
     */
    public function adminRecipients(): array
    {
        $configured = BookingSetting::getValue('email_notifikasi_upt');

        if (is_string($configured) && filter_var($configured, FILTER_VALIDATE_EMAIL)) {
            return [$configured];
        }

        return User::query()
            ->where('is_active', 1)
            ->role('admin_upt')
            ->whereNotNull('email')
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }

    private function latestPayment(Booking $booking): ?BookingPayment
    {
        return $booking->payments()
            ->where('gateway', 'manual')
            ->latest('id')
            ->first();
    }

    private function safeSend(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
