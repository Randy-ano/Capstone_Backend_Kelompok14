<?php

namespace App\Observers;

use App\Enums\StatusEvaluasi;
use App\Enums\StatusTahapan;
use App\Models\EvaluasiKandidat;
use App\Services\ProgresService;

class EvaluasiKandidatObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /** R12: evaluasi disimpan → log "evaluasi" jika diterima, "ditolak" jika kandidat ditolak. */
    public function created(EvaluasiKandidat $evaluasi): void
    {
        $pengajuan = $evaluasi->pengajuan;
        $tahapan = $evaluasi->status_evaluasi === StatusEvaluasi::Ditolak ? StatusTahapan::Ditolak : StatusTahapan::Evaluasi;
        $this->progres->catat($pengajuan->jobRequirement, $tahapan, $pengajuan->id_talent, $pengajuan->id_am);
    }
}
