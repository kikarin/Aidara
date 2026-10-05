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
            Penyewa <b>{{ $booking->penyewaProfile?->nama ?? $booking->user?->name }}</b>
            telah mengunggah bukti pembayaran. Mohon lakukan pemeriksaan pada aplikasi.
        </p>

        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
            <table style="width:100%;font-size:14px;border-collapse:collapse;">
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Nomor pengajuan</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">{{ $booking->nomor }}</td>
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
                <tr>
                    <td style="padding:2px 0;color:#64748b;">Jumlah transfer</td>
                    <td style="padding:2px 0;text-align:right;font-weight:bold;">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                </tr>
                @if ($payment->bank)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Bank</td>
                        <td style="padding:2px 0;text-align:right;">{{ $payment->bank }}</td>
                    </tr>
                @endif
                @if ($payment->atas_nama)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Atas nama</td>
                        <td style="padding:2px 0;text-align:right;">{{ $payment->atas_nama }}</td>
                    </tr>
                @endif
                @if ($payment->paid_at)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Waktu unggah</td>
                        <td style="padding:2px 0;text-align:right;">{{ $payment->paid_at->format('d-m-Y H:i') }}</td>
                    </tr>
                @endif
                @if ($payment->notes)
                    <tr>
                        <td style="padding:2px 0;color:#64748b;">Catatan penyewa</td>
                        <td style="padding:2px 0;text-align:right;">{{ $payment->notes }}</td>
                    </tr>
                @endif
            </table>
        </div>

        @if ($buktiUrl)
            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
                <a href="{{ $buktiUrl }}" style="color:#1d4ed8;text-decoration:underline;">Lihat berkas bukti pembayaran</a>
            </p>
        @endif

        <a href="{{ $adminUrl }}"
           style="display:inline-block;background:#1d4ed8;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Buka &amp; verifikasi pembayaran
        </a>
    </div>
</body>
</html>
