<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Pembayaran tervalidasi</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>,<br><br>
            Pembayaran Anda telah <b style="color:#2e7d32;">divalidasi</b> dan booking Anda
            <b style="color:#2e7d32;">terkonfirmasi</b>. Terima kasih.
        </p>

        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Venue</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">{{ $booking->venue?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jadwal</td>
                    <td style="padding:2px 0;text-align:right;">
                        {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
                    </td>
                </tr>
                @if ($payment)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Jumlah dibayar</td>
                        <td style="padding:2px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
            Silakan hadir sesuai jadwal. Tunjukkan nomor booking ini bila diperlukan.
        </p>

        <a href="{{ $detailUrl }}"
           style="display:inline-block;background:#2e7d32;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Lihat detail pesanan
        </a>
    </div>
</body>
</html>
