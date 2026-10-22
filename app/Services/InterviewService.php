<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusInterview;
use App\Enums\StatusPengajuan;
use App\Models\JadwalInterview;
use App\Models\PengajuanKandidat;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-11, R16: jadwal interview (bisa beberapa ronde) untuk pengajuan berstatus lanjut_interview.
 * Dijadwalkan/diubah oleh AM atau HR Client dari klien pemilik job.
 */
class InterviewService
{
    public const RELASI = ['pengajuan.talent', 'pengajuan.jobRequirement.klien'];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    /** Status akhir: interview tidak bisa diubah lagi. */
    private const STATUS_AKHIR = [StatusInterview::Selesai, StatusInterview::Dibatalkan];

    /**
     * @param  array{dari?: string|null, sampai?: string|null, status?: string|null, id_pengajuan?: int|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return JadwalInterview::query()
            ->whereHas('pengajuan', fn (Builder $q) => PengajuanService::scope($q, $user))
            ->with(self::RELASI)
            ->when($filter['dari'] ?? null, fn (Builder $q, string $tgl) => $q->whereDate('tanggal_interview', '>=', $tgl))
            ->when($filter['sampai'] ?? null, fn (Builder $q, string $tgl) => $q->whereDate('tanggal_interview', '<=', $tgl))
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_interview', $status))
            ->when($filter['id_pengajuan'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_pengajuan', $id))
            ->orderBy('tanggal_interview')
            ->orderBy('waktu_interview')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @param  array{id_pengajuan: int, tanggal_interview: string, waktu_interview: string, link_lokasi_meeting?: string|null, instruksi?: string|null}  $data
     */
    public function buat(User $user, array $data): JadwalInterview
    {
        $pengajuan = PengajuanKandidat::with(['jobRequirement', 'evaluasi'])->findOrFail($data['id_pengajuan']);
        $this->pastikanBolehMengatur($user, $pengajuan);
        $this->pastikanPengajuanTerbuka($pengajuan, 'id_pengajuan');

        $interview = $pengajuan->interviews()->create(Arr::only($data, ['tanggal_interview', 'waktu_interview', 'link_lokasi_meeting', 'instruksi']));
        $this->pemberitahuan->interview($interview, 'dijadwalkan');

        return $interview->load(self::RELASI);
    }

    /**
     * Jadwal ulang (tanggal/waktu/link/instruksi) atau ubah status (selesai/dibatalkan) — tidak sekaligus.
     * Tanggal/waktu yang berubah otomatis menjadikan status dijadwal_ulang.
     *
     * @param  array<string, mixed>  $data
     */
    public function ubah(JadwalInterview $interview, array $data): JadwalInterview
    {
        $pengajuan = $interview->pengajuan->load(['jobRequirement', 'evaluasi']);
        if (in_array($interview->status_interview, self::STATUS_AKHIR, true)) {
            throw ValidationException::withMessages(['status_interview' => "Interview yang sudah {$interview->status_interview->value} tidak dapat diubah."]);
        }
        $this->pastikanPengajuanTerbuka($pengajuan, 'status_interview');

        if (isset($data['status_interview'])) {
            $status = StatusInterview::from($data['status_interview']);
            if ($status === StatusInterview::Selesai && $interview->tanggal_interview->isFuture()) {
                throw ValidationException::withMessages(['status_interview' => 'Interview belum berlangsung, sehingga belum dapat ditandai selesai.']);
            }
            $interview->update(['status_interview' => $status]);
            $this->pemberitahuan->interview($interview, $status === StatusInterview::Selesai ? 'ditandai selesai' : 'dibatalkan');

            return $interview->load(self::RELASI);
        }

        $interview->fill(Arr::only($data, ['tanggal_interview', 'waktu_interview', 'link_lokasi_meeting', 'instruksi']));
        if ($interview->isDirty(['tanggal_interview', 'waktu_interview'])) {
            $interview->status_interview = StatusInterview::DijadwalUlang;
        }
        $berubah = $interview->isDirty();
        $interview->save();
        if ($berubah) {
            $this->pemberitahuan->interview($interview, $interview->status_interview === StatusInterview::DijadwalUlang ? 'dijadwal_ulang' : 'diperbarui');
        }

        return $interview->load(self::RELASI);
    }

    /** AM boleh semua; HR Client hanya pengajuan ke job kliennya. Role lain sudah ditolak di route. */
    public function pastikanBolehMengatur(User $user, PengajuanKandidat $pengajuan): void
    {
        if ($user->hasRole(Role::HrClient) && $pengajuan->jobRequirement->id_klien !== $user->idKlien()) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke pengajuan ini.');
        }
    }

    private function pastikanPengajuanTerbuka(PengajuanKandidat $pengajuan, string $field): void
    {
        if ($pengajuan->status_pengajuan !== StatusPengajuan::LanjutInterview) {
            throw ValidationException::withMessages([$field => 'Interview hanya dapat diatur untuk pengajuan berstatus lanjut interview.']);
        }
        if ($pengajuan->evaluasi) {
            throw ValidationException::withMessages([$field => 'Pengajuan ini sudah dievaluasi; jadwal interview tidak dapat diubah.']);
        }
        PengajuanService::pastikanJobBerjalan($pengajuan->jobRequirement, $field);
    }
}
