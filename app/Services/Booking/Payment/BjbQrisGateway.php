<?php

namespace App\Services\Booking\Payment;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use RuntimeException;

/**
 * Stub — aktif setelah E-BOOKING-BJB.md.
 */
class BjbQrisGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'bjb_qris';
    }

    public function initiate(Booking $booking, array $payload = []): BookingPayment
    {
        throw new RuntimeException('BJB QRIS belum aktif.');
    }
}
