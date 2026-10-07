<?php

namespace App\Support\Booking;

use App\Models\Booking\Booking;

final class SuratTemplate
{
    /**
     * @return array{perihal: string, isi: string}
     */
    public static function forJenis(Booking $booking, string $jenis): array
    {
        $venue  = $booking->venue?->name ?? 'venue';
        $jadwal = ($booking->starts_at?->format('Y-m-d H:i') ?? '-')
            .' s.d. '.($booking->ends_at?->format('Y-m-d H:i') ?? '-');

        return match ($jenis) {
            default => [
                'perihal' => 'Undangan Meeting Penyewaan Fasilitas',
                'isi'     => "Menindaklanjuti pengajuan sewa {$venue} ({$jadwal}), kami mengundang Anda untuk hadir pada meeting pembahasan pengajuan sesuai waktu dan tempat tercantum di bawah.\n\nMohon membawa dokumen yang tercantum pada surat ini serta siap dengan informasi kegiatan yang akan dilaksanakan.",
            ],
        };
    }
}
