<?php

namespace App\Services;

use App\Enums\IndikatorSla;
use App\Enums\KanalNotifikasi;
use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusDokumen;
use App\Enums\StatusInterview;
use App\Enums\StatusReq;
use App\Enums\StatusTtd;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\Notifikasi;
use App\Models\PengajuanCuti;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-16, R31: pengingat harian (07.00 WIB). Dikirim lewat kanal email (queue) dan tercatat di notifikasi.
 * Idempoten per hari: pengingat yang sama untuk penerima yang sama tidak dikirim dua kali di hari yang sama.
 */
class PengingatService
{
    public function __construct(
        private readonly NotifikasiService $notifikasi,
        private readonly ProgresService $progres,
    ) {}

    /**
     * @return array{sla: int, interview: int, cuti: int, tanda_tangan: int, terjadwal: int}
     */
    public function jalankan(): array
    {
        return [
            'sla' => $this->sla(),
            'interview' => $this->interviewBesok(),
            'cuti' => $this->cutiTertunda(),
            'tanda_tangan' => $this->tandaTanganTertunda(),
            'terjadwal' => $this->notifikasi->kirimTerjadwal(),
        ];
    }

    /** Job SLA mendekati/terlambat → AM penanggung jawab & Manager. */
    private function sla(): int
    {
        $jumlah = 0;
        foreach ([IndikatorSla::Terlambat, IndikatorSla::Mendekati] as $indikator) {
            $jobs = $this->progres->filterIndikatorSla(JobRequirement::query(), $indikator)
                ->whereIn('status_req', [StatusReq::Diajukan, StatusReq::Diproses])
                ->with(['klien', 'accountManager.user'])
                ->get();
            foreach ($jobs as $job) {
                $deadline = $job->sla_deadline->translatedFormat('d F Y H:i');
                $pesan = $indikator === IndikatorSla::Terlambat
                    ? "SLA permintaan {$job->posisi} ({$job->klien->nama_perusahaan}) sudah terlambat sejak {$deadline}."
                    : "SLA permintaan {$job->posisi} ({$job->klien->nama_perusahaan}) jatuh tempo {$deadline}.";
                $jumlah += $this->kirim([$job->accountManager?->user, ...NotifikasiService::managerAktif()], "sla_{$indikator->value}", $pesan);
            }
        }

        return $jumlah;
    }

    /** Interview besok → talent & HR Client klien. */
    private function interviewBesok(): int
    {
        $jumlah = 0;
        $interviews = JadwalInterview::query()
            ->whereDate('tanggal_interview', now()->addDay()->toDateString())
            ->whereIn('status_interview', [StatusInterview::Dijadwalkan, StatusInterview::DijadwalUlang])
            ->with(['pengajuan.talent.user', 'pengajuan.jobRequirement'])
            ->get();
        foreach ($interviews as $iv) {
            $p = $iv->pengajuan;
            $pesan = "Pengingat: interview {$p->talent->nama_lengkap} untuk posisi {$p->jobRequirement->posisi} besok pukul ".substr($iv->waktu_interview, 0, 5).'.';
            $jumlah += $this->kirim([$p->talent->user, ...NotifikasiService::hrKlien($p->jobRequirement->id_klien)], 'pengingat_interview', $pesan);
        }

        return $jumlah;
    }

    /** Cuti menunggu > 2 hari → approver yang belum memutuskan. */
    private function cutiTertunda(): int
    {
        $jumlah = 0;
        $cuti = PengajuanCuti::query()
            ->where('status_cuti', StatusCuti::Menunggu)
            ->where('created_at', '<', now()->subDays(2))
            ->with(['talent', 'placement'])
            ->get();
        foreach ($cuti as $c) {
            $penerima = [
                ...($c->status_approval_klien === StatusApproval::Menunggu ? NotifikasiService::hrKlien($c->placement->id_klien) : []),
                ...($c->status_approval_manager === StatusApproval::Menunggu ? NotifikasiService::managerAktif() : []),
            ];
            $pesan = "Pengingat: cuti {$c->talent->nama_lengkap} mulai {$c->tanggal_mulai->translatedFormat('d F Y')} masih menunggu keputusan Anda.";
            $jumlah += $this->kirim($penerima, 'pengingat_cuti', $pesan);
        }

        return $jumlah;
    }

    /** Tanda tangan menunggu > 2 hari → penandatangan. */
    private function tandaTanganTertunda(): int
    {
        $jumlah = 0;
        $ttd = TandaTanganDokumen::query()
            ->where('status_ttd', StatusTtd::Menunggu)
            ->where('created_at', '<', now()->subDays(2))
            ->whereHas('dokumen', fn (Builder $q) => $q->where('status_dokumen', StatusDokumen::MenungguTtd))
            ->with(['dokumen', 'penandatangan'])
            ->get();
        foreach ($ttd as $t) {
            $pesan = 'Pengingat: dokumen '.DokumenService::namaFile($t->dokumen).' masih menunggu tanda tangan Anda.';
            $jumlah += $this->kirim([$t->penandatangan], 'pengingat_tanda_tangan', $pesan);
        }

        return $jumlah;
    }

    /**
     * Kirim lewat email kecuali penerima sudah menerima pesan yang sama hari ini.
     *
     * @param  iterable<User|null>  $penerima
     */
    private function kirim(iterable $penerima, string $tipe, string $pesan): int
    {
        $jumlah = 0;
        foreach (collect($penerima)->filter()->unique('id_user') as $user) {
            $sudah = Notifikasi::where('id_user_penerima', $user->id_user)
                ->where('tipe_notifikasi', $tipe)
                ->where('pesan', $pesan)
                ->whereDate('created_at', now()->toDateString())
                ->exists();
            if (! $sudah) {
                $this->notifikasi->kirim($user, $tipe, $pesan, KanalNotifikasi::Email);
                $jumlah++;
            }
        }

        return $jumlah;
    }
}
