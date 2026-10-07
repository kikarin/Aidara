<?php

namespace App\Mail\Booking;

use App\Models\Booking\BookingSurat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSuratMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public BookingSurat $surat,
        public string $unduhUrl,
        public string $pdfContent,
    ) {
    }

    public function envelope(): Envelope
    {
        $judul = $this->surat->perihal ?: $this->surat->jenisLabel();

        return new Envelope(
            subject: $judul.' — '.$this->surat->booking->nomor,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'booking.emails.surat',
        );
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->surat->attachmentName())
                ->withMime('application/pdf'),
        ];
    }
}
