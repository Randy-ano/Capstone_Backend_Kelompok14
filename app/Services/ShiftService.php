<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\StatusPlacement;
use App\Http\Resources\PlacementResource;
use App\Models\Placement;
use App\Models\ShiftingTalent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-15, R22: shift dikelola HR Client (klien yang sama) atau Manager, dengan input massal.
 * Talent hanya melihat shift miliknya. Satu shift per placement per tanggal.
 */
class ShiftService
{
    public const RELASI = [...PlacementResource::RELASI_RINGKAS, 'pengelola.hrClient', 'pengelola.pegawai'];

    public static function scope(Builder $query, User $user): Builder
    {
        return $query->whereHas('placement', fn (Builder $q) => PlacementService::scope($q, $user));
    }

    /**
     * @param  array{id_placement?: int|null, dari?: string|null, sampai?: string|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(ShiftingTalent::query(), $user)
            ->with(self::RELASI)
            ->when($filter['id_placement'] ?? null, fn (Builder $q, int|string $id) => $q->where('id_placement', $id))
            ->when($filter['dari'] ?? null, fn (Builder $q, string $dari) => $q->whereDate('tanggal_shift', '>=', $dari))
            ->when($filter['sampai'] ?? null, fn (Builder $q, string $sampai) => $q->whereDate('tanggal_shift', '<=', $sampai))
            ->orderBy('tanggal_shift')
            ->orderBy('id_placement')
            ->paginate($filter['per_page'] ?? 31)
            ->withQueryString();
    }

    /**
     * Input massal: satu jenis shift untuk banyak tanggal. Tanggal yang sudah punya shift diperbarui.
     *
     * @param  array{id_placement: int, tanggal: list<string>, jenis_shift: string, keterangan?: string|null}  $data
     * @return array{dibuat: int, diperbarui: int}
     */
    public function simpanMassal(User $user, array $data): array
    {
        $placement = Placement::findOrFail($data['id_placement']);
        $this->pastikanBolehKelola($user, $placement, 'id_placement');
        $tanggal = collect($data['tanggal'])->map(fn (string $t) => Carbon::parse($t)->toDateString())->unique()->sort()->values();
        $this->pastikanDalamPeriode($placement, $tanggal->first(), $tanggal->last(), 'tanggal');

        return DB::transaction(function () use ($user, $data, $placement, $tanggal) {
            $hasil = ['dibuat' => 0, 'diperbarui' => 0];
            foreach ($tanggal as $t) {
                // whereDate: kolom date bisa tersimpan beserta jam (mis. SQLite), jadi tidak dicocokkan sebagai string.
                $shift = ShiftingTalent::where('id_placement', $placement->id_placement)->whereDate('tanggal_shift', $t)->first()
                    ?? new ShiftingTalent(['id_placement' => $placement->id_placement, 'tanggal_shift' => $t]);
                $hasil[$shift->exists ? 'diperbarui' : 'dibuat']++;
                $shift->fill([
                    'id_pengelola' => $user->id_user,
                    'jenis_shift' => $data['jenis_shift'],
                    'keterangan' => $data['keterangan'] ?? null,
                ])->save();
            }

            return $hasil;
        });
    }

    /**
     * @param  array{tanggal_shift?: string, jenis_shift?: string, keterangan?: string|null}  $data
     */
    public function ubah(User $user, ShiftingTalent $shift, array $data): ShiftingTalent
    {
        $placement = $shift->placement;
        $this->pastikanBolehKelola($user, $placement, 'jenis_shift');

        if (isset($data['tanggal_shift'])) {
            $this->pastikanDalamPeriode($placement, $data['tanggal_shift'], $data['tanggal_shift'], 'tanggal_shift');
            $dipakai = ShiftingTalent::where('id_placement', $placement->id_placement)
                ->whereDate('tanggal_shift', $data['tanggal_shift'])
                ->whereKeyNot($shift->id_shift)
                ->exists();
            if ($dipakai) {
                throw ValidationException::withMessages(['tanggal_shift' => 'Talent sudah memiliki shift pada tanggal tersebut.']);
            }
        }

        $shift->update([...Arr::only($data, ['tanggal_shift', 'jenis_shift', 'keterangan']), 'id_pengelola' => $user->id_user]);

        return $shift->load(self::RELASI);
    }

    public function hapus(User $user, ShiftingTalent $shift): void
    {
        $this->pastikanBolehKelola($user, $shift->placement, 'id_shift');
        $shift->delete();
    }

    /** HR Client hanya placement di kliennya; shift hanya untuk placement aktif. */
    private function pastikanBolehKelola(User $user, Placement $placement, string $field): void
    {
        if ($user->hasRole(Role::HrClient) && $placement->id_klien !== $user->idKlien()) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke placement ini.');
        }
        if ($placement->status_placement !== StatusPlacement::Aktif) {
            throw ValidationException::withMessages([$field => 'Shift hanya dapat diatur untuk placement yang masih aktif.']);
        }
    }

    private function pastikanDalamPeriode(Placement $placement, string $awal, string $akhir, string $field): void
    {
        $mulai = $placement->tanggal_mulai->toDateString();
        $selesai = $placement->tanggal_selesai?->toDateString();
        if ($awal < $mulai || ($selesai && $akhir > $selesai)) {
            $periode = $selesai ? "{$mulai} s.d. {$selesai}" : "mulai {$mulai}";
            throw ValidationException::withMessages([$field => "Tanggal shift harus dalam periode placement ({$periode})."]);
        }
    }
}
