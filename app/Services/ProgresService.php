<?php

namespace App\Services;

use App\Enums\IndikatorSla;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\ProgresPemenuhan;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * FR-09: log progres pemenuhan (R8, R12) dan indikator SLA (R13).
 */
class ProgresService
{
    /** Batas "mendekati" SLA dalam hari. */
    public const HARI_MENDEKATI = 3;

    /** Status job yang tidak lagi diukur SLA-nya. */
    public const STATUS_SELESAI = [StatusReq::Terpenuhi, StatusReq::Dibatalkan];

    /**
     * R13: terlambat jika deadline lewat dan job belum selesai; mendekati jika sisa ≤ 3 hari; aman selain itu.
     * Job terpenuhi/dibatalkan selalu aman. Versi SQL-nya ada di scopeDenganIndikatorSla() agar filter sama persis.
     */
    public function indikatorSla(JobRequirement $job, ?CarbonInterface $sekarang = null): IndikatorSla
    {
        $sekarang ??= now();

        return match (true) {
            in_array($job->status_req, self::STATUS_SELESAI, true) => IndikatorSla::Aman,
            $sekarang->greaterThan($job->sla_deadline) => IndikatorSla::Terlambat,
            $job->sla_deadline->lessThanOrEqualTo($sekarang->copy()->addDays(self::HARI_MENDEKATI)) => IndikatorSla::Mendekati,
            default => IndikatorSla::Aman,
        };
    }

    /**
     * Filter query job requirement berdasarkan indikator SLA saat ini (padanan SQL dari indikatorSla()).
     */
    public function filterIndikatorSla(Builder $query, IndikatorSla $indikator): Builder
    {
        $sekarang = now();
        $batasMendekati = $sekarang->copy()->addDays(self::HARI_MENDEKATI);
        $selesai = array_map(fn (StatusReq $s) => $s->value, self::STATUS_SELESAI);

        return match ($indikator) {
            IndikatorSla::Terlambat => $query
                ->whereNotIn('status_req', $selesai)
                ->where('sla_deadline', '<', $sekarang),
            IndikatorSla::Mendekati => $query
                ->whereNotIn('status_req', $selesai)
                ->whereBetween('sla_deadline', [$sekarang, $batasMendekati]),
            IndikatorSla::Aman => $query->where(fn (Builder $q) => $q
                ->whereIn('status_req', $selesai)
                ->orWhere('sla_deadline', '>', $batasMendekati)),
        };
    }

    /**
     * Catat satu baris log progres; indikator SLA dihitung saat log dibuat.
     */
    public function catat(JobRequirement $job, StatusTahapan $tahapan, ?int $idTalent = null, ?int $idPenanggungJawab = null): ProgresPemenuhan
    {
        return $job->progres()->create([
            'id_talent' => $idTalent,
            'id_penanggung_jawab' => $idPenanggungJawab,
            'status_tahapan' => $tahapan,
            'indikator_sla' => $this->indikatorSla($job),
            'log_waktu_pembaruan' => now(),
        ]);
    }

    /**
     * Log progres satu job, terbaru dulu.
     *
     * @return Collection<int, ProgresPemenuhan>
     */
    public function riwayat(JobRequirement $job): Collection
    {
        return $job->progres()
            ->with(['talent', 'penanggungJawab'])
            ->orderByDesc('log_waktu_pembaruan')
            ->orderByDesc('id_progres')
            ->get();
    }

    /**
     * Tabel monitoring: semua job dengan SLA terkini dan log terakhirnya; paling mendesak dulu.
     *
     * @param  array{status?: string|null, indikator_sla?: string|null, id_klien?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function monitoring(array $filter): LengthAwarePaginator
    {
        $query = JobRequirement::query()
            ->with(JobRequirementService::RELASI_DAFTAR)
            ->with('progresTerakhir.talent')
            ->withCount(JobRequirementService::hitungTerpenuhi())
            ->withCount('screenings');

        app(JobRequirementService::class)->terapkanFilter($query, $filter);

        return $query
            ->orderBy('sla_deadline')
            ->orderBy('id_job_req')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }
}
