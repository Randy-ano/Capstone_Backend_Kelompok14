<?php

namespace App\Services;

use App\Enums\StatusLaporan;
use App\Http\Resources\PlacementResource;
use App\Models\LaporanKeluhan;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-19, R24: laporan keluhan dibuat HR Client untuk placement di kliennya (status baru),
 * ditindaklanjuti AM/Manager (diproses → selesai).
 */
class LaporanKeluhanService
{
    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'hrClient', 'penindakLanjut'];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    public static function scope(Builder $query, User $user): Builder
    {
        return $query->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user));
    }

    /**
     * @param  array{status?: string|null, id_placement?: int|null, search?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(LaporanKeluhan::query(), $user)
            ->with(self::RELASI)
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_laporan', $status))
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->where('judul_laporan', 'like', "%{$search}%")
                ->orWhereHas('placement.talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))))
            ->orderByDesc('tanggal_laporan')
            ->orderByDesc('id_laporan')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * Jumlah laporan per status yang dapat dilihat user (tidak terpengaruh filter).
     *
     * @return array<string, int>
     */
    public function ringkasan(User $user): array
    {
        $perStatus = self::scope(LaporanKeluhan::query(), $user)
            ->selectRaw('status_laporan, count(*) as jumlah')
            ->groupBy('status_laporan')
            ->pluck('jumlah', 'status_laporan');

        $ringkasan = ['total' => (int) $perStatus->sum()];
        foreach (StatusLaporan::cases() as $status) {
            $ringkasan[$status->value] = (int) ($perStatus[$status->value] ?? 0);
        }

        return $ringkasan;
    }

    /**
     * @param  array{id_placement: int, judul_laporan: string, deskripsi_keluhan: string}  $data
     */
    public function buat(User $user, array $data): LaporanKeluhan
    {
        $placement = Placement::findOrFail($data['id_placement']);
        if ($placement->id_klien !== $user->idKlien()) {
            throw new AccessDeniedHttpException('Anda hanya dapat melaporkan talent yang ditempatkan di perusahaan Anda.');
        }

        $laporan = $placement->laporanKeluhan()->create([
            'id_hr_client' => $user->hrClient->id_hr_client,
            'judul_laporan' => $data['judul_laporan'],
            'deskripsi_keluhan' => $data['deskripsi_keluhan'],
            'tanggal_laporan' => now(),
        ]);
        $this->pemberitahuan->keluhanBaru($laporan);

        return $laporan->load(self::RELASI);
    }

    /**
     * R24: AM/Manager mengisi penindak lanjut, hasil, tanggal, dan status (diproses/selesai). Laporan selesai final.
     *
     * @param  array{status_laporan: string, hasil_tindak_lanjut?: string|null}  $data
     */
    public function tindakLanjut(User $user, LaporanKeluhan $laporan, array $data): LaporanKeluhan
    {
        if ($laporan->status_laporan === StatusLaporan::Selesai) {
            throw ValidationException::withMessages(['status_laporan' => 'Laporan yang sudah selesai tidak dapat diubah.']);
        }

        $laporan->update([
            'status_laporan' => $data['status_laporan'],
            'hasil_tindak_lanjut' => $data['hasil_tindak_lanjut'] ?? $laporan->hasil_tindak_lanjut,
            'id_penindak_lanjut' => $user->pegawai->id_pegawai,
            'tanggal_tindak_lanjut' => now(),
        ]);

        return $laporan->load(self::RELASI);
    }
}
