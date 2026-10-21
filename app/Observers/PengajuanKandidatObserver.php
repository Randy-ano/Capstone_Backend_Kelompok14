<?php

namespace App\Observers;

use App\Enums\StatusPengajuan;
use App\Enums\StatusTahapan;
use App\Models\PengajuanKandidat;
use App\Services\ProgresService;

class PengajuanKandidatObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /** R12: pengajuan dibuat → log tahapan "diajukan" (penanggung jawab AM). */
    public function created(PengajuanKandidat $pengajuan): void
    {
        $this->progres->catat($pengajuan->jobRequirement, StatusTahapan::Diajukan, $pengajuan->id_talent, $pengajuan->id_am);
    }

    /** R12: kandidat ditolak klien saat peninjauan → log tahapan "ditolak". */
    public function updated(PengajuanKandidat $pengajuan): void
    {
        if ($pengajuan->wasChanged('status_pengajuan') && $pengajuan->status_pengajuan === StatusPengajuan::Ditolak) {
            $this->progres->catat($pengajuan->jobRequirement, StatusTahapan::Ditolak, $pengajuan->id_talent, $pengajuan->id_am);
        }
    }
}
