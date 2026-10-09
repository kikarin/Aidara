<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingSurat;
use App\Services\Booking\SuratDeliveryService;
use App\Services\Booking\SuratService;
use App\Services\Fonnte\FonnteService;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Throwable;

class SuratController extends Controller
{
    public function __construct(
        private readonly SuratService $surats,
        private readonly SuratDeliveryService $delivery,
        private readonly FonnteService $fonnte,
    ) {
    }

    public function download(BookingSurat $surat)
    {
        return $this->surats->downloadResponse($surat);
    }

    public function sendEmail(BookingSurat $surat): RedirectResponse
    {
        try {
            $this->delivery->sendEmail($surat);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with(
            'success',
            'Surat dikirim ke '.$surat->booking->user?->email.' beserta lampiran PDF.'.$this->meetingNotice($surat)
        );
    }

    public function shareWhatsApp(BookingSurat $surat): RedirectResponse
    {
        try {
            $this->delivery->sendWhatsApp($surat);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $phone = (string) ($surat->booking->penyewaProfile?->no_hp ?? '');

        return back()->with(
            'success',
            'Surat dikirim via WhatsApp ke '.$this->fonnte->normalizePhone($phone).'.'.$this->meetingNotice($surat)
        );
    }

    private function meetingNotice(BookingSurat $surat): string
    {
        return $surat->jenis           === BookingSurat::JENIS_UNDANGAN_MEETING
            && $surat->booking->status === BookingStatus::MENUNGGU_MEETING
            ? ' Status pengajuan sekarang "Menunggu keputusan akhir".'
            : '';
    }
}
