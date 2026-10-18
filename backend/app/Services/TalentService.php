<?php

namespace App\Services;

use App\Enums\StatusKelengkapan;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TalentService
{
    /**
     * Mengambil profil talent yang sedang login
     * beserta data pendukung CV.
     */
    public function getProfile(User $user): Talent
    {
        $talent = $this->getTalent($user);

        return $talent->load([
            'kompetensi',
            'riwayatPendidikan',
            'pengalamanKerjaTalent',
            'sertifikat',
            'bahasa',
        ]);
    }

    /**
     * Memperbarui profil utama talent.
     */
    public function updateProfile(
        User $user,
        array $data
    ): Talent {
        $talent = $this->getTalent($user);

        return DB::transaction(function () use ($talent, $data) {
            $talent->update($data);

            $this->updateKelengkapan($talent);
            $this->syncTalentPool($talent);

            return $talent->fresh()->load([
                'kompetensi',
                'riwayatPendidikan',
                'pengalamanKerjaTalent',
                'sertifikat',
                'bahasa',
            ]);
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

            return $talent->fresh()->load([
                'kompetensi',
                'riwayatPendidikan',
                'pengalamanKerjaTalent',
                'sertifikat',
                'bahasa',
            ]);
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
     * Memeriksa kelengkapan profil berdasarkan aturan backend.
     *
     * Syarat:
     * - Nama lengkap terisi.
     * - Pendidikan terisi.
     * - Pengalaman kerja terisi.
     * - Minimal satu kompetensi.
     */
    private function updateKelengkapan(Talent $talent): void
    {
        $talent->load('kompetensi');

        $lengkap =
            !empty($talent->nama_lengkap) &&
            !empty($talent->pendidikan) &&
            !empty($talent->pengalaman_kerja) &&
            $talent->kompetensi->isNotEmpty();

        $talent->update([
            'status_kelengkapan' => $lengkap
                ? StatusKelengkapan::Lengkap
                : StatusKelengkapan::BelumLengkap,
        ]);
    }

    /**
     * Membuat entri Talent Pool ketika profil sudah lengkap
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
