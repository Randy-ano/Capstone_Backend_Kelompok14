<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} {{ $data['nomor_dokumen'] }}</title>
    <style>
        @page { margin: 40px 48px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; line-height: 1.5; color: #222222; }
        .kop { border-bottom: 2px solid #00236f; padding-bottom: 10px; margin-bottom: 18px; }
        .kop .nama { font-size: 17px; font-weight: bold; color: #00236f; }
        .kop .sub { font-size: 9px; color: #555555; }
        h1 { font-size: 16px; text-align: center; margin: 0 0 4px; text-transform: uppercase; letter-spacing: 1px; }
        .nomor { text-align: center; font-size: 10px; color: #444444; margin-bottom: 18px; }
        table.info { width: 100%; margin-bottom: 14px; }
        table.info td { padding: 2px 0; vertical-align: top; }
        table.info td.label { width: 120px; color: #555555; }
        table.item { width: 100%; border-collapse: collapse; margin: 8px 0 14px; }
        table.item th { background: #e5eeff; text-align: left; padding: 6px; border: 1px solid #c5c5d3; font-size: 10px; }
        table.item td { padding: 6px; border: 1px solid #c5c5d3; }
        .kanan { text-align: right; }
        .total td { font-weight: bold; background: #f8f9ff; }
        .bagian { margin-top: 14px; }
        .bagian .judul { font-weight: bold; margin-bottom: 4px; }
        .ttd { margin-top: 40px; width: 100%; }
        .ttd td { width: 50%; text-align: center; vertical-align: top; }
        .ttd .garis { margin-top: 60px; border-top: 1px solid #222222; display: inline-block; width: 70%; padding-top: 4px; }
        .catatan-kaki { margin-top: 24px; font-size: 8.5px; color: #777777; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="nama">PT Intellisys IT Outsourcing</div>
        <div class="sub">Penyedia talent IT · Intellisys Talent Hub</div>
    </div>

    <h1>{{ $judul }}</h1>
    <div class="nomor">Nomor: {{ $data['nomor_dokumen'] }}</div>

    <table class="info">
        <tr><td class="label">Kepada</td><td>: {{ $klien->nama_perusahaan }}</td></tr>
        @if ($klien->alamat)
            <tr><td class="label">Alamat</td><td>: {{ $klien->alamat }}</td></tr>
        @endif
        @if ($klien->penanggung_jawab)
            <tr><td class="label">Up.</td><td>: {{ $klien->penanggung_jawab }}</td></tr>
        @endif
        <tr><td class="label">Tanggal</td><td>: {{ \Illuminate\Support\Carbon::parse($data['tanggal_dokumen'])->translatedFormat('d F Y') }}</td></tr>
        <tr><td class="label">Perihal</td><td>: {{ $data['perihal'] }}</td></tr>
        @if (! empty($data['berlaku_sampai']))
            <tr><td class="label">Berlaku sampai</td><td>: {{ \Illuminate\Support\Carbon::parse($data['berlaku_sampai'])->translatedFormat('d F Y') }}</td></tr>
        @endif
    </table>

    @if ($items->isNotEmpty())
        <table class="item">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Deskripsi</th>
                    <th class="kanan" style="width: 60px;">Jumlah</th>
                    <th class="kanan" style="width: 110px;">Harga Satuan</th>
                    <th class="kanan" style="width: 120px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item['deskripsi'] }}</td>
                        <td class="kanan">{{ $item['jumlah'] }}</td>
                        <td class="kanan">Rp{{ number_format($item['harga_satuan'], 0, ',', '.') }}</td>
                        <td class="kanan">Rp{{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="4" class="kanan">Total</td>
                    <td class="kanan">Rp{{ number_format($total, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if (! empty($data['ketentuan']))
        <div class="bagian">
            <div class="judul">Ketentuan</div>
            <div>{!! nl2br(e($data['ketentuan'])) !!}</div>
        </div>
    @endif

    @if (! empty($data['catatan']))
        <div class="bagian">
            <div class="judul">Catatan</div>
            <div>{!! nl2br(e($data['catatan'])) !!}</div>
        </div>
    @endif

    <table class="ttd">
        <tr>
            <td>
                PT Intellisys IT Outsourcing
                <div><span class="garis">{{ $pembuat }}</span></div>
            </td>
            <td>
                {{ $klien->nama_perusahaan }}
                <div><span class="garis">{{ $klien->penanggung_jawab ?? 'Perwakilan klien' }}</span></div>
            </td>
        </tr>
    </table>

    <div class="catatan-kaki">Dokumen ini dibuat melalui Intellisys Talent Hub. Tanda tangan digital dicatat di sistem.</div>
</body>
</html>
