<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $surat->nomor_surat }}</title>
    <style>
        @page { margin: 28px 40px 40px 40px; }
        * { font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; font-size: 11pt; color: #1a1a1a; }
        .kop { width: 100%; border-bottom: 3px solid #1a5c2e; padding-bottom: 8px; }
        .kop td { vertical-align: middle; }
        .kop-logo { width: 70px; }
        .kop-info { text-align: center; }
        .kop-info .instansi { font-size: 15pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4px; }
        .kop-info .alamat { font-size: 9pt; color: #444; }
        .meta { margin-top: 14px; width: 100%; }
        .meta td { padding: 1px 0; vertical-align: top; font-size: 10.5pt; }
        .meta .label { width: 95px; }
        .tujuan { margin-top: 16px; font-size: 10.5pt; line-height: 1.5; }
        .isi { margin-top: 14px; text-align: justify; line-height: 1.65; }
        .meeting { margin-top: 10px; padding: 8px 12px; background: #f1f5f1; border-left: 4px solid #1a5c2e; line-height: 1.6; }
        .dokumen { margin-top: 10px; padding: 8px 12px; border: 1px solid #c9d6cc; background: #fafcfa; line-height: 1.6; }
        .dokumen ul { margin: 6px 0 0 18px; padding: 0; }
        .dokumen li { margin-bottom: 3px; }
        .ttd { margin-top: 28px; width: 100%; }
        .ttd td { font-size: 10.5pt; vertical-align: top; }
        .ttd .kanan { width: 45%; text-align: left; padding-left: 55%; }
        .ttd .nama { margin-top: 64px; font-weight: bold; text-decoration: underline; }
        .footer { margin-top: 24px; border-top: 1px solid #999; padding-top: 6px; font-size: 8pt; color: #666; text-align: center; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td class="kop-logo">
                @if (file_exists(public_path('Logo.png')))
                    <img src="{{ public_path('Logo.png') }}" width="60" alt="logo">
                @endif
            </td>
            <td class="kop-info">
                <div class="instansi">{{ $kop['instansi'] ?? config('app.name', 'E-Booking') }}</div>
                @if (! empty($kop['alamat']))
                    <div class="alamat">{{ $kop['alamat'] }}</div>
                @endif
                @if (! empty($kop['telp']) || ! empty($kop['email']))
                    <div class="alamat">
                        @if (! empty($kop['telp']))Telp. {{ $kop['telp'] }}@endif
                        @if (! empty($kop['telp']) && ! empty($kop['email'])) &middot; @endif
                        @if (! empty($kop['email'])){{ $kop['email'] }}@endif
                    </div>
                @endif
            </td>
            <td class="kop-logo"></td>
        </tr>
    </table>

    <table class="meta">
        <tr><td class="label">Nomor</td><td>: {{ $surat->nomor_surat }}</td></tr>
        <tr><td class="label">Lampiran</td><td>: -</td></tr>
        <tr><td class="label">Perihal</td><td>: <b>{{ $surat->perihal }}</b></td></tr>
    </table>

    <div class="tujuan">
        Kepada Yth.<br>
        <b>{{ $penyewa['nama'] }}</b>@if (! empty($penyewa['instansi'])) — {{ $penyewa['instansi'] }}@endif<br>
        di Tempat
    </div>

    <div class="isi">
        <p>Dengan hormat,</p>
        <p>{!! nl2br(e($surat->isi)) !!}</p>

        @if ($surat->meeting_at || $surat->meeting_place)
            <div class="meeting">
                <b>Pelaksanaan meeting</b><br>
                Waktu: {{ $surat->meeting_at ? $surat->meeting_at->translatedFormat('l, d F Y — H:i') . ' WIB' : '-' }}<br>
                Tempat: {{ $surat->meeting_place ?: '-' }}
            </div>
        @endif

        @if ($surat->dokumen)
            <div class="dokumen">
                <b>Dokumen yang perlu disiapkan (dibawa saat meeting):</b>
                <ul>
                    @foreach ($surat->dokumen as $dok)
                        <li>{{ $dok }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p style="margin-top: 12px;">Demikian surat ini kami sampaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.</p>
    </div>

    <table class="ttd">
        <tr>
            <td class="kanan">
                {{ $kota }}, {{ now()->translatedFormat('d F Y') }}<br>
                @if (! empty($surat->penandatangan_jabatan)){{ $surat->penandatangan_jabatan }}@else Pimpinan @endif<br>
                <div class="nama">{{ $surat->penandatangan_nama ?: '(............................)' }}</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Surat ini diterbitkan melalui sistem {{ config('app.name', 'E-Booking') }} &middot; Ref: {{ $surat->nomor_surat }} &middot; {{ $surat->created_at?->format('d-m-Y H:i') }}
    </div>
</body>
</html>
