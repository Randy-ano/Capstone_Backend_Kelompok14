<?php

namespace App\Services;

use App\Enums\HasilScreening;
use App\Enums\IndikatorSla;
use App\Enums\Role;
use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusDokumen;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusLaporan;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Enums\StatusTtd;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\LaporanKeluhan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanKandidat;
use App\Models\Placement;
use App\Models\ScreeningTalent;
use App\Models\TalentPool;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * FR-20, R32: agregasi dashboard dari tabel yang ada (tanpa tabel baru).
 */
class DashboardService
{
    /** Status job yang masih berjalan. */
    private const JOB_AKTIF = [StatusReq::Diajukan, StatusReq::Diproses];

    public function __construct(
        private readonly ProgresService $progres,
        private readonly NotifikasiService $notifikasi,
    ) {}

    /**
     * R32: dashboard Manager.
     *
     * @return array<string, mixed>
     */
    public function manager(): array
    {
        $perStatus = JobRequirement::query()->selectRaw('status_req, count(*) as jumlah')->groupBy('status_req')->pluck('jumlah', 'status_req');

        return [
            'job_requirement' => [
                'total' => (int) $perStatus->sum(),
                'per_status' => collect(StatusReq::cases())->mapWithKeys(fn (StatusReq $s) => [$s->value => (int) ($perStatus[$s->value] ?? 0)])->all(),
            ],
            'sla' => [
                'terlambat' => $this->jumlahSla(IndikatorSla::Terlambat),
                'mendekati' => $this->jumlahSla(IndikatorSla::Mendekati),
            ],
            'kandidat_per_tahapan' => $this->kandidatPerTahapan(),
            'placement_aktif' => Placement::where('status_placement', StatusPlacement::Aktif)->count(),
            'laporan_keluhan_terbuka' => LaporanKeluhan::whereIn('status_laporan', [StatusLaporan::Baru, StatusLaporan::Diproses])->count(),
            'tren_placement' => $this->trenPlacement(),
        ];
    }

    /**
     * Posisi kandidat saat ini di setiap tahap (satu kandidat dihitung di tahap terakhirnya).
     *
     * @return list<array{tahap: string, label: string, jumlah: int}>
     */
    public function kandidatPerTahapan(): array
    {
        $tahap = [
            ['tersedia', 'Tersedia di talent pool', TalentPool::where('status_ketersediaan', StatusKetersediaan::Tersedia)
                ->whereHas('talent', fn (Builder $q) => $q->where('status_kandidat', StatusKandidat::Tersedia))->count()],
            ['screening', 'Sedang di-screening', ScreeningTalent::where('hasil_screening', HasilScreening::Pending)
                ->whereHas('jobRequirement', fn (Builder $q) => $q->whereIn('status_req', self::JOB_AKTIF))->count()],
            ['lolos_screening', 'Lolos screening', ScreeningTalent::where('hasil_screening', HasilScreening::Lolos)->doesntHave('pengajuan')
                ->whereHas('jobRequirement', fn (Builder $q) => $q->whereIn('status_req', self::JOB_AKTIF))->count()],
            ['diajukan', 'Diajukan ke klien', PengajuanKandidat::where('status_pengajuan', StatusPengajuan::Diajukan)->count()],
            ['interview', 'Tahap interview', PengajuanKandidat::where('status_pengajuan', StatusPengajuan::LanjutInterview)->doesntHave('evaluasi')->count()],
            ['diterima', 'Diterima, menunggu placement', PengajuanKandidat::whereHas('evaluasi', fn (Builder $q) => $q->where('status_evaluasi', StatusEvaluasi::Diterima)->doesntHave('placement'))->count()],
            ['ditempatkan', 'Ditempatkan', Placement::where('status_placement', StatusPlacement::Aktif)->count()],
        ];

        return array_map(fn (array $t) => ['tahap' => $t[0], 'label' => $t[1], 'jumlah' => $t[2]], $tahap);
    }

    /**
     * Jumlah placement per bulan mulai (12 bulan terakhir termasuk bulan ini). Dikelompokkan di PHP agar tidak bergantung fungsi tanggal MySQL/SQLite.
     *
     * @return list<array{bulan: string, label: string, jumlah: int}>
     */
    public function trenPlacement(): array
    {
        $awal = now()->startOfMonth()->subMonths(11);
        $perBulan = Placement::whereDate('tanggal_mulai', '>=', $awal->toDateString())
            ->pluck('tanggal_mulai')
            ->countBy(fn (Carbon $t) => $t->format('Y-m'));

        return collect(range(0, 11))->map(function (int $i) use ($awal, $perBulan) {
            $bulan = $awal->copy()->addMonths($i);

            return ['bulan' => $bulan->format('Y-m'), 'label' => $bulan->translatedFormat('M Y'), 'jumlah' => (int) ($perBulan[$bulan->format('Y-m')] ?? 0)];
        })->all();
    }

    /**
     * Kartu ringkasan beranda per role. `tujuan` = path halaman frontend terkait.
     *
     * @return array{kartu: list<array{kunci: string, label: string, nilai: int|string, keterangan: string|null, tujuan: string|null}>, notifikasi_belum_dibaca: int}
     */
    public function ringkasan(User $user): array
    {
        $kartu = match ($user->role) {
            Role::Manager => $this->kartuManager($user),
            Role::AccountManager => $this->kartuAm($user),
            Role::TalentSupport => $this->kartuTs(),
            Role::HrClient => $this->kartuHr($user),
            Role::Talent => $this->kartuTalent($user),
        };

        return ['kartu' => $kartu, 'notifikasi_belum_dibaca' => $this->notifikasi->jumlahBelumDibaca($user)];
    }

    private function kartuManager(User $user): array
    {
        return [
            $this->kartu('job_aktif', 'Permintaan berjalan', $this->jobAktif()->count(), 'diajukan & diproses', '/permintaan-kebutuhan'),
            $this->kartu('sla_terlambat', 'SLA terlambat', $this->jumlahSla(IndikatorSla::Terlambat), "{$this->jumlahSla(IndikatorSla::Mendekati)} mendekati deadline", '/pipeline-rekrutmen'),
            $this->kartu('placement_aktif', 'Talent ditempatkan', Placement::where('status_placement', StatusPlacement::Aktif)->count(), 'placement aktif', '/penugasan-penempatan'),
            $this->kartu('cuti_menunggu', 'Cuti menunggu keputusan', PengajuanCuti::where('status_cuti', StatusCuti::Menunggu)->where('status_approval_manager', StatusApproval::Menunggu)->count(), 'perlu persetujuan Anda', '/pengajuan-cuti-shift'),
            $this->kartu('keluhan_terbuka', 'Keluhan terbuka', LaporanKeluhan::whereIn('status_laporan', [StatusLaporan::Baru, StatusLaporan::Diproses])->count(), 'baru & diproses', '/tiket-komplain-klien'),
            $this->kartu('ttd_menunggu', 'Perlu tanda tangan Anda', $this->ttdMenunggu($user), 'dokumen', '/dokumen-quotation-po'),
        ];
    }

    private function kartuAm(User $user): array
    {
        return [
            $this->kartu('job_aktif', 'Permintaan berjalan', $this->jobAktif()->count(), 'diajukan & diproses', '/permintaan-kebutuhan'),
            $this->kartu('siap_diajukan', 'Siap diajukan ke klien', ScreeningTalent::where('hasil_screening', HasilScreening::Lolos)->doesntHave('pengajuan')
                ->whereHas('jobRequirement', fn (Builder $q) => $q->where('status_req', StatusReq::Diproses))->count(), 'lolos screening', '/pengajuan-kandidat'),
            $this->kartu('menunggu_klien', 'Menunggu tinjauan klien', PengajuanKandidat::where('status_pengajuan', StatusPengajuan::Diajukan)->count(), 'pengajuan', '/pengajuan-kandidat'),
            $this->kartu('interview_mendatang', 'Interview 7 hari ke depan', $this->interviewMendatang()->count(), null, '/jadwal-interview'),
            $this->kartu('keluhan_terbuka', 'Keluhan terbuka', LaporanKeluhan::whereIn('status_laporan', [StatusLaporan::Baru, StatusLaporan::Diproses])->count(), 'perlu tindak lanjut', '/tiket-komplain-klien'),
            $this->kartu('ttd_menunggu', 'Perlu tanda tangan Anda', $this->ttdMenunggu($user), 'dokumen', '/dokumen-quotation-po'),
        ];
    }

    private function kartuTs(): array
    {
        return [
            $this->kartu('talent_tersedia', 'Talent tersedia', TalentPool::where('status_ketersediaan', StatusKetersediaan::Tersedia)
                ->whereHas('talent', fn (Builder $q) => $q->where('status_kandidat', StatusKandidat::Tersedia))->count(), 'di talent pool', '/talent-pool'),
            $this->kartu('job_terbuka', 'Permintaan perlu kandidat', $this->jobAktif()->count(), 'diajukan & diproses', '/rekomendasi-kandidat'),
            $this->kartu('screening_pending', 'Screening belum diputuskan', ScreeningTalent::where('hasil_screening', HasilScreening::Pending)
                ->whereHas('jobRequirement', fn (Builder $q) => $q->whereIn('status_req', self::JOB_AKTIF))->count(), null, '/rekomendasi-kandidat'),
            $this->kartu('menunggu_placement', 'Menunggu placement', PengajuanKandidat::whereHas('evaluasi', fn (Builder $q) => $q->where('status_evaluasi', StatusEvaluasi::Diterima)->doesntHave('placement'))->count(), 'evaluasi diterima', '/pengajuan-kandidat'),
        ];
    }

    private function kartuHr(User $user): array
    {
        $idKlien = $user->idKlien();
        $milikKlien = fn (Builder $q) => $q->where('id_klien', $idKlien);

        return [
            $this->kartu('job_aktif', 'Permintaan berjalan', $this->jobAktif()->where('id_klien', $idKlien)->count(), 'diajukan & diproses', '/permintaan-kebutuhan'),
            $this->kartu('perlu_ditinjau', 'Kandidat perlu ditinjau', PengajuanKandidat::where('status_pengajuan', StatusPengajuan::Diajukan)->whereHas('jobRequirement', $milikKlien)->count(), null, '/pengajuan-kandidat'),
            $this->kartu('interview_mendatang', 'Interview 7 hari ke depan', $this->interviewMendatang()->whereHas('pengajuan.jobRequirement', $milikKlien)->count(), null, '/jadwal-interview'),
            $this->kartu('placement_aktif', 'Talent ditempatkan', Placement::where('status_placement', StatusPlacement::Aktif)->where('id_klien', $idKlien)->count(), 'di perusahaan Anda', '/penugasan-penempatan'),
            $this->kartu('cuti_menunggu', 'Cuti menunggu keputusan', PengajuanCuti::where('status_cuti', StatusCuti::Menunggu)->where('status_approval_klien', StatusApproval::Menunggu)->whereHas('placement', $milikKlien)->count(), 'perlu persetujuan Anda', '/pengajuan-cuti-shift'),
            $this->kartu('ttd_menunggu', 'Perlu tanda tangan Anda', $this->ttdMenunggu($user), 'dokumen', '/dokumen-quotation-po'),
        ];
    }

    private function kartuTalent(User $user): array
    {
        $talent = $user->talent;
        $placement = $talent?->placements()->where('status_placement', StatusPlacement::Aktif)->with('klien')->first();
        $lengkap = $talent?->status_kelengkapan === StatusKelengkapan::Lengkap;

        return [
            $this->kartu('status_profil', 'Status profil', $lengkap ? 'Lengkap' : 'Belum lengkap', $lengkap ? 'masuk talent pool' : 'lengkapi agar masuk talent pool', '/profil-saya'),
            $this->kartu('status_kandidat', 'Status kandidat', $placement ? 'Ditempatkan' : ($talent?->status_kandidat === StatusKandidat::DalamProses ? 'Dalam proses' : 'Tersedia'), $placement ? "{$placement->posisi_penempatan} di {$placement->klien->nama_perusahaan}" : null, $placement ? '/penugasan-penempatan' : '/pengajuan-kandidat'),
            $this->kartu('interview_mendatang', 'Interview 7 hari ke depan', $this->interviewMendatang()->whereHas('pengajuan', fn (Builder $q) => $q->where('id_talent', $talent?->id_talent))->count(), null, '/jadwal-interview'),
            $this->kartu('cuti_menunggu', 'Cuti menunggu persetujuan', PengajuanCuti::where('id_talent', $talent?->id_talent)->where('status_cuti', StatusCuti::Menunggu)->count(), null, '/pengajuan-cuti-shift'),
        ];
    }

    private function kartu(string $kunci, string $label, int|string $nilai, ?string $keterangan, ?string $tujuan): array
    {
        return compact('kunci', 'label', 'nilai', 'keterangan', 'tujuan');
    }

    private function jobAktif(): Builder
    {
        return JobRequirement::whereIn('status_req', self::JOB_AKTIF);
    }

    private function jumlahSla(IndikatorSla $indikator): int
    {
        return $this->progres->filterIndikatorSla($this->jobAktif(), $indikator)->count();
    }

    private function interviewMendatang(): Builder
    {
        return JadwalInterview::whereIn('status_interview', [StatusInterview::Dijadwalkan, StatusInterview::DijadwalUlang])
            ->whereDate('tanggal_interview', '>=', now()->toDateString())
            ->whereDate('tanggal_interview', '<=', now()->addDays(7)->toDateString());
    }

    private function ttdMenunggu(User $user): int
    {
        return TandaTanganDokumen::where('id_user_penandatangan', $user->id_user)
            ->where('status_ttd', StatusTtd::Menunggu)
            ->whereHas('dokumen', fn (Builder $q) => $q->where('status_dokumen', StatusDokumen::MenungguTtd))
            ->count();
    }
}
