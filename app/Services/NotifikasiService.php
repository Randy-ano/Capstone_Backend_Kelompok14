<?php

namespace App\Services;

use App\Enums\KanalNotifikasi;
use App\Enums\Role;
use App\Enums\StatusPengiriman;
use App\Jobs\KirimEmailNotifikasi;
use App\Models\Notifikasi;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * FR-16, R29: satu pintu pengiriman notifikasi. Kanal sistem langsung terkirim (tampil di lonceng);
 * kanal email dikirim lewat queue lalu status_pengiriman diperbarui. Notifikasi berjadwal dikirim scheduler (R31).
 */
class NotifikasiService
{
    /**
     * R29: simpan notifikasi; kirim segera jika tanpa jadwal.
     */
    public function kirim(User $user, string $tipe, string $pesan, KanalNotifikasi $kanal = KanalNotifikasi::Sistem, ?CarbonInterface $jadwal = null): Notifikasi
    {
        $notifikasi = Notifikasi::create([
            'id_user_penerima' => $user->id_user,
            'tipe_notifikasi' => $tipe,
            'pesan' => $pesan,
            'kanal' => $kanal,
            'jadwal_kirim' => $jadwal,
        ]);

        if ($jadwal === null || $jadwal->isPast()) {
            $this->proses($notifikasi);
        }

        return $notifikasi;
    }

    /**
     * Kirim ke banyak penerima sekaligus (duplikat & null diabaikan).
     *
     * @param  iterable<User|null>  $penerima
     */
    public function kirimKe(iterable $penerima, string $tipe, string $pesan, KanalNotifikasi $kanal = KanalNotifikasi::Sistem): void
    {
        collect($penerima)->filter()->unique('id_user')->each(fn (User $u) => $this->kirim($u, $tipe, $pesan, $kanal));
    }

    /** Kanal sistem langsung terkirim; kanal email masuk antrean. */
    public function proses(Notifikasi $notifikasi): void
    {
        if ($notifikasi->kanal === KanalNotifikasi::Email) {
            KirimEmailNotifikasi::dispatch($notifikasi);
        } else {
            $notifikasi->update(['status_pengiriman' => StatusPengiriman::Terkirim]);
        }
    }

    /**
     * R31 (bagian akhir): kirim notifikasi berjadwal yang sudah jatuh tempo.
     */
    public function kirimTerjadwal(): int
    {
        $jatuhTempo = Notifikasi::query()
            ->where('status_pengiriman', StatusPengiriman::Menunggu)
            ->whereNotNull('jadwal_kirim')
            ->where('jadwal_kirim', '<=', now())
            ->get();
        $jatuhTempo->each(fn (Notifikasi $n) => $this->proses($n));

        return $jatuhTempo->count();
    }

    /**
     * @param  array{status_baca?: bool|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return $this->milik($user)
            ->when(array_key_exists('status_baca', $filter) && $filter['status_baca'] !== null, fn (Builder $q) => $q->where('status_baca', (bool) $filter['status_baca']))
            ->orderByDesc('created_at')
            ->orderByDesc('id_notifikasi')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    public function jumlahBelumDibaca(User $user): int
    {
        return $this->milik($user)->where('status_baca', false)->count();
    }

    public function bacaSemua(User $user): int
    {
        return $this->milik($user)->where('status_baca', false)->update(['status_baca' => true]);
    }

    /** Notifikasi milik user yang sudah boleh tampil (tidak menampilkan yang masih dijadwalkan). */
    private function milik(User $user): Builder
    {
        return Notifikasi::query()
            ->where('id_user_penerima', $user->id_user)
            ->where(fn (Builder $q) => $q->whereNull('jadwal_kirim')->orWhere('jadwal_kirim', '<=', now()));
    }

    /* ---------- Penerima umum ---------- */

    /** @return Collection<int, User> */
    public static function managerAktif(): Collection
    {
        return User::where('role', Role::Manager)->where('status_aktif', true)->get();
    }

    /** @return Collection<int, User> */
    public static function hrKlien(?int $idKlien): Collection
    {
        return $idKlien === null
            ? new Collection
            : User::where('status_aktif', true)->whereHas('hrClient', fn (Builder $q) => $q->where('id_klien', $idKlien))->get();
    }
}
