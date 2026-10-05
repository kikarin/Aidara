<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaymentRejectedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingPayment $payment,
        public string $reason = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bukti pembayaran '.$this->booking->nomor.' ditolak — silakan unggah ulang',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.payment-rejected',
            with: [
                'detailUrl' => route('e-booking.bookings.show', $this->booking->id),
                'reason' => $this->reason,
            ],
        );
    }
}
