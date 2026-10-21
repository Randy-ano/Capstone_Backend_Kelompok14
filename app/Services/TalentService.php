<?php

namespace App\Services;

use App\Enums\StatusKelengkapan;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TalentService
{
    /** Relasi yang ikut ditampilkan di profil talent. */
    public const RELASI_PROFIL = [
        'kompetensi',
        'riwayatPendidikan',
        'pengalamanKerjaTalent',
        'sertifikat',
        'bahasa',
    ];

    /**
     * Mengambil profil talent yang sedang login
     * beserta data pendukung CV.
     */
    public function getProfile(User $user): Talent
    {
        return $this->getTalent($user)->load(self::RELASI_PROFIL);
    }

    /**
     * Memperbarui profil utama talent. Riwayat pendidikan/pengalaman,
     * jika dikirim, menggantikan seluruh daftar lama.
     */
    public function updateProfile(
        User $user,
        array $data
    ): Talent {
        $talent = $this->getTalent($user);

        return DB::transaction(function () use ($talent, $data) {
            $talent->update(Arr::except($data, ['riwayat_pendidikan', 'riwayat_pengalaman_kerja']));

            if (array_key_exists('riwayat_pendidikan', $data)) {
                $talent->riwayatPendidikan()->delete();
                $talent->riwayatPendidikan()->createMany($data['riwayat_pendidikan']);
            }

            if (array_key_exists('riwayat_pengalaman_kerja', $data)) {
                $talent->pengalamanKerjaTalent()->delete();
                $talent->pengalamanKerjaTalent()->createMany(array_map(
                    fn (array $baris) => $this->normalisasiPengalaman($baris),
                    $data['riwayat_pengalaman_kerja']
                ));
            }

            $this->updateKelengkapan($talent);
            $this->syncTalentPool($talent);

            return $talent->fresh()->load(self::RELASI_PROFIL);
        });
    }

    /**
     * Memperbarui daftar kompetensi talent.
     */
    public function syncKompetensi(
        User $user,
        array $idKompetensi
    ): Talent {
        $talent = $this->getTalent($user);

        return DB::transaction(function () use (
            $talent,
            $idKompetensi
        ) {
            $talent->kompetensi()->sync($idKompetensi);

            $this->updateKelengkapan($talent);
            $this->syncTalentPool($talent);

            return $talent->fresh()->load(self::RELASI_PROFIL);
        });
    }

    /**
     * Memastikan user memiliki profil talent sendiri.
     */
    private function getTalent(User $user): Talent
    {
        $talent = $user->talent;

        if (!$talent) {
            throw new AccessDeniedHttpException(
                'Profil talent tidak ditemukan atau akses ditolak.'
            );
        }

        return $talent;
    }

    /**
     * Pekerjaan yang masih berjalan tidak punya tanggal selesai.
     */
    private function normalisasiPengalaman(array $baris): array
    {
        $baris['masih_bekerja'] = (bool) ($baris['masih_bekerja'] ?? false);

        if ($baris['masih_bekerja']) {
            $baris['tanggal_selesai'] = null;
        }

        return $baris;
    }

    /**
     * R4: profil lengkap jika nama lengkap terisi, minimal satu
     * riwayat pendidikan, dan minimal satu kompetensi.
     */
    private function updateKelengkapan(Talent $talent): void
    {
        $lengkap =
            trim((string) $talent->nama_lengkap) !== '' &&
            $talent->riwayatPendidikan()->exists() &&
            $talent->kompetensi()->exists();

        $talent->update([
            'status_kelengkapan' => $lengkap
                ? StatusKelengkapan::Lengkap
                : StatusKelengkapan::BelumLengkap,
        ]);
    }

    /**
     * R5: membuat entri Talent Pool ketika profil sudah lengkap
     * dan talent belum memiliki entri.
     */
    private function syncTalentPool(Talent $talent): void
    {
        if (
            $talent->status_kelengkapan === StatusKelengkapan::Lengkap &&
            !$talent->talentPool()->exists()
        ) {
            $talent->talentPool()->create([
                'tanggal_masuk' => now(),
            ]);
        }
    }
}
