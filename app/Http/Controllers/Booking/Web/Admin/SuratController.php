<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Mail\Booking\BookingSuratMail;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Services\Booking\SuratService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class SuratController extends Controller
{
    public function __construct(
        private readonly SuratService $surats,
    ) {}

    public function store(Request $request, int $bookingId): RedirectResponse
    {
        $booking = Booking::query()->findOrFail($bookingId);

        $data = $request->validate([
            'jenis' => ['required', 'in:'.implode(',', BookingSurat::jenisList())],
            'nomor_surat' => ['required', 'string', 'max:150'],
            'perihal' => ['required', 'string', 'max:200'],
            'isi' => ['required', 'string', 'max:10000'],
            'meeting_at' => ['nullable', 'date', 'required_if:jenis,'.BookingSurat::JENIS_UNDANGAN_MEETING],
            'meeting_place' => ['nullable', 'string', 'max:200', 'required_if:jenis,'.BookingSurat::JENIS_UNDANGAN_MEETING],
            'dokumen' => ['nullable', 'array'],
            'penandatangan_nama' => ['nullable', 'string', 'max:150'],
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
        $surat->loadMissing(['booking.user', 'booking.penyewaProfile']);

        $email = $surat->booking->user?->email;

        if (! $email) {
            return back()->with('error', 'Email penyewa tidak ditemukan.');
        }

        try {
            $unduhUrl = URL::temporarySignedRoute('e-booking.surat.shared', now()->addDays(7), ['surat' => $surat->id]);

            Mail::to($email)->send(
                new BookingSuratMail($surat, $unduhUrl, $this->surats->rawPdf($surat))
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $surat->forceFill(['sent_email_at' => now()])->save();

        return back()->with('success', 'Surat dikirim ke '.$email.' beserta lampiran PDF.');
    }

    public function shareWhatsApp(BookingSurat $surat): RedirectResponse
    {
        $surat->loadMissing(['booking.user', 'booking.penyewaProfile']);

        $unduhUrl = URL::temporarySignedRoute('e-booking.surat.shared', now()->addDays(7), ['surat' => $surat->id]);

        $text = "Surat balasan pengajuan {$surat->booking->nomor}\n"
            .$surat->jenisLabel()." — {$surat->nomor_surat}\n"
            ."Perihal: {$surat->perihal}\n\n"
            ."Unduh surat (PDF): {$unduhUrl}";

        $phone = (string) ($surat->booking->penyewaProfile?->no_hp ?? '');
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        return redirect()->away(
            $digits !== ''
                ? 'https://wa.me/'.$digits.'?text='.rawurlencode($text)
                : 'https://wa.me/?text='.rawurlencode($text)
        );
    }
}
