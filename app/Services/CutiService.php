<?php

namespace App\Services;

use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Enums\StatusPlacement;
use App\Http\Resources\PlacementResource;
use App\Models\PengajuanCuti;
use App\Models\Placement;
use App\Models\Talent;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * FR-14, R21: cuti diajukan talent untuk placement aktif miliknya dan diputuskan HR Client dari klien tempat
 * talent ditempatkan; keputusan HR Client langsung menjadi status akhir. Internal (Manager, AM, TS) hanya melihat.
 * Lama cuti dihitung dalam hari kerja (Senin–Jumat) dan dibatasi kuota per tahun kalender.
 */
class CutiService
{
    /** Kuota cuti per talent per tahun kalender, dalam hari kerja. Cuti menunggu + disetujui ikut dihitung. */
    public const KUOTA_HARI_KERJA_PER_TAHUN = 12;

    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'approverKlien'];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    /** Talent miliknya, HR Client cuti talent yang ditempatkan di kliennya, internal semua. */
    public static function scope(Builder $query, User $user): Builder
    {
        return $query->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user));
    }

    /**
     * Jumlah hari kerja (Sabtu & Minggu tidak dihitung) dalam rentang, opsional hanya yang jatuh di tahun tertentu.
     */
    public static function hariKerja(CarbonInterface $mulai, CarbonInterface $selesai, ?int $tahun = null): int
    {
        $jumlah = 0;
        foreach (CarbonPeriod::create($mulai->copy()->startOfDay(), $selesai->copy()->startOfDay()) as $hari) {
            if (! $hari->isWeekend() && ($tahun === null || $hari->year === $tahun)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /**
     * Kuota cuti talent di satu tahun kalender.
     *
     * @return array{tahun: int, kuota: int, terpakai: int, sisa: int}
     */
    public function kuota(Talent $talent, int $tahun): array
    {
        $terpakai = PengajuanCuti::query()
            ->where('id_talent', $talent->id_talent)
            ->whereIn('status_cuti', [StatusCuti::Menunggu, StatusCuti::Disetujui])
            ->whereDate('tanggal_mulai', '<=', "{$tahun}-12-31")
            ->whereDate('tanggal_selesai', '>=', "{$tahun}-01-01")
            ->get()
            ->sum(fn (PengajuanCuti $c) => self::hariKerja($c->tanggal_mulai, $c->tanggal_selesai, $tahun));

        return [
            'tahun' => $tahun,
            'kuota' => self::KUOTA_HARI_KERJA_PER_TAHUN,
            'terpakai' => $terpakai,
            'sisa' => max(0, self::KUOTA_HARI_KERJA_PER_TAHUN - $terpakai),
        ];
    }

    /**
     * @param  array{status?: string|null, id_placement?: int|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(PengajuanCuti::query(), $user)
            ->with(self::RELASI)
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_cuti', $status))
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
            ->orderByDesc('tanggal_mulai')
            ->orderByDesc('id_cuti')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * @param  array{id_placement: int, tanggal_mulai: string, tanggal_selesai: string, alasan_cuti: string}  $data
     */
    public function ajukan(User $user, array $data): PengajuanCuti
    {
        $placement = Placement::findOrFail($data['id_placement']);
        $talent = $user->talent;
        $mulai = Carbon::parse($data['tanggal_mulai'])->startOfDay();
        $selesai = Carbon::parse($data['tanggal_selesai'])->startOfDay();

        if ($placement->id_talent !== $talent->id_talent) {
            throw ValidationException::withMessages(['id_placement' => 'Cuti hanya dapat diajukan untuk penempatan milik Anda.']);
        }
        if ($placement->status_placement !== StatusPlacement::Aktif) {
            throw ValidationException::withMessages(['id_placement' => 'Cuti hanya dapat diajukan untuk penempatan yang masih aktif.']);
        }
        if ($mulai->lte(now()->startOfDay())) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Tanggal mulai cuti paling cepat besok.']);
        }
        $this->pastikanDalamPeriode($placement, $mulai, $selesai);

        $jumlahHariKerja = self::hariKerja($mulai, $selesai);
        if ($jumlahHariKerja === 0) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Rentang tanggal cuti hanya berisi hari Sabtu/Minggu. Pilih minimal satu hari kerja.']);
        }

        $bentrok = PengajuanCuti::query()
            ->where('id_talent', $talent->id_talent)
            ->whereIn('status_cuti', [StatusCuti::Menunggu, StatusCuti::Disetujui])
            ->whereDate('tanggal_mulai', '<=', $selesai->toDateString())
            ->whereDate('tanggal_selesai', '>=', $mulai->toDateString())
            ->exists();
        if ($bentrok) {
            throw ValidationException::withMessages(['tanggal_mulai' => 'Tanggal cuti bertumpang tindih dengan cuti lain yang masih menunggu atau sudah disetujui.']);
        }

        // Kuota per tahun kalender: cuti yang melewati pergantian tahun dihitung per tahunnya masing-masing.
        foreach (range($mulai->year, $selesai->year) as $tahun) {
            $diminta = self::hariKerja($mulai, $selesai, $tahun);
            $kuota = $this->kuota($talent, $tahun);
            if ($diminta > $kuota['sisa']) {
                throw ValidationException::withMessages(['tanggal_selesai' => "Sisa kuota cuti tahun {$tahun} adalah {$kuota['sisa']} hari kerja, sedangkan pengajuan ini {$diminta} hari kerja."]);
            }
        }

        $cuti = PengajuanCuti::create([
            'id_talent' => $talent->id_talent,
            'id_placement' => $placement->id_placement,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
            'alasan_cuti' => $data['alasan_cuti'],
        ]);
        $this->pemberitahuan->cutiDiajukan($cuti);

        return $cuti->load(self::RELASI);
    }

    /**
     * HR Client memutuskan; keputusannya langsung menjadi status akhir cuti (R21, revisi 2026-11-05).
     * Kolom approval manager tidak dipakai lagi.
     */
    public function putuskan(User $user, PengajuanCuti $cuti, StatusApproval $keputusan): PengajuanCuti
    {
        if ($cuti->status_cuti !== StatusCuti::Menunggu) {
            throw ValidationException::withMessages(['keputusan' => "Cuti ini sudah {$cuti->status_cuti->value}; keputusan tidak dapat diubah."]);
        }

        $cuti->update([
            'status_approval_klien' => $keputusan,
            'id_approver_klien' => $user->hrClient->id_hr_client,
            'status_cuti' => $keputusan === StatusApproval::Disetujui ? StatusCuti::Disetujui : StatusCuti::Ditolak,
        ]);
        $this->pemberitahuan->cutiDiputuskan($cuti);

        return $cuti->load(self::RELASI);
    }

    /** Talent membatalkan cutinya selama masih menunggu (R21). */
    public function batal(PengajuanCuti $cuti): PengajuanCuti
    {
        if ($cuti->status_cuti !== StatusCuti::Menunggu) {
            throw ValidationException::withMessages(['status_cuti' => 'Hanya cuti yang masih menunggu yang dapat dibatalkan.']);
        }
        $cuti->update(['status_cuti' => StatusCuti::Dibatalkan]);

        return $cuti->load(self::RELASI);
    }

    /** Tanggal cuti harus di dalam periode penempatan. */
    private function pastikanDalamPeriode(Placement $placement, CarbonInterface $mulai, CarbonInterface $selesai): void
    {
        $awal = $placement->tanggal_mulai->toDateString();
        $akhir = $placement->tanggal_selesai?->toDateString();
        if ($mulai->toDateString() < $awal || ($akhir && $selesai->toDateString() > $akhir)) {
            $periode = $akhir ? "{$awal} s.d. {$akhir}" : "mulai {$awal}";
            throw ValidationException::withMessages(['tanggal_mulai' => "Tanggal cuti harus dalam periode penempatan ({$periode})."]);
        }
    }
}
