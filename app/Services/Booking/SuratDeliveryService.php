<?php

namespace App\Services\Booking;

use App\Mail\Booking\BookingSuratMail;
use App\Models\Booking\BookingSurat;
use App\Services\Fonnte\FonnteService;
use App\Support\Booking\BookingStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class SuratDeliveryService
{
    public function __construct(
        private readonly SuratService $surats,
        private readonly FonnteService $fonnte,
        private readonly BookingStatusService $statuses,
    ) {
    }

    /**
     * @return array{email: bool, whatsapp: bool, errors: list<string>}
     */
    public function deliver(BookingSurat $surat): array
    {
        $errors = [];

        try {
            $this->sendEmail($surat);
            $emailSent = true;
        } catch (Throwable $e) {
            $emailSent = false;
            $errors[]  = 'Email: '.$e->getMessage();
            report($e);
        }

        try {
            $whatsappSent = $this->sendWhatsApp($surat);
        } catch (Throwable $e) {
            $whatsappSent = false;
            $errors[]     = 'WhatsApp: '.$e->getMessage();
            report($e);
        }

        return [
            'email'    => $emailSent,
            'whatsapp' => $whatsappSent,
            'errors'   => $errors,
        ];
    }

    public function sendEmail(BookingSurat $surat): void
    {
        $surat->loadMissing(['booking.user', 'booking.penyewaProfile']);

        $email = $surat->booking->user?->email;

        if (! $email) {
            throw new \RuntimeException('Email penyewa tidak ditemukan.');
        }

        $unduhUrl = $this->downloadUrl($surat);

        Mail::to($email)->send(
            new BookingSuratMail($surat, $unduhUrl, $this->surats->rawPdf($surat))
        );

        $surat->forceFill(['sent_email_at' => now()])->save();

        $this->markMeetingInvited($surat, 'email');
    }

    public function sendWhatsApp(BookingSurat $surat): bool
    {
        $surat->loadMissing(['booking.user', 'booking.penyewaProfile']);

        if (! $this->fonnte->isConfigured()) {
            throw new \RuntimeException('Fonnte belum dikonfigurasi. Atur FONNTE_ENABLED dan FONNTE_TOKEN terlebih dahulu.');
        }

        $phone = (string) ($surat->booking->penyewaProfile?->no_hp ?? '');

        if ($this->fonnte->normalizePhone($phone) === '') {
            throw new \RuntimeException('Nomor WhatsApp penyewa tidak ditemukan.');
        }

        $text = $this->message($surat);

        $this->fonnte->sendDocument(
            $phone,
            $text,
            $this->surats->rawPdf($surat),
            $surat->attachmentName(),
        );

        $surat->forceFill(['sent_whatsapp_at' => now()])->save();

        $this->markMeetingInvited($surat, 'WhatsApp');

        return true;
    }

    /**
     * Pengajuan yang masih ditinjau berpindah ke menunggu_meeting begitu undangannya sampai ke penyewa.
     * Undangan yang dikirim saat approve tidak mengubah status karena booking sudah awaiting_payment.
     */
    private function markMeetingInvited(BookingSurat $surat, string $channel): void
    {
        if ($surat->jenis !== BookingSurat::JENIS_UNDANGAN_MEETING) {
            return;
        }

        $booking = $surat->booking->refresh();

        if (! in_array($booking->status, [BookingStatus::MENUNGGU_APPROVAL, BookingStatus::PERLU_KLARIFIKASI], true)) {
            return;
        }

        $jadwal = $surat->meeting_at?->translatedFormat('d F Y, H:i');

        $note = 'Undangan meeting dikirim via '.$channel
            .($jadwal ? ' · '.$jadwal.' WIB' : '')
            .($surat->meeting_place ? ' · '.$surat->meeting_place : '');

        $this->statuses->transition($booking, BookingStatus::MENUNGGU_MEETING, $note, Auth::id());
    }

    public function downloadUrl(BookingSurat $surat): string
    {
        return URL::temporarySignedRoute('e-booking.surat.shared', now()->addDays(7), ['surat' => $surat->id]);
    }

    private function message(BookingSurat $surat): string
    {
        $text = "Surat pengajuan {$surat->booking->nomor}\n"
            .$surat->jenisLabel()." — {$surat->nomor_surat}\n"
            ."Perihal: {$surat->perihal}";

        if ($surat->meeting_at || $surat->meeting_place) {
            $text .= "\n\nMeeting: "
                .($surat->meeting_at?->translatedFormat('d F Y, H:i') ?? '-')
                .' WIB'
                .($surat->meeting_place ? ' · '.$surat->meeting_place : '');
        }

        return $text."\n\nUnduh surat (PDF): ".$this->downloadUrl($surat);
    }
}
