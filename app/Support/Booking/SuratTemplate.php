<?php

namespace App\Support\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;

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
            BookingSurat::JENIS_BALASAN_PERSETUJUAN => [
                'perihal' => 'Persetujuan Pengajuan Sewa Fasilitas',
                'isi'     => "Menindaklanjuti pengajuan sewa {$venue} ({$jadwal}), dengan ini kami sampaikan bahwa pengajuan Anda telah disetujui.\n\nSilakan melakukan pembayaran sesuai petunjuk pada halaman detail pesanan Anda sebelum batas waktu berakhir.",
            ],
            BookingSurat::JENIS_BALASAN_PENOLAKAN => [
                'perihal' => 'Balasan Pengajuan Sewa Fasilitas',
                'isi'     => "Menindaklanjuti pengajuan sewa {$venue} ({$jadwal}), dengan ini kami sampaikan bahwa pengajuan Anda belum dapat kami setujui.\n\nApabila terdapat keperluan lain, silakan ajukan kembali melalui sistem E-Booking.",
            ],
            default => [
                'perihal' => 'Undangan Meeting Penyewaan Fasilitas',
                'isi'     => "Menindaklanjuti pengajuan sewa {$venue} ({$jadwal}), kami mengundang Anda untuk hadir pada meeting pembahasan pengajuan sesuai waktu dan tempat tercantum di bawah.\n\nMohon membawa dokumen yang tercantum pada surat ini serta siap dengan informasi kegiatan yang akan dilaksanakan.",
            ],
        };
    }
}
