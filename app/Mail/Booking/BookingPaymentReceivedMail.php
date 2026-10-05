<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaymentReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingPayment $payment,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bukti pembayaran '.$this->booking->nomor.' diterima — menunggu validasi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.payment-received',
            with: [
                'detailUrl' => route('e-booking.bookings.show', $this->booking->id),
                'expiresAt' => $this->payment->meta['expires_at'] ?? null,
            ],
        );
    }
}
