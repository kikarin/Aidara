<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Pengajuan disetujui</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>,<br><br>
            Selamat, pengajuan sewa <b>{{ $booking->venue?->name }}</b> pada
            {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
            telah <b style="color:#2e7d32;">disetujui</b>.
        </p>

        @if ($dokumenWajib)
            <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
                <b>Silakan siapkan dokumen berikut (dibawa saat meeting):</b>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    @foreach ($dokumenWajib as $dok)
                        <li>{{ $dok }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
            Surat balasan berisi jadwal meeting akan dikirim terpisah melalui email/WA oleh pengelola.
            Setelah meeting, silakan lakukan pembayaran sesuai petunjuk pada halaman detail pesanan.
        </p>

        <a href="{{ route('e-booking.bookings.show', $booking->id) }}"
           style="display:inline-block;background:#2e7d32;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Lihat detail pesanan
        </a>
    </div>
</body>
</html>
