<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Bukti pembayaran diterima</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>,<br><br>
            Bukti pembayaran Anda untuk booking <b>{{ $booking->nomor }}</b> telah kami terima.
            Saat ini sedang <b style="color:#b45309;">menunggu validasi</b> oleh pengelola.
            Anda akan menerima email berikutnya setelah pembayaran divalidasi.
        </p>

        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jumlah transfer</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jadwal</td>
                    <td style="padding:2px 0;text-align:right;">
                        {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
                    </td>
                </tr>
                @if ($payment->paid_at)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Waktu unggah</td>
                        <td style="padding:2px 0;text-align:right;">{{ $payment->paid_at->format('d-m-Y H:i') }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <a href="{{ $detailUrl }}"
           style="display:inline-block;background:#2e7d32;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Lihat detail pesanan
        </a>
    </div>
</body>
</html>
