<?php

namespace App\Observers;

use App\Enums\HasilScreening;
use App\Enums\StatusTahapan;
use App\Models\ScreeningTalent;
use App\Services\ProgresService;

class ScreeningTalentObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /**
     * R12: screening dibuat → log tahapan "screening".
     */
    public function created(ScreeningTalent $screening): void
    {
        $this->catat($screening, StatusTahapan::Screening);
    }

    /**
     * R12: kandidat ditolak di tahap screening → log tahapan "ditolak".
     */
    public function updated(ScreeningTalent $screening): void
    {
        if ($screening->wasChanged('hasil_screening') && $screening->hasil_screening === HasilScreening::TidakLolos) {
            $this->catat($screening, StatusTahapan::Ditolak);
        }
    }

    private function catat(ScreeningTalent $screening, StatusTahapan $tahapan): void
    {
        $this->progres->catat($screening->jobRequirement, $tahapan, $screening->id_talent, $screening->id_ts);
    }
}
