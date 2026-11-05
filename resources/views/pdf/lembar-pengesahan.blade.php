<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Pengesahan {{ $namaFile }}</title>
    <style>
        @page { margin: 40px 48px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; line-height: 1.5; color: #222222; }
        .kop { border-bottom: 2px solid #00236f; padding-bottom: 10px; margin-bottom: 18px; }
        .kop .nama { font-size: 17px; font-weight: bold; color: #00236f; }
        .kop .sub { font-size: 9px; color: #555555; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 14px; text-transform: uppercase; letter-spacing: 1px; }
        table.info { width: 100%; margin-bottom: 16px; }
        table.info td { padding: 2px 0; vertical-align: top; }
        table.info td.label { width: 120px; color: #555555; }
        table.ttd { width: 100%; border-collapse: collapse; }
        table.ttd th { background: #e5eeff; text-align: left; padding: 6px; border: 1px solid #c5c5d3; font-size: 10px; }
        table.ttd td { padding: 6px; border: 1px solid #c5c5d3; vertical-align: middle; }
        table.ttd tr { page-break-inside: avoid; }
        .kotak { width: 150px; height: 60px; text-align: center; }
        .kosong { display: inline-block; margin-top: 22px; font-size: 9px; color: #888888; font-style: italic; }
        .nama { font-weight: bold; }
        .peran { font-size: 9.5px; color: #555555; }
        .status-ditandatangani { color: #1b6b3a; font-weight: bold; }
        .status-ditolak { color: #ba1a1a; font-weight: bold; }
        .status-menunggu { color: #8a5a00; font-weight: bold; }
        .catatan-kaki { margin-top: 24px; font-size: 8.5px; color: #777777; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="nama">PT Intellisys IT Outsourcing</div>
        <div class="sub">Penyedia talent IT · Intellisys Talent Hub</div>
    </div>

    <h1>Lembar Pengesahan</h1>

    <table class="info">
        <tr><td class="label">Dokumen</td><td>: {{ $namaFile }}</td></tr>
        <tr><td class="label">Jenis</td><td>: {{ $judul }}</td></tr>
        @if ($dokumen->klien)
            <tr><td class="label">Klien</td><td>: {{ $dokumen->klien->nama_perusahaan }}</td></tr>
        @endif
        @if ($dokumen->talent)
            <tr><td class="label">Talent</td><td>: {{ $dokumen->talent->nama_lengkap }}</td></tr>
        @endif
        <tr><td class="label">Tanggal unggah</td><td>: {{ $dokumen->tanggal_unggah?->timezone(config('app.timezone'))->translatedFormat('d F Y, H:i') }} WIB</td></tr>
    </table>

    <table class="ttd">
        <thead>
            <tr>
                <th style="width: 24px;">No</th>
                <th>Penandatangan</th>
                <th style="width: 158px;">Tanda tangan</th>
                <th style="width: 104px;">Tanggal &amp; jam</th>
                <th style="width: 112px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($penandatangan as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="nama">{{ $p['nama'] }}</div>
                        <div class="peran">{{ $p['peran'] }}</div>
                    </td>
                    <td><div class="kotak" id="{{ $p['kotak'] }}">@if ($p['status'] !== \App\Enums\StatusTtd::Ditandatangani)<span class="kosong">{{ $p['label_status'] }}</span>@endif</div></td>
                    <td>{{ $p['waktu'] ?? '-' }}</td>
                    <td class="status-{{ $p['status']->value }}">{{ $p['label_status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="catatan-kaki">Lembar ini ditambahkan otomatis oleh Intellisys Talent Hub saat dokumen diunduh. Tanda tangan digital tercatat di sistem.</div>
</body>
</html>
