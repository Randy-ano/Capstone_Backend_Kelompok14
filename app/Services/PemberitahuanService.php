<?php

namespace App\Services;

use App\Enums\StatusCuti;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusPengajuan;
use App\Models\Dokumen;
use App\Models\EvaluasiKandidat;
use App\Models\JadwalInterview;
use App\Models\LaporanKeluhan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanKandidat;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * FR-16, R30: isi pesan & penerima untuk setiap event. Dipanggil service terkait setelah aksi berhasil.
 * Semua lewat NotifikasiService (kanal sistem).
 */
class PemberitahuanService
{
    public function __construct(private readonly NotifikasiService $notifikasi) {}

    /** Pengajuan kandidat dibuat → HR Client klien. */
    public function pengajuanDibuat(PengajuanKandidat $p): void
    {
        $p->loadMissing(['talent', 'jobRequirement']);
        $this->notifikasi->kirimKe(
            NotifikasiService::hrKlien($p->jobRequirement->id_klien),
            'pengajuan_dibuat',
            "Kandidat baru diajukan untuk posisi {$p->jobRequirement->posisi}: {$p->talent->nama_lengkap}. Silakan tinjau.",
        );
    }

    /** Status pengajuan berubah → AM pengaju & talent. */
    public function statusPengajuan(PengajuanKandidat $p): void
    {
        $p->loadMissing(['talent.user', 'jobRequirement', 'accountManager.user']);
        $hasil = $p->status_pengajuan === StatusPengajuan::LanjutInterview ? 'dilanjutkan ke tahap interview' : 'ditolak oleh klien';
        $this->notifikasi->kirimKe(
            [$p->accountManager?->user, $p->talent->user],
            'pengajuan_diperbarui',
            "Pengajuan {$p->talent->nama_lengkap} untuk posisi {$p->jobRequirement->posisi} {$hasil}.",
        );
    }

    /** Interview dijadwalkan/diubah → talent & HR Client klien. */
    public function interview(JadwalInterview $iv, string $aksi): void
    {
        $iv->loadMissing(['pengajuan.talent.user', 'pengajuan.jobRequirement']);
        $p = $iv->pengajuan;
        $jadwal = Carbon::parse($iv->tanggal_interview)->translatedFormat('d F Y').' pukul '.substr($iv->waktu_interview, 0, 5);
        $pesan = match ($aksi) {
            'dijadwalkan' => "Interview {$p->talent->nama_lengkap} untuk posisi {$p->jobRequirement->posisi} dijadwalkan {$jadwal}.",
            'dijadwal_ulang' => "Interview {$p->talent->nama_lengkap} untuk posisi {$p->jobRequirement->posisi} dijadwal ulang menjadi {$jadwal}.",
            default => "Interview {$p->talent->nama_lengkap} untuk posisi {$p->jobRequirement->posisi} ({$jadwal}) {$aksi}.",
        };
        $this->notifikasi->kirimKe(
            [$p->talent->user, ...NotifikasiService::hrKlien($p->jobRequirement->id_klien)],
            $aksi === 'dijadwalkan' ? 'interview_dijadwalkan' : 'interview_diubah',
            $pesan,
        );
    }

    /** Evaluasi disimpan → AM pengaju & TS yang menyaring. */
    public function evaluasiDisimpan(EvaluasiKandidat $ev): void
    {
        $ev->loadMissing(['pengajuan.talent', 'pengajuan.jobRequirement', 'pengajuan.accountManager.user', 'pengajuan.screening.talentSupport.user']);
        $p = $ev->pengajuan;
        $hasil = $ev->status_evaluasi === StatusEvaluasi::Diterima ? 'diterima' : 'ditolak';
        $this->notifikasi->kirimKe(
            [$p->accountManager?->user, $p->screening?->talentSupport?->user],
            'evaluasi_disimpan',
            "Hasil evaluasi klien: {$p->talent->nama_lengkap} {$hasil} untuk posisi {$p->jobRequirement->posisi} (nilai {$ev->nilai_score}).",
        );
    }

    /** Placement dibuat → talent & HR Client klien. */
    public function placementDibuat(Placement $pl): void
    {
        $pl->loadMissing(['talent.user', 'klien']);
        $mulai = $pl->tanggal_mulai->translatedFormat('d F Y');
        $this->notifikasi->kirimKe(
            [$pl->talent->user, ...NotifikasiService::hrKlien($pl->id_klien)],
            'placement_dibuat',
            "{$pl->talent->nama_lengkap} ditempatkan di {$pl->klien->nama_perusahaan} sebagai {$pl->posisi_penempatan} mulai {$mulai}.",
        );
    }

    /** Cuti diajukan → HR Client klien & Manager. */
    public function cutiDiajukan(PengajuanCuti $c): void
    {
        $c->loadMissing(['talent', 'placement']);
        $this->notifikasi->kirimKe(
            [...NotifikasiService::hrKlien($c->placement->id_klien), ...NotifikasiService::managerAktif()],
            'cuti_diajukan',
            "{$c->talent->nama_lengkap} mengajukan cuti {$this->rentang($c)}. Mohon beri keputusan.",
        );
    }

    /** Cuti diputuskan (status akhir) → talent. */
    public function cutiDiputuskan(PengajuanCuti $c): void
    {
        if ($c->status_cuti === StatusCuti::Menunggu) {
            return;
        }
        $c->loadMissing('talent.user');
        $this->notifikasi->kirim(
            $c->talent->user,
            'cuti_diputuskan',
            "Pengajuan cuti Anda {$this->rentang($c)} {$c->status_cuti->value}.",
        );
    }

    /** Laporan keluhan baru → AM klien (AM job requirement placement) & Manager. */
    public function keluhanBaru(LaporanKeluhan $l): void
    {
        $l->loadMissing(['placement.talent', 'placement.klien', 'placement.jobRequirement.accountManager.user']);
        $pl = $l->placement;
        $this->notifikasi->kirimKe(
            [$pl->jobRequirement?->accountManager?->user, ...NotifikasiService::managerAktif()],
            'keluhan_baru',
            "Keluhan baru dari {$pl->klien->nama_perusahaan} terkait {$pl->talent->nama_lengkap}: {$l->judul_laporan}.",
        );
    }

    /**
     * Permintaan tanda tangan → penandatangan.
     *
     * @param  iterable<User>  $penandatangan
     */
    public function mintaTandaTangan(Dokumen $d, iterable $penandatangan): void
    {
        $this->notifikasi->kirimKe(
            $penandatangan,
            'tanda_tangan_diminta',
            'Anda diminta menandatangani dokumen '.DokumenService::namaFile($d).'.',
        );
    }

    private function rentang(PengajuanCuti $c): string
    {
        $mulai = $c->tanggal_mulai->translatedFormat('d F Y');

        return $c->tanggal_mulai->equalTo($c->tanggal_selesai) ? $mulai : "{$mulai} – {$c->tanggal_selesai->translatedFormat('d F Y')}";
    }
}
