<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Helvetica,Arial,sans-serif;color:#1e293b;">
    <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px;">
        <p style="margin:0 0 4px;font-size:14px;color:#64748b;">Surat dari pengelola</p>
        <h1 style="margin:0 0 16px;font-size:20px;">{{ $surat->booking->nomor }}</h1>

        <p style="margin:0 0 12px;font-size:14px;line-height:1.6;">
            Kepada Yth. <b>{{ $surat->booking->penyewaProfile?->nama ?? $surat->booking->user?->name }}</b>,<br><br>
            Anda menerima surat dengan rincian berikut:
        </p>

        <table style="width:100%;font-size:14px;border-collapse:collapse;margin:0 0 16px;">
            <tr>
                <td style="padding:4px 0;color:#64748b;width:120px;">Jenis surat</td>
                <td style="padding:4px 0;"><b>{{ $surat->jenisLabel() }}</b></td>
            </tr>
            @if ($surat->nomor_surat)
                <tr>
                    <td style="padding:4px 0;color:#64748b;">Nomor surat</td>
                    <td style="padding:4px 0;">{{ $surat->nomor_surat }}</td>
                </tr>
            @endif
            @if ($surat->perihal)
                <tr>
                    <td style="padding:4px 0;color:#64748b;">Perihal</td>
                    <td style="padding:4px 0;">{{ $surat->perihal }}</td>
                </tr>
            @endif
            @if ($surat->meeting_at || $surat->meeting_place)
                <tr>
                    <td style="padding:4px 0;color:#64748b;vertical-align:top;">Meeting</td>
                    <td style="padding:4px 0;">
                        {{ $surat->meeting_at?->translatedFormat('d F Y, H:i') ?? '-' }} WIB<br>
                        {{ $surat->meeting_place }}
                    </td>
                </tr>
            @endif
        </table>

        @if ($surat->isi)
            <p style="margin:0 0 16px;font-size:14px;line-height:1.7;white-space:pre-line;">{{ $surat->isi }}</p>
        @endif

        @if ($surat->dokumen)
            <div style="border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;margin:0 0 16px;font-size:14px;line-height:1.7;background:#f8fafc;">
                <b>Dokumen yang perlu disiapkan:</b>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    @foreach ($surat->dokumen as $dok)
                        <li>{{ $dok }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p style="margin:0 0 20px;font-size:14px;line-height:1.6;">
            Surat resminya terlampir pada email ini (PDF). Anda juga bisa mengunduhnya kapan saja dari
            halaman detail pesanan di sistem E-Booking.
        </p>

        <a href="{{ $unduhUrl }}"
           style="display:inline-block;background:#2e7d32;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;padding:10px 20px;border-radius:999px;">
            Unduh surat (PDF)
        </a>

        <p style="margin:24px 0 0;font-size:12px;color:#94a3b8;">
            Link unduh berlaku 7 hari. Terima kasih.
        </p>
    </div>
</body>
</html>
