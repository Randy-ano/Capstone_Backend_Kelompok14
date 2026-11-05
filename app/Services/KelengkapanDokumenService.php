<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusEvaluasi;
use App\Enums\StatusReq;
use App\Models\Dokumen;
use App\Models\JobRequirement;
use App\Models\PengajuanKandidat;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Dokumen kerja sama berurutan per permintaan kebutuhan (R34–R36, revisi 2026-11-06):
 * kandidat diterima → Quotation → Purchase Order → PKS (per permintaan) → Kontrak Kerja (per kandidat) → penempatan.
 * Satu langkah selesai jika ada dokumen jenis itu yang berstatus `ditandatangani`.
 */
class KelengkapanDokumenService
{
    /** Urutan dokumen. Tiga pertama per permintaan kebutuhan, Kontrak Kerja per pengajuan kandidat. */
    public const URUTAN = [JenisDokumen::Quotation, JenisDokumen::PurchaseOrder, JenisDokumen::Pks, JenisDokumen::KontrakKerja];

    public const JENIS_PERMINTAAN = [JenisDokumen::Quotation, JenisDokumen::PurchaseOrder, JenisDokumen::Pks];

    public const LABEL = [
        'quotation' => 'Quotation',
        'purchase_order' => 'Purchase Order',
        'pks' => 'PKS',
        'kontrak_kerja' => 'Kontrak Kerja',
    ];

    /** Role yang boleh mengunggah/membuat tiap jenis berurutan. */
    public const PENGUNGGAH = [
        'quotation' => [Role::AccountManager],
        'purchase_order' => [Role::HrClient],
        'pks' => [Role::AccountManager, Role::Manager],
        'kontrak_kerja' => [Role::AccountManager],
    ];

    private const NAMA_PENGUNGGAH = [
        'quotation' => 'Account Manager',
        'purchase_order' => 'HR Client',
        'pks' => 'Account Manager atau Manager',
        'kontrak_kerja' => 'Account Manager',
    ];

    public const STATUS = [
        'belum_ada' => 'Belum ada',
        'menunggu' => 'Menunggu tanda tangan',
        'ditolak' => 'Ditolak',
        'selesai' => 'Selesai',
    ];

    private const STATUS_BERJALAN = [StatusDokumen::Draft, StatusDokumen::MenungguTtd];

    public static function berurutan(JenisDokumen $jenis): bool
    {
        return in_array($jenis, self::URUTAN, true);
    }

    /* ---------- Status kelengkapan ---------- */

    /**
     * Status satu langkah dari dokumen jenis itu: dokumen yang sudah ditandatangani diutamakan, lalu yang masih
     * berjalan (draft/menunggu tanda tangan), lalu yang ditolak; masing-masing yang terbaru.
     *
     * @param  iterable<Dokumen>  $dokumen
     */
    public function langkah(JenisDokumen $jenis, iterable $dokumen): array
    {
        $daftar = collect($dokumen)->filter(fn (Dokumen $d) => $d->jenis_dokumen === $jenis)->sortByDesc('id_dokumen');
        $pilih = $daftar->first(fn (Dokumen $d) => $d->status_dokumen === StatusDokumen::Ditandatangani)
            ?? $daftar->first(fn (Dokumen $d) => in_array($d->status_dokumen, self::STATUS_BERJALAN, true))
            ?? $daftar->first();
        $status = match ($pilih?->status_dokumen) {
            null => 'belum_ada',
            StatusDokumen::Ditandatangani => 'selesai',
            StatusDokumen::Ditolak => 'ditolak',
            default => 'menunggu',
        };

        return [
            'jenis' => $jenis->value,
            'label' => self::LABEL[$jenis->value],
            'status' => $status,
            'label_status' => self::STATUS[$status],
            'dokumen' => $pilih ? [
                'id_dokumen' => $pilih->id_dokumen,
                'nama_file' => DokumenService::namaFile($pilih),
                'status_dokumen' => $pilih->status_dokumen,
            ] : null,
        ];
    }

    /** Empat langkah untuk satu kandidat diterima: Quotation, PO, PKS permintaannya + Kontrak Kerja miliknya. */
    public function untukPengajuan(PengajuanKandidat $pengajuan): array
    {
        $dokumenJob = $this->dokumenPermintaan($pengajuan->id_job_req);
        $kontrak = $pengajuan->dokumen()->where('jenis_dokumen', JenisDokumen::KontrakKerja)->get();

        $langkah = [
            ...array_map(fn (JenisDokumen $j) => $this->langkah($j, $dokumenJob), self::JENIS_PERMINTAAN),
            $this->langkah(JenisDokumen::KontrakKerja, $kontrak),
        ];
        $kurang = collect($langkah)->where('status', '!=', 'selesai')->pluck('label')->values()->all();

        return ['langkah' => $langkah, 'selesai' => $kurang === [], 'kurang' => $kurang];
    }

    /**
     * Detail permintaan: tiga langkah permintaan + Kontrak Kerja tiap kandidat diterima + ringkasan tahap.
     * Null jika belum ada kandidat diterima (rangkaian dokumen belum dimulai).
     */
    public function untukPermintaan(JobRequirement $job): ?array
    {
        $diterima = $this->pengajuanDiterima($job->id_job_req)
            ->with(['talent', 'evaluasi.placement', 'dokumen' => fn ($q) => $q->where('jenis_dokumen', JenisDokumen::KontrakKerja)])
            ->orderBy('id_pengajuan')
            ->get();
        if ($diterima->isEmpty()) {
            return null;
        }

        $dokumenJob = $this->dokumenPermintaan($job->id_job_req);
        $langkah = array_map(fn (JenisDokumen $j) => $this->langkah($j, $dokumenJob), self::JENIS_PERMINTAAN);
        $kontrak = $diterima->map(fn (PengajuanKandidat $p) => [
            'id_pengajuan' => $p->id_pengajuan,
            'nama_lengkap' => $p->talent->nama_lengkap,
            'id_placement' => $p->evaluasi?->placement?->id_placement,
            ...$this->langkah(JenisDokumen::KontrakKerja, $p->dokumen),
        ])->values()->all();

        return ['langkah' => $langkah, 'kontrak_kerja' => $kontrak, 'tahap' => $this->tahap($langkah, $kontrak)];
    }

    /** Ringkasan tahap dokumen untuk Pipeline Rekrutmen (null jika belum ada kandidat diterima). */
    public function tahapPermintaan(JobRequirement $job): ?array
    {
        return $this->untukPermintaan($job)['tahap'] ?? null;
    }

    /**
     * Langkah pertama yang belum selesai, mis. "PKS: Menunggu tanda tangan" atau "Kontrak Kerja: 1 dari 2 selesai".
     */
    private function tahap(array $langkah, array $kontrak): array
    {
        $kontrakSelesai = collect($kontrak)->where('status', 'selesai')->count();
        $belum = collect($langkah)->firstWhere('status', '!=', 'selesai');
        $semuaKontrak = $kontrakSelesai === count($kontrak);

        return [
            'label' => $belum
                ? "{$belum['label']}: {$belum['label_status']}"
                : ($semuaKontrak ? 'Dokumen lengkap' : "Kontrak Kerja: {$kontrakSelesai} dari ".count($kontrak).' selesai'),
            'langkah_selesai' => collect($langkah)->where('status', 'selesai')->count() + ($belum === null && $semuaKontrak ? 1 : 0),
            'total_langkah' => count(self::URUTAN),
            'kontrak_selesai' => $kontrakSelesai,
            'jumlah_kandidat' => count($kontrak),
            'selesai' => $belum === null && $semuaKontrak,
        ];
    }

    /* ---------- Aturan urutan & hak unggah ---------- */

    /**
     * R34–R35: hak unggah per role dan urutan dokumen. Mengembalikan permintaan & pengajuan tujuan.
     *
     * @return array{0: JobRequirement, 1: PengajuanKandidat|null}
     */
    public function pastikanBolehUnggah(User $user, JenisDokumen $jenis, ?int $idJob, ?int $idPengajuan): array
    {
        $this->pastikanRole($user, $jenis);
        $pengajuan = null;

        if ($jenis === JenisDokumen::KontrakKerja) {
            if (! $idPengajuan) {
                throw ValidationException::withMessages(['id_pengajuan' => 'Pilih kandidat yang diterima untuk Kontrak Kerja.']);
            }
            $pengajuan = PengajuanKandidat::with(['evaluasi', 'jobRequirement'])->findOrFail($idPengajuan);
            if ($pengajuan->evaluasi?->status_evaluasi !== StatusEvaluasi::Diterima) {
                throw ValidationException::withMessages(['id_pengajuan' => 'Kontrak Kerja hanya dapat dibuat untuk kandidat yang diterima di evaluasi.']);
            }
            $job = $pengajuan->jobRequirement;
            $field = 'id_pengajuan';
        } else {
            if (! $idJob) {
                throw ValidationException::withMessages(['id_job_req' => 'Pilih permintaan kebutuhan untuk '.self::LABEL[$jenis->value].'.']);
            }
            $job = JobRequirement::findOrFail($idJob);
            $field = 'id_job_req';
        }

        if ($user->hasRole(Role::HrClient) && $job->id_klien !== $user->idKlien()) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke permintaan kebutuhan ini.');
        }
        if ($job->status_req === StatusReq::Dibatalkan) {
            throw ValidationException::withMessages([$field => 'Permintaan kebutuhan ini sudah dibatalkan.']);
        }

        $kurang = $this->syaratBelumTerpenuhi($jenis, $job);
        if ($kurang !== null) {
            throw ValidationException::withMessages([$field => $kurang]);
        }
        if ($this->masihBerjalan($jenis, $job, $pengajuan)) {
            $tujuan = $pengajuan ? 'kandidat ini' : 'permintaan ini';
            throw ValidationException::withMessages([$field => self::LABEL[$jenis->value]." untuk {$tujuan} masih dalam proses tanda tangan. Selesaikan dokumen tersebut terlebih dahulu."]);
        }

        return [$job, $pengajuan];
    }

    /** Pesan syarat urutan yang belum terpenuhi, atau null jika boleh. */
    public function syaratBelumTerpenuhi(JenisDokumen $jenis, JobRequirement $job): ?string
    {
        $label = self::LABEL[$jenis->value];
        if ($jenis === JenisDokumen::Quotation) {
            return $this->pengajuanDiterima($job->id_job_req)->exists()
                ? null
                : "{$label} belum bisa diunggah: permintaan ini belum memiliki kandidat yang diterima di evaluasi.";
        }

        $sebelum = self::URUTAN[array_search($jenis, self::URUTAN, true) - 1];
        $status = $this->langkah($sebelum, $this->dokumenPermintaan($job->id_job_req))['status'];

        return $status === 'selesai'
            ? null
            : "{$label} belum bisa diunggah: ".self::LABEL[$sebelum->value].' untuk permintaan ini belum selesai ditandatangani.';
    }

    /**
     * Pilihan tujuan di form unggah: permintaan (Quotation/PO/PKS) atau kandidat diterima (Kontrak Kerja) yang
     * memenuhi urutan dan belum punya dokumen jenis itu yang masih berjalan atau sudah selesai.
     */
    public function tujuan(User $user, JenisDokumen $jenis): array
    {
        $this->pastikanRole($user, $jenis);

        if ($jenis === JenisDokumen::KontrakKerja) {
            return PengajuanKandidat::query()
                ->whereHas('evaluasi', fn (Builder $q) => $q->where('status_evaluasi', StatusEvaluasi::Diterima)->doesntHave('placement'))
                ->whereHas('jobRequirement', fn (Builder $q) => $q->where('status_req', '!=', StatusReq::Dibatalkan))
                ->with(['talent', 'jobRequirement.klien', 'dokumen'])
                ->orderBy('id_pengajuan')
                ->get()
                ->filter(fn (PengajuanKandidat $p) => $this->syaratBelumTerpenuhi($jenis, $p->jobRequirement) === null
                    && in_array($this->langkah($jenis, $p->dokumen)['status'], ['belum_ada', 'ditolak'], true))
                ->map(fn (PengajuanKandidat $p) => [
                    'id_pengajuan' => $p->id_pengajuan,
                    'id_job_req' => $p->id_job_req,
                    'nama_lengkap' => $p->talent->nama_lengkap,
                    'posisi' => $p->jobRequirement->posisi,
                    'nama_perusahaan' => $p->jobRequirement->klien->nama_perusahaan,
                ])->values()->all();
        }

        return JobRequirement::query()
            ->where('status_req', '!=', StatusReq::Dibatalkan)
            ->whereHas('pengajuanKandidat.evaluasi', fn (Builder $q) => $q->where('status_evaluasi', StatusEvaluasi::Diterima))
            ->when($user->hasRole(Role::HrClient), fn (Builder $q) => $q->where('id_klien', $user->idKlien()))
            ->with(['klien', 'dokumen'])
            ->orderBy('sla_deadline')
            ->get()
            ->filter(fn (JobRequirement $j) => $this->syaratBelumTerpenuhi($jenis, $j) === null
                && in_array($this->langkah($jenis, $j->dokumen)['status'], ['belum_ada', 'ditolak'], true))
            ->map(fn (JobRequirement $j) => [
                'id_job_req' => $j->id_job_req,
                'posisi' => $j->posisi,
                'id_klien' => $j->id_klien,
                'nama_perusahaan' => $j->klien->nama_perusahaan,
            ])->values()->all();
    }

    /**
     * Penandatangan yang disarankan (dicentang otomatis, masih bisa diubah):
     * Quotation → HR Client · PO → Manager · PKS → Manager + HR Client · Kontrak Kerja → talent + Manager.
     *
     * @param  Collection<int, User>  $calon
     * @return list<int>
     */
    public function penandatanganDisarankan(Dokumen $dokumen, Collection $calon): array
    {
        $peran = match ($dokumen->jenis_dokumen) {
            JenisDokumen::Quotation => [Role::HrClient],
            JenisDokumen::PurchaseOrder => [Role::Manager],
            JenisDokumen::Pks => [Role::Manager, Role::HrClient],
            JenisDokumen::KontrakKerja => [Role::Talent, Role::Manager],
            default => [],
        };

        return $calon->filter(fn (User $u) => in_array($u->role, $peran, true))->pluck('id_user')->values()->all();
    }

    /* ---------- Bantuan ---------- */

    private function pastikanRole(User $user, JenisDokumen $jenis): void
    {
        $boleh = self::PENGUNGGAH[$jenis->value] ?? null;
        if ($boleh !== null && ! $user->hasRole(...$boleh)) {
            throw new AccessDeniedHttpException(self::LABEL[$jenis->value].' hanya dapat diunggah oleh '.self::NAMA_PENGUNGGAH[$jenis->value].'.');
        }
    }

    private function masihBerjalan(JenisDokumen $jenis, JobRequirement $job, ?PengajuanKandidat $pengajuan): bool
    {
        $query = $pengajuan ? $pengajuan->dokumen() : $job->dokumen();

        return $query->where('jenis_dokumen', $jenis)->whereIn('status_dokumen', self::STATUS_BERJALAN)->exists();
    }

    private function dokumenPermintaan(int $idJob): Collection
    {
        return Dokumen::where('id_job_req', $idJob)->whereIn('jenis_dokumen', self::JENIS_PERMINTAAN)->get();
    }

    private function pengajuanDiterima(int $idJob): Builder
    {
        return PengajuanKandidat::query()
            ->where('id_job_req', $idJob)
            ->whereHas('evaluasi', fn (Builder $q) => $q->where('status_evaluasi', StatusEvaluasi::Diterima));
    }
}
