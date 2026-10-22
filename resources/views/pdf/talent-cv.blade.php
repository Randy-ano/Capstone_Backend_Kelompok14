<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>CV - {{ $talent->nama_lengkap ?? 'Talent' }}</title>

    <style>
        @page {
            margin: 35px 45px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #222222;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .name {
            font-size: 23px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 6px;
        }

        .contact {
            font-size: 9px;
            color: #444444;
            line-height: 1.7;
        }

        .section {
            margin-top: 17px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #444444;
            padding-bottom: 4px;
            margin: 0 0 9px;
        }

        .item {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }

        .item-title {
            font-size: 10px;
            font-weight: bold;
            margin: 0;
        }

        .item-subtitle {
            font-size: 9px;
            color: #444444;
            margin: 1px 0 3px;
        }

        .item-description {
            margin: 3px 0 0;
            text-align: left;
        }

        .text {
            margin: 0;
            text-align: left;
            white-space: pre-line;
        }

        ul {
            margin: 4px 0 0 16px;
            padding: 0;
        }

        li {
            margin-bottom: 2px;
        }

        .skills {
            margin: 0;
        }

        .empty {
            color: #666666;
            font-style: italic;
        }

        .footer {
            margin-top: 22px;
            padding-top: 5px;
            border-top: 1px solid #dddddd;
            font-size: 8px;
            text-align: center;
            color: #777777;
        }
    </style>
</head>

<body>

    {{-- HEADER / IDENTITAS --}}
    <div class="header">
        <h1 class="name">
            {{ $talent->nama_lengkap ?? 'Nama Talent' }}
        </h1>

        <div class="contact">
            @if ($talent->domisili)
                {{ $talent->domisili }}
            @endif

            @if ($talent->nomor_telepon)
                {{ $talent->domisili ? ' | ' : '' }}
                {{ $talent->nomor_telepon }}
            @endif

            @if (optional($talent->user)->email)
                {{ ($talent->domisili || $talent->nomor_telepon) ? ' | ' : '' }}
                {{ $talent->user->email }}
            @endif

            @if ($talent->alamat)
                <br>{{ $talent->alamat }}
            @endif

            @if ($talent->linkedin)
                <br>LinkedIn: {{ $talent->linkedin }}
            @endif

            @if ($talent->portfolio)
                <br>Portfolio: {{ $talent->portfolio }}
            @endif

            @if ($talent->github)
                <br>GitHub: {{ $talent->github }}
            @endif
        </div>
    </div>


    {{-- PROFESSIONAL SUMMARY --}}
    @if ($talent->tentang_saya)
        <div class="section">
            <h2 class="section-title">Professional Summary</h2>

            <p class="text">{{ $talent->tentang_saya }}</p>
        </div>
    @endif


    {{-- WORK EXPERIENCE --}}
    <div class="section">
        <h2 class="section-title">Work Experience</h2>

        @forelse ($talent->pengalamanKerjaTalent as $pengalaman)
            <div class="item">
                <p class="item-title">
                    {{ $pengalaman->posisi ?? 'Posisi' }}
                </p>

                <p class="item-subtitle">
                    {{ $pengalaman->nama_perusahaan ?? 'Nama Perusahaan' }}

                    @if ($pengalaman->lokasi)
                        | {{ $pengalaman->lokasi }}
                    @endif
                </p>

                <p class="item-subtitle">
                    @if ($pengalaman->tanggal_mulai)
                        {{ \Carbon\Carbon::parse($pengalaman->tanggal_mulai)->format('M Y') }}
                    @endif

                    –

                    @if ($pengalaman->masih_bekerja)
                        Present
                    @elseif ($pengalaman->tanggal_selesai)
                        {{ \Carbon\Carbon::parse($pengalaman->tanggal_selesai)->format('M Y') }}
                    @else
                        -
                    @endif

                    @if ($pengalaman->jenis_pekerjaan)
                        | {{ $pengalaman->jenis_pekerjaan }}
                    @endif
                </p>

                @if ($pengalaman->deskripsi)
                    <p class="item-description">
                        {{ $pengalaman->deskripsi }}
                    </p>
                @endif
            </div>
        @empty
            <p class="empty">Work experience has not been added.</p>
        @endforelse
    </div>


    {{-- EDUCATION --}}
    <div class="section">
        <h2 class="section-title">Education</h2>

        @forelse ($talent->riwayatPendidikan as $pendidikan)
            <div class="item">
                <p class="item-title">
                    {{ $pendidikan->nama_institusi ?? 'Nama Institusi' }}
                </p>

                <p class="item-subtitle">
                    @if ($pendidikan->jenjang)
                        {{ $pendidikan->jenjang }}
                    @endif

                    @if ($pendidikan->jurusan)
                        {{ $pendidikan->jenjang ? ' - ' : '' }}
                        {{ $pendidikan->jurusan }}
                    @endif
                </p>

                <p class="item-subtitle">
                    @if ($pendidikan->tahun_mulai)
                        {{ $pendidikan->tahun_mulai }}
                    @endif

                    –

                    @if ($pendidikan->tahun_selesai)
                        {{ $pendidikan->tahun_selesai }}
                    @else
                        Present
                    @endif
                </p>

                @if ($pendidikan->deskripsi)
                    <p class="item-description">
                        {{ $pendidikan->deskripsi }}
                    </p>
                @endif
            </div>
        @empty
            {{-- Fallback jika data pendidikan hanya disimpan di tabel talent --}}
            @if ($talent->pendidikan || $talent->nama_institusi || $talent->jurusan)
                <div class="item">
                    <p class="item-title">
                        {{ $talent->nama_institusi ?? 'Institusi Pendidikan' }}
                    </p>

                    <p class="item-subtitle">
                        {{ $talent->pendidikan ?? '' }}

                        @if ($talent->jurusan)
                            {{ $talent->pendidikan ? ' - ' : '' }}
                            {{ $talent->jurusan }}
                        @endif
                    </p>

                    @if ($talent->tahun_lulus)
                        <p class="item-subtitle">
                            Graduated {{ $talent->tahun_lulus }}
                        </p>
                    @endif
                </div>
            @else
                <p class="empty">Education has not been added.</p>
            @endif
        @endforelse
    </div>


    {{-- SKILLS / COMPETENCIES --}}
    <div class="section">
        <h2 class="section-title">Skills</h2>

        @forelse ($talent->kompetensi as $kompetensi)
            <p class="skills">
                {{ $kompetensi->nama_kompetensi
                    ?? $kompetensi->nama
                    ?? 'Kompetensi' }}

                @if (isset($kompetensi->pivot) && $kompetensi->pivot->tingkat_kemampuan)
                    — {{ $kompetensi->pivot->tingkat_kemampuan }}
                @endif
            </p>
        @empty
            <p class="empty">Skills have not been added.</p>
        @endforelse
    </div>


    {{-- LANGUAGES --}}
    @if ($talent->bahasa->isNotEmpty())
        <div class="section">
            <h2 class="section-title">Languages</h2>

            @foreach ($talent->bahasa as $itemBahasa)
                <p class="skills">
                    {{ $itemBahasa->nama_bahasa
                        ?? $itemBahasa->nama
                        ?? 'Language' }}

                    @if (isset($itemBahasa->pivot) && $itemBahasa->pivot->tingkat_kemampuan)
                        — {{ $itemBahasa->pivot->tingkat_kemampuan }}
                    @endif
                </p>
            @endforeach
        </div>
    @endif


    {{-- CERTIFICATIONS --}}
    @if ($talent->sertifikat->isNotEmpty())
        <div class="section">
            <h2 class="section-title">Certifications</h2>

            @foreach ($talent->sertifikat as $sertifikat)
                <div class="item">
                    <p class="item-title">
                        {{ $sertifikat->nama_sertifikat ?? 'Certificate' }}
                    </p>

                    @if ($sertifikat->penerbit)
                        <p class="item-subtitle">
                            {{ $sertifikat->penerbit }}
                        </p>
                    @endif

                    @if ($sertifikat->tanggal_terbit)
                        <p class="item-subtitle">
                            Issued:
                            {{ \Carbon\Carbon::parse($sertifikat->tanggal_terbit)->format('M Y') }}

                            @if ($sertifikat->tanggal_kedaluwarsa)
                                | Valid until:
                                {{ \Carbon\Carbon::parse($sertifikat->tanggal_kedaluwarsa)->format('M Y') }}
                            @endif
                        </p>
                    @endif

                    @if ($sertifikat->url_sertifikat)
                        <p class="item-subtitle">
                            {{ $sertifikat->url_sertifikat }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif


    {{-- ADDITIONAL INFORMATION --}}
    @if ($talent->gaji_harapan)
        <div class="section">
            <h2 class="section-title">Additional Information</h2>

            <p>
                Expected Salary:
                Rp{{ number_format((float) $talent->gaji_harapan, 0, ',', '.') }}
            </p>
        </div>
    @endif

    <div class="footer">
        Generated by Intellisys Talent Hub
    </div>

</body>
</html>