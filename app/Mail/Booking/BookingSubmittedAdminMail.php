<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSubmittedAdminMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan baru '.$this->booking->nomor.' menunggu peninjauan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.submitted-admin',
            with: [
                'adminUrl' => route('e-booking.admin.bookings.show', $this->booking->id),
            ],
        );
    }
}
