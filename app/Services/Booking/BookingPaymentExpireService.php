<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingSetting;
use App\Support\Booking\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingPaymentExpireService
{
    public function __construct(
        private readonly BookingStatusService $statuses,
    ) {}

    /**
     * @return array{expired: int, skipped: int, ids: list<int>}
     */
    public function expireDue(?Carbon $now = null): array
    {
        $now = $now ?? now();
        $defaultHours = (int) (BookingSetting::getValue('payment_expire_hours', 48) ?? 48);
        if ($defaultHours < 1) {
            $defaultHours = 48;
        }

        $candidates = Booking::query()
            ->where('status', BookingStatus::AWAITING_PAYMENT)
            ->with(['payments' => fn ($q) => $q->where('gateway', 'manual')->latest('id')])
            ->orderBy('id')
            ->get();

        $expired = 0;
        $skipped = 0;
        $ids = [];

        foreach ($candidates as $booking) {
            /** @var BookingPayment|null $payment */
            $payment = $booking->payments->first();
            if (! $payment) {
                $skipped++;

                continue;
            }

            if (! in_array($payment->status, ['pending', 'awaiting_verification'], true)) {
                $skipped++;

                continue;
            }

            $expiresAt = $this->resolveExpiresAt($payment, $defaultHours);
            if ($expiresAt === null || $now->lt($expiresAt)) {
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($booking, $payment, $expiresAt, &$expired, &$ids) {
                $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->first();
                if (! $locked || $locked->status !== BookingStatus::AWAITING_PAYMENT) {
                    return;
                }

                $this->statuses->transition(
                    $locked,
                    BookingStatus::EXPIRED,
                    'Pembayaran kadaluarsa (melewati tenggat '.$expiresAt->toDateTimeString().')',
                    null
                );

                $payment->update([
                    'status' => 'expired',
                    'notes' => trim(($payment->notes ? $payment->notes.' | ' : '').'Auto-expired'),
                    'meta' => array_merge($payment->meta ?? [], [
                        'expired_at' => now()->toDateTimeString(),
                    ]),
                ]);

                $expired++;
                $ids[] = $locked->id;
            });
        }

        return compact('expired', 'skipped', 'ids');
    }

    private function resolveExpiresAt(BookingPayment $payment, int $defaultHours): ?Carbon
    {
        $meta = $payment->meta ?? [];
        if (! empty($meta['expires_at'])) {
            return Carbon::parse($meta['expires_at']);
        }

        $hours = (int) ($meta['expire_hours'] ?? $defaultHours);

        return Carbon::parse($payment->created_at)->addHours(max(1, $hours));
    }
}
