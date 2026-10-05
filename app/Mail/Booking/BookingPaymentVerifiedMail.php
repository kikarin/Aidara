<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaymentVerifiedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?BookingPayment $payment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pembayaran '.$this->booking->nomor.' tervalidasi — booking dikonfirmasi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.payment-verified',
            with: [
                'detailUrl' => route('e-booking.bookings.show', $this->booking->id),
            ],
        );
    }
}
