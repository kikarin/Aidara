<?php

namespace App\Services\Booking\Payment;

use App\Models\Booking\BookingSetting;
use InvalidArgumentException;

class PaymentGatewayFactory
{
    public function resolve(?string $mode = null): PaymentGatewayInterface
    {
        $mode = $mode ?? BookingSetting::getValue('payment_mode', 'manual');
        if (is_array($mode)) {
            $mode = $mode[0] ?? 'manual';
        }
        $mode = (string) $mode;

        return match ($mode) {
            'manual' => app(ManualTransferGateway::class),
            'bjb', 'bjb_va' => app(BjbVirtualAccountGateway::class),
            'bjb_qris' => app(BjbQrisGateway::class),
            default => throw new InvalidArgumentException("Payment mode tidak dikenal: {$mode}"),
        };
    }
}
