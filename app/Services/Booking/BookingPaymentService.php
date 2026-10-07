<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\User;
use App\Support\Booking\BookingJenisSewa;
use App\Support\Booking\BookingStatus;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingPaymentService
{
    public function __construct(
        private readonly BookingStatusService $statuses,
        private readonly AvailabilityService $availability,
        private readonly RulesEngine $rules,
        private readonly BookingNotifier $notifier,
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
            ->latest('id')
            ->first();

        if (! $payment) {
            throw new InvalidArgumentException('Instruksi pembayaran belum tersedia. Hubungi admin.');
        }

        // Hanya boleh upload pertama kali (pending) atau kirim ulang setelah ditolak.
        // Setelah bukti diverifikasi, booking tidak lagi berstatus awaiting_payment.
        if (! in_array($payment->status, ['pending', 'rejected'], true)) {
            throw new InvalidArgumentException(
                $payment->status === 'awaiting_verification'
                    ? 'Bukti pembayaran sedang diperiksa admin.'
                    : 'Pembayaran sudah diverifikasi dan tidak bisa diunggah ulang.'
            );
        }

        $path = $file->store('booking/payments/'.$booking->id, 'public');

        $payment->update([
            'bukti_path' => $path,
            'status' => 'awaiting_verification',
            'paid_at' => now(),
            'notes' => $notes,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        $payment = $payment->refresh();

        $this->notifier->notifyBuktiAdmins($booking, $payment);
        $this->notifier->notifyBuktiReceived($booking, $payment);

        return $payment;
    }

    /**
     * @return array{booking: Booking, payment: BookingPayment}
     */
    public function verify(BookingPayment $payment, User $admin, ?string $notes = null): array
    {
        $result = DB::transaction(function () use ($payment, $admin, $notes) {
            /** @var BookingPayment $payment */
            $payment = BookingPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Booking $booking */
            $booking = Booking::query()
                ->whereKey($payment->booking_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($booking->status !== BookingStatus::AWAITING_PAYMENT) {
                throw new InvalidArgumentException('Booking tidak dalam status awaiting_payment.');
            }

            if (! $payment->bukti_path) {
                throw new InvalidArgumentException('Belum ada bukti transfer.');
            }

            // Serialisasi race: kunci semua booking overlap di window buffer.
            $this->lockOverlappingBookings($booking);

            // Blokir jika sudah ada hard-lock booking lain di slot yang sama.
            // Pengajuan per hari tidak memblokir pembayaran booking per jam lama.
            $this->availability->assertBookable([
                'venue_id' => $booking->venue_id,
                'area_id' => $booking->area_id,
                'starts_at' => $booking->starts_at,
                'ends_at' => $booking->ends_at,
                'exclude_booking_id' => $booking->id,
                'is_per_hari' => BookingJenisSewa::isPerHari($booking),
                'block_pengajuan' => false,
            ]);

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
                'booking' => $booking->load(['payments', 'venue', 'areas', 'items']),
                'payment' => $payment->refresh(),
            ];
        });

        $this->notifier->notifyPaymentVerified($result['booking']);

        return $result;
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

        $payment->refresh();

        $this->notifier->notifyPaymentRejected($booking, $payment, $reason);

        return $payment;
    }

    private function lockOverlappingBookings(Booking $booking): void
    {
        $bufferBefore = (int) ($booking->buffer_before_days
            ?? $this->rules->get('buffer_before_days', $booking->venue_id, 1)
            ?? 1);
        $bufferAfter = (int) ($booking->buffer_after_days
            ?? $this->rules->get('buffer_after_days', $booking->venue_id, 1)
            ?? 1);

        $windowStart = Carbon::parse($booking->starts_at)->subDays($bufferBefore)->startOfDay();
        $windowEnd = Carbon::parse($booking->ends_at)->addDays($bufferAfter)->endOfDay();

        $query = Booking::query()
            ->where('venue_id', $booking->venue_id)
            ->whereIn('status', BookingStatus::locking())
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart)
            ->orderBy('id');

        if ($booking->area_id) {
            $query->where(function ($q) use ($booking) {
                $q->where('area_id', $booking->area_id)->orWhereNull('area_id');
            });
        }

        // lockForUpdate menahan transaksi lain yang overlap sampai commit.
        $query->lockForUpdate()->get(['id']);
    }
}
