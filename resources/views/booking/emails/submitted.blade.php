<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">
            {{ $perluBayar ? 'Booking diterima' : 'Pengajuan diterima' }}
        </p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>,<br><br>
            Terima kasih. {{ $perluBayar ? 'Booking' : 'Pengajuan' }} sewa
            <b>{{ $booking->venue?->name }}</b> pada
            {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
            telah kami <b style="color:#2e7d32;">terima</b>.
        </p>

        @if ($perluBayar)
            @if ($payment)
                <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
                    <b>Petunjuk pembayaran</b>
                    <table style="width:100%;margin-top:6px;font-size:14px;border-collapse:collapse;">
                        <tr>
                            <td style="padding:2px 0;color:#64748b;">Jumlah</td>
                            <td style="padding:2px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                        </tr>
                        @if ($payment->bank)
                            <tr>
                                <td style="padding:2px 0;color:#64748b;">Bank</td>
                                <td style="padding:2px 0;text-align:right;">{{ $payment->bank }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;color:#64748b;">No. rekening</td>
                                <td style="padding:2px 0;text-align:right;font-family:monospace;font-size:15px;">{{ $payment->rekening }}</td>
                            </tr>
                            <tr>
                                <td style="padding:2px 0;color:#64748b;">Atas nama</td>
                                <td style="padding:2px 0;text-align:right;">{{ $payment->atas_nama }}</td>
                            </tr>
                        @endif
                        @if ($expiresAt)
                            <tr>
                                <td style="padding:2px 0;color:#64748b;">Bayar sebelum</td>
                                <td style="padding:2px 0;text-align:right;color:#b45309;font-weight:bold;">{{ \Illuminate\Support\Carbon::parse($expiresAt)->format('d-m-Y H:i') }}</td>
                            </tr>
                        @endif
                    </table>
                </div>
            @endif

            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
                Silakan transfer sesuai petunjuk di atas, lalu unggah bukti pembayaran di halaman detail pesanan.
                Jadwal sudah kami tahan untuk Anda sampai batas waktu pembayaran. Setelah bukti diunggah,
                pengelola akan memvalidasi pembayaran Anda.
            </p>
        @else
            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
                Pengajuan Anda sedang ditinjau oleh pengelola. Kami akan mengirimkan
                pemberitahuan berikutnya setelah pengajuan disetujui.
            </p>
        @endif

        <a href="{{ route('e-booking.bookings.show', $booking->id) }}"
           style="display:inline-block;background:#2e7d32;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            {{ $perluBayar ? 'Bayar dan unggah bukti' : 'Lihat detail pesanan' }}
        </a>
    </div>
</body>
</html>
