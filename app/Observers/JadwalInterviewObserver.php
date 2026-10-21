<?php

namespace App\Observers;

use App\Enums\StatusTahapan;
use App\Models\JadwalInterview;
use App\Services\ProgresService;

class JadwalInterviewObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /** R12: interview dijadwalkan (setiap ronde) → log tahapan "interview". */
    public function created(JadwalInterview $interview): void
    {
        $pengajuan = $interview->pengajuan;
        $this->progres->catat($pengajuan->jobRequirement, StatusTahapan::Interview, $pengajuan->id_talent, $pengajuan->id_am);
    }
}
