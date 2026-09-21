<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\User;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingPaymentService
{
    public function __construct(
        private readonly BookingStatusService $statuses,
    ) {}

    public function uploadBukti(Booking $booking, User $user, UploadedFile $file, ?string $notes = null): BookingPayment
    {
        if ($booking->user_id !== $user->id && ! $user->hasRole('admin_upt')) {
            throw new InvalidArgumentException('Tidak berhak mengunggah bukti untuk booking ini.');
        }

        if ($booking->status !== BookingStatus::AWAITING_PAYMENT) {
            throw new InvalidArgumentException('Upload bukti hanya untuk booking berstatus awaiting_payment.');
        }

        $payment = $booking->payments()
            ->where('gateway', 'manual')
            ->whereIn('status', ['pending', 'awaiting_verification'])
            ->latest('id')
            ->first();

        if (! $payment) {
            throw new InvalidArgumentException('Instruksi pembayaran belum tersedia. Hubungi admin.');
        }

        $path = $file->store('booking/payments/'.$booking->id, 'public');

        $payment->update([
            'bukti_path' => $path,
            'status' => 'awaiting_verification',
            'paid_at' => now(),
            'notes' => $notes,
        ]);

        return $payment->refresh();
    }

    /**
     * @return array{booking: Booking, payment: BookingPayment}
     */
    public function verify(BookingPayment $payment, User $admin, ?string $notes = null): array
    {
        $booking = $payment->booking;
        if (! $booking) {
            throw new InvalidArgumentException('Booking tidak ditemukan.');
        }

        if ($booking->status !== BookingStatus::AWAITING_PAYMENT) {
            throw new InvalidArgumentException('Booking tidak dalam status awaiting_payment.');
        }

        if (! $payment->bukti_path) {
            throw new InvalidArgumentException('Belum ada bukti transfer.');
        }

        return DB::transaction(function () use ($payment, $booking, $admin, $notes) {
            $payment->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $admin->id,
                'notes' => $notes ?? $payment->notes,
            ]);

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::PAID,
                'Bukti transfer diverifikasi',
                $admin->id
            );

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::CONFIRMED,
                'Booking dikonfirmasi (hard-lock)',
                $admin->id
            );

            $booking->forceFill(['confirmed_at' => now()])->save();

            return [
                'booking' => $booking->load(['payments', 'venue', 'area', 'items']),
                'payment' => $payment->refresh(),
            ];
        });
    }

    public function rejectBukti(BookingPayment $payment, User $admin, string $reason): BookingPayment
    {
        $booking = $payment->booking;
        if (! $booking || $booking->status !== BookingStatus::AWAITING_PAYMENT) {
            throw new InvalidArgumentException('Pembayaran tidak bisa ditolak pada status ini.');
        }

        $payment->update([
            'status' => 'rejected',
            'verified_at' => now(),
            'verified_by' => $admin->id,
            'notes' => $reason,
            'bukti_path' => null,
            'paid_at' => null,
        ]);

        return $payment->refresh();
    }
}
