<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judul }} {{ $data['nomor_dokumen'] }}</title>
    @php
        $tgl = fn (?string $nilai) => $nilai ? \Illuminate\Support\Carbon::parse($nilai)->translatedFormat('d F Y') : '-';
        $dihentikan = $jenis === 'surat_pemberhentian_penempatan';
    @endphp
    <style>
        @page { margin: 40px 48px; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; line-height: 1.6; color: #222222; }
        .kop { border-bottom: 2px solid #00236f; padding-bottom: 10px; margin-bottom: 22px; }
        .kop .nama { font-size: 17px; font-weight: bold; color: #00236f; }
        .kop .sub { font-size: 9px; color: #555555; }
        h1 { font-size: 15px; text-align: center; margin: 0 0 2px; text-transform: uppercase; letter-spacing: 1px; text-decoration: underline; }
        .nomor { text-align: center; font-size: 10px; color: #444444; margin-bottom: 22px; }
        p { margin: 0 0 10px; text-align: justify; }
        table.info { width: 100%; margin: 4px 0 14px 18px; }
        table.info td { padding: 2px 0; vertical-align: top; }
        table.info td.label { width: 150px; color: #444444; }
        .alasan { border-left: 3px solid #b3261e; background: #fbeeee; padding: 8px 12px; margin: 4px 0 14px; }
        .alasan .judul { font-weight: bold; margin-bottom: 2px; }
        .tanggal { margin-top: 18px; }
        .blok-ttd { margin-top: 8px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .blok-ttd td { width: 40%; text-align: center; vertical-align: top; padding: 0 6px 14px; }
        .blok-ttd td.kosong { width: 60%; }
        .blok-ttd .peran { font-size: 10px; color: #444444; }
        .blok-ttd .kotak { height: 64px; margin: 4px 8px; border-bottom: 1px solid #222222; text-align: center; }
        .blok-ttd .status-kotak { display: inline-block; margin-top: 26px; font-size: 9px; color: #888888; font-style: italic; }
        .blok-ttd .nama { font-weight: bold; margin-top: 4px; }
        .blok-ttd .keterangan { font-size: 8.5px; color: #555555; }
        .catatan-kaki { margin-top: 28px; font-size: 8.5px; color: #777777; }
    </style>
</head>
<body>
    <div class="kop">
        <div class="nama">PT Intellisys IT Outsourcing</div>
        <div class="sub">Penyedia talent IT · Intellisys Talent Hub</div>
    </div>

    <h1>{{ $judul }}</h1>
    <div class="nomor">Nomor: {{ $data['nomor_dokumen'] }}</div>

    @if ($dihentikan)
        <p>Yang bertanda tangan di bawah ini, atas nama PT Intellisys IT Outsourcing, dengan ini menyatakan bahwa penempatan talent berikut <strong>dihentikan</strong>:</p>
    @else
        <p>Yang bertanda tangan di bawah ini, atas nama PT Intellisys IT Outsourcing, menerangkan bahwa talent berikut telah <strong>menyelesaikan masa penempatan</strong>:</p>
    @endif

    <table class="info">
        <tr><td class="label">Nama talent</td><td>: <strong>{{ $talent['nama_lengkap'] }}</strong></td></tr>
        <tr><td class="label">Posisi</td><td>: {{ $posisi }}</td></tr>
        <tr><td class="label">Klien</td><td>: {{ $klien->nama_perusahaan }}</td></tr>
        @if ($klien->alamat)
            <tr><td class="label">Alamat klien</td><td>: {{ $klien->alamat }}</td></tr>
        @endif
        <tr><td class="label">Periode penempatan</td><td>: {{ $tgl($tanggal_mulai) }} s.d. {{ $tanggal_selesai_kontrak ? $tgl($tanggal_selesai_kontrak) : 'tidak ditentukan' }}</td></tr>
        <tr><td class="label">{{ $dihentikan ? 'Dihentikan per tanggal' : 'Tanggal berakhir' }}</td><td>: <strong>{{ $tgl($tanggal_berakhir) }}</strong></td></tr>
    </table>

    @if ($dihentikan)
        <div class="alasan">
            <div class="judul">Alasan penghentian</div>
            <div>{!! nl2br(e($alasan ?: '-')) !!}</div>
        </div>
        <p>Sejak tanggal tersebut, hak dan kewajiban talent pada penempatan ini berakhir sesuai ketentuan kontrak kerja. Hal-hal yang belum diselesaikan akan dikoordinasikan oleh Account Manager bersama klien.</p>
    @else
        <p>Selama masa penempatan, talent tersebut telah menjalankan tugasnya pada posisi di atas. Surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
    @endif

    <div class="tanggal">Diterbitkan pada {{ $tgl($data['tanggal_dokumen']) }}</div>

    {{-- Blok tanda tangan Manager; gambar ditempel di kotak ber-id ttd-… saat dokumen diunduh (R28a). --}}
    <table class="blok-ttd">
        <tr>
            <td class="kosong"></td>
            <td>
                @if (empty($penandatangan))
                    <div class="peran">Manager, PT Intellisys IT Outsourcing</div>
                    <div class="kotak"><span class="status-kotak">Belum ada penandatangan</span></div>
                @else
                    @foreach ($penandatangan as $p)
                        <div class="peran">{{ $p['peran'] }}, PT Intellisys IT Outsourcing</div>
                        <div class="kotak" id="{{ $p['kotak'] }}">
                            @if ($p['status'] !== \App\Enums\StatusTtd::Ditandatangani)
                                <span class="status-kotak">{{ $p['label_status'] }}</span>
                            @endif
                        </div>
                        <div class="nama">{{ $p['nama'] }}</div>
                        <div class="keterangan">{{ $p['label_status'] }}@if ($p['waktu']) · {{ $p['waktu'] }}@endif</div>
                    @endforeach
                @endif
            </td>
        </tr>
    </table>

    <div class="catatan-kaki">Dokumen ini dibuat otomatis oleh Intellisys Talent Hub saat penempatan diakhiri. Tanda tangan digital dicatat di sistem.</div>
</body>
</html>
