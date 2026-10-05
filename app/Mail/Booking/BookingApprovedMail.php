<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Support\Booking\BookingJenisSewa;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingApprovedMail extends Mailable implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  list<string>  $dokumenWajib
     */
    public function __construct(
        public Booking $booking,
        public array $dokumenWajib,
        public string $jenisSewa = BookingJenisSewa::EVENT,
        public ?BookingPayment $payment = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->jenisSewa === BookingJenisSewa::REGULER
                ? 'Pengajuan '.$this->booking->nomor.' disetujui — silakan lakukan pembayaran'
                : 'Pengajuan '.$this->booking->nomor.' disetujui — silakan siapkan dokumen',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.approved',
            with: [
                'reguler' => $this->jenisSewa === BookingJenisSewa::REGULER,
                'expiresAt' => $this->payment?->meta['expires_at'] ?? null,
            ],
        );
    }
}
