<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Pengajuan baru masuk</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $booking->nomor }}</h1>

        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;">
            Pengajuan sewa baru telah dikirim dan menunggu peninjauan pengelola.
        </p>

        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Penyewa</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">{{ $booking->penyewaProfile?->nama ?? $booking->user?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Venue</td>
                    <td style="padding:2px 0;text-align:right;">{{ $booking->venue?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jadwal</td>
                    <td style="padding:2px 0;text-align:right;">
                        {{ $booking->starts_at?->format('d-m-Y H:i') }} — {{ $booking->ends_at?->format('d-m-Y H:i') }}
                    </td>
                </tr>
                @if ($booking->tujuan)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Tujuan</td>
                        <td style="padding:2px 0;text-align:right;">{{ $booking->tujuan }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Total</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $booking->grand_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Dikirim</td>
                    <td style="padding:2px 0;text-align:right;">{{ $booking->submitted_at?->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
            </table>
        </div>

        <a href="{{ $adminUrl }}"
           style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Buka &amp; tinjau pengajuan
        </a>
    </div>
</body>
</html>
