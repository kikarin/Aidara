<?php

namespace App\Services\Booking\Payment;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;

interface PaymentGatewayInterface
{
    public function name(): string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function initiate(Booking $booking, array $payload = []): BookingPayment;
}
