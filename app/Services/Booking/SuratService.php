<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingSurat;
use App\Models\User;
use App\Support\Booking\SuratTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SuratService
{
    public function create(Booking $booking, User $admin, array $data): BookingSurat
    {
        $jenis = (string) ($data['jenis'] ?? '');

        if (! in_array($jenis, BookingSurat::jenisList(), true)) {
            throw new InvalidArgumentException('Jenis surat tidak dikenal.');
        }

        if ($jenis === BookingSurat::JENIS_UNDANGAN_MEETING && (empty($data['meeting_at']) || empty($data['meeting_place']))) {
            throw new InvalidArgumentException('Waktu dan tempat meeting wajib diisi untuk undangan meeting.');
        }

        $dokumen = collect($data['dokumen'] ?? [])
            ->map(function ($item) {
                if (is_numeric($item)) {
                    return BookingDocumentType::query()->find((int) $item)?->name;
                }

                return is_string($item) ? trim($item) : null;
            })
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->unique()
            ->values()
            ->all();

        $kop = BookingSetting::getValue('surat_kop');
        $kop = is_array($kop) ? $kop : [];

        return DB::transaction(function () use ($booking, $admin, $data, $jenis, $dokumen, $kop) {
            /** @var BookingSurat $surat */
            $surat = $booking->surats()->create([
                'jenis'                 => $jenis,
                'nomor_surat'           => trim((string) $data['nomor_surat']),
                'perihal'               => trim((string) $data['perihal']),
                'isi'                   => (string) $data['isi'],
                'meeting_at'            => $data['meeting_at']    ?? null,
                'meeting_place'         => $data['meeting_place'] ?? null,
                'dokumen'               => $dokumen !== [] ? $dokumen : null,
                'penandatangan_nama'    => $data['penandatangan_nama']    ?? ($kop['penandatangan_nama'] ?? null),
                'penandatangan_jabatan' => $data['penandatangan_jabatan'] ?? ($kop['penandatangan_jabatan'] ?? null),
                'file_path'             => '',
                'created_by'            => $admin->id,
            ]);

            $pdf = Pdf::loadView('booking.surat', [
                'surat'   => $surat,
                'kop'     => $kop,
                'penyewa' => [
                    'nama'     => $booking->penyewaProfile?->nama ?? $booking->user?->name ?? '-',
                    'instansi' => $booking->penyewaProfile?->instansi,
                ],
                'kota' => $kop['kota'] ?? 'Kabupaten Bogor',
            ])->setPaper('a4', 'portrait');

            $path = "booking-surats/{$booking->id}/surat-{$surat->id}.pdf";

            if (! Storage::disk('local')->put($path, $pdf->output())) {
                throw new InvalidArgumentException('Gagal menyimpan berkas surat.');
            }

            $surat->update(['file_path' => $path]);

            return $surat->refresh();
        });
    }

    public function createUndanganMeeting(Booking $booking, User $admin, string $meetingAt, string $meetingPlace): BookingSurat
    {
        $template = SuratTemplate::forJenis($booking, BookingSurat::JENIS_UNDANGAN_MEETING);

        return $this->create($booking, $admin, [
            'jenis'         => BookingSurat::JENIS_UNDANGAN_MEETING,
            'nomor_surat'   => $this->generateNomor(),
            'perihal'       => $template['perihal'],
            'isi'           => $template['isi'],
            'meeting_at'    => $meetingAt,
            'meeting_place' => $meetingPlace,
            'dokumen'       => BookingDocumentType::query()
                ->where('is_active', true)
                ->where('is_required', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->pluck('name')
                ->all(),
        ]);
    }

    public function generateNomor(): string
    {
        $year = (int) now()->year;

        $count = BookingSurat::query()
            ->withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%03d/EB-UPT/%d', $count, $year);
    }

    public function rawPdf(BookingSurat $surat): string
    {
        $content = Storage::disk('local')->get($surat->file_path);

        if ($content === null) {
            throw new InvalidArgumentException('Berkas surat tidak ditemukan.');
        }

        return $content;
    }

    public function downloadResponse(BookingSurat $surat)
    {
        return response()->download(
            Storage::disk('local')->path($surat->file_path),
            $surat->attachmentName(),
            ['Content-Type' => 'application/pdf']
        );
    }
}
