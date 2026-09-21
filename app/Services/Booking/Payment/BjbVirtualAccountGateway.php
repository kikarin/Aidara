<?php

namespace App\Services\Booking\Payment;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use RuntimeException;

/**
 * Stub — aktif setelah E-BOOKING-BJB.md.
 */
class BjbVirtualAccountGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'bjb_va';
    }

    public function initiate(Booking $booking, array $payload = []): BookingPayment
    {
        throw new RuntimeException('BJB Virtual Account belum aktif.');
    }
}
