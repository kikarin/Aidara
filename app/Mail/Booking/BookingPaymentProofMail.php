<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class BookingPaymentProofMail extends Mailable
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
            subject: 'Bukti pembayaran '.$this->booking->nomor.' menunggu verifikasi',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.payment-proof',
            with: [
                'buktiUrl' => $this->payment->bukti_path
                    ? url(Storage::disk('public')->url($this->payment->bukti_path))
                    : null,
                'adminUrl' => route('e-booking.admin.bookings.show', $this->booking->id),
            ],
        );
    }
}
