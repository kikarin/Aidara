<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Services\Booking\SuratDeliveryService;
use App\Services\Booking\SuratService;
use App\Services\Fonnte\FonnteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class SuratController extends Controller
{
    public function __construct(
        private readonly SuratService $surats,
        private readonly SuratDeliveryService $delivery,
        private readonly FonnteService $fonnte,
    ) {
    }

    public function store(Request $request, int $bookingId): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($bookingId);

        $data = $request->validate([
            'jenis'                 => ['required', 'in:'.implode(',', BookingSurat::jenisList())],
            'nomor_surat'           => ['required', 'string', 'max:150'],
            'perihal'               => ['required', 'string', 'max:200'],
            'isi'                   => ['required', 'string', 'max:10000'],
            'meeting_at'            => ['nullable', 'date', 'required_if:jenis,'.BookingSurat::JENIS_UNDANGAN_MEETING],
            'meeting_place'         => ['nullable', 'string', 'max:200', 'required_if:jenis,'.BookingSurat::JENIS_UNDANGAN_MEETING],
            'dokumen'               => ['nullable', 'array'],
            'penandatangan_nama'    => ['nullable', 'string', 'max:150'],
            'penandatangan_jabatan' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $surat = $this->surats->create($booking, $request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $booking->id)
            ->with('success', 'Surat "'.$surat->jenisLabel().'" berhasil dibuat dan bisa diunduh.');
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
            'Surat dikirim ke '.$surat->booking->user?->email.' beserta lampiran PDF.'
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
            'Surat dikirim via WhatsApp ke '.$this->fonnte->normalizePhone($phone).'.'
        );
    }
}
