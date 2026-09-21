<?php

namespace App\Console\Commands;

use App\Services\Booking\BookingPaymentExpireService;
use Illuminate\Console\Command;

class ExpireBookingPaymentsCommand extends Command
{
    protected $signature = 'booking:expire-payments';

    protected $description = 'Expire E-Booking payments that passed awaiting_payment deadline';

    public function handle(BookingPaymentExpireService $service): int
    {
        $result = $service->expireDue();

        $this->info(sprintf(
            'E-Booking expire: %d expired, %d skipped.',
            $result['expired'],
            $result['skipped']
        ));

        if ($result['ids'] !== []) {
            $this->line('IDs: '.implode(', ', $result['ids']));
        }

        return self::SUCCESS;
    }
}
