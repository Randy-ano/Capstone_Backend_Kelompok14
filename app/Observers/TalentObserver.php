<?php

namespace App\Observers;

use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Models\Talent;
use App\Models\TalentPool;

class TalentObserver
{
    /**
     * R5: saat status berubah menjadi lengkap dan talent belum ada di pool,
     * otomatis dibuat entri talent pool. Hanya event updated: talent baru selalu
     * belum lengkap, dan seeder yang membuat talent lengkap mengisi pool sendiri.
     */
    public function updated(Talent $talent): void
    {
        if (
            ! $talent->wasChanged('status_kelengkapan') ||
            $talent->status_kelengkapan !== StatusKelengkapan::Lengkap
        ) {
            return;
        }

        TalentPool::firstOrCreate(
            ['id_talent' => $talent->id_talent],
            ['tanggal_masuk' => now(), 'status_ketersediaan' => StatusKetersediaan::Tersedia]
        );
    }
}
