<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Models\User;

class BookingInvitationService
{
    public function __construct(
        private readonly SuratService $surats,
        private readonly SuratDeliveryService $delivery,
    ) {
    }

    /**
     * Buat Undangan Meeting lalu kirim via email dan WhatsApp.
     *
     * @return array{surat: BookingSurat, email: bool, whatsapp: bool, errors: list<string>}
     */
    public function sendOnApproval(Booking $booking, User $admin, string $meetingAt, string $meetingPlace): array
    {
        $booking->loadMissing(['venue', 'penyewaProfile', 'user']);

        $surat = $this->surats->createUndanganMeeting($booking, $admin, $meetingAt, $meetingPlace);

        $result = $this->delivery->deliver($surat);

        return [
            'surat'    => $surat,
            'email'    => $result['email'],
            'whatsapp' => $result['whatsapp'],
            'errors'   => $result['errors'],
        ];
    }
}
