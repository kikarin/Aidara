<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Bukti pembayaran ditolak</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>,<br><br>
            Mohon maaf, bukti pembayaran Anda untuk booking <b>{{ $booking->nomor }}</b>
            <b style="color:#b91c1c;">belum dapat kami validasi</b>.
        </p>

        @if ($reason)
            <div style="border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#fef2f2;">
                <b>Alasan:</b><br>{{ $reason }}
            </div>
        @endif

        <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
            Silakan unggah ulang bukti pembayaran yang benar melalui halaman detail pesanan
            agar dapat kami proses kembali.
        </p>

        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jadwal</td>
                    <td style="padding:2px 0;text-align:right;">
                        {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
                    </td>
                </tr>
            </table>
        </div>

        <a href="{{ $detailUrl }}"
           style="display:inline-block;background:#b91c1c;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Unggah ulang bukti pembayaran
        </a>
    </div>
</body>
</html>
