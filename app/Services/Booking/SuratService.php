<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class SuratService
{
    /**
     * Simpan surat dari PDF yang diunggah admin (undangan meeting / surat balasan).
     * Metadata nomor/perihal dibiarkan kosong karena berkas disiapkan admin.
     */
    public function storeUpload(
        Booking $booking,
        User $admin,
        string $jenis,
        UploadedFile $file,
        ?string $catatan = null,
        ?string $meetingAt = null,
        ?string $meetingPlace = null,
    ): BookingSurat {
        if (! in_array($jenis, BookingSurat::jenisUnggahan(), true)) {
            throw new InvalidArgumentException('Jenis surat tidak dikenal.');
        }

        return DB::transaction(function () use ($booking, $admin, $jenis, $file, $catatan, $meetingAt, $meetingPlace) {
            /** @var BookingSurat $surat */
            $surat = $booking->surats()->create([
                'jenis'         => $jenis,
                'isi'           => $catatan,
                'meeting_at'    => $meetingAt ?: null,
                'meeting_place' => $meetingPlace ?: null,
                'file_path'     => '',
                'created_by'    => $admin->id,
            ]);

            $path = "booking-surats/{$booking->id}/surat-{$surat->id}.pdf";

            if (! Storage::disk('local')->put($path, $file->get())) {
                throw new InvalidArgumentException('Gagal menyimpan berkas surat.');
            }

            $surat->update(['file_path' => $path]);

            return $surat->refresh();
        });
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
