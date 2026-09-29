<?php

namespace App\Mail\Booking;

use App\Models\Booking\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingApprovedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<string>  $dokumenWajib
     */
    public function __construct(
        public Booking $booking,
        public array $dokumenWajib,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengajuan '.$this->booking->nomor.' disetujui — silakan siapkan dokumen',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.approved',
        );
    }
}
