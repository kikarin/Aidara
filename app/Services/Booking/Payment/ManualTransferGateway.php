<?php

namespace App\Services\Booking\Payment;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingSetting;
use Carbon\Carbon;

class ManualTransferGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function initiate(Booking $booking, array $payload = []): BookingPayment
    {
        $existing = BookingPayment::query()
            ->where('booking_id', $booking->id)
            ->where('gateway', $this->name())
            ->whereIn('status', ['pending', 'awaiting_verification'])
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $rekening = BookingSetting::getValue('rekening_transfer', [
            'bank' => 'BJB Cabang Cibinong',
            'rekening' => '048.026020204.2',
            'atas_nama' => 'Rekening Kas Umum Daerah (RKUD) Kabupaten Bogor',
        ]);

        if (! is_array($rekening)) {
            $rekening = [
                'bank' => 'BJB Cabang Cibinong',
                'rekening' => '048.026020204.2',
                'atas_nama' => 'Rekening Kas Umum Daerah (RKUD) Kabupaten Bogor',
            ];
        }

        $expireHours = (int) (BookingSetting::getValue('payment_expire_hours', 48) ?? 48);
        if ($expireHours < 1) {
            $expireHours = 48;
        }

        $expiresAt = Carbon::now()->addHours($expireHours);

        return BookingPayment::query()->create([
            'booking_id' => $booking->id,
            'gateway' => $this->name(),
            'amount' => $booking->grand_total,
            'status' => 'pending',
            'bank' => $rekening['bank'] ?? null,
            'rekening' => $rekening['rekening'] ?? null,
            'atas_nama' => $rekening['atas_nama'] ?? null,
            'meta' => array_merge($payload, [
                'expires_at' => $expiresAt->toDateTimeString(),
                'expire_hours' => $expireHours,
            ]),
        ]);
    }
}
