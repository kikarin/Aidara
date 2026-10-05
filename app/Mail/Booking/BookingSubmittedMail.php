<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Support\Booking\BookingStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSubmittedMail extends Mailable
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
            subject: $this->perluBayar()
                ? 'Booking '.$this->booking->nomor.' diterima — silakan lakukan pembayaran'
                : 'Pengajuan '.$this->booking->nomor.' diterima — menunggu peninjauan',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.submitted',
            with: [
                'perluBayar' => $this->perluBayar(),
                'expiresAt' => $this->payment?->meta['expires_at'] ?? null,
            ],
        );
    }

    private function perluBayar(): bool
    {
        return $this->booking->status === BookingStatus::AWAITING_PAYMENT;
    }
}
