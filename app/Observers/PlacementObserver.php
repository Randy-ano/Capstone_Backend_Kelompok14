<?php

namespace App\Observers;

use App\Enums\StatusTahapan;
use App\Models\Placement;
use App\Services\ProgresService;

class PlacementObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /** R12: placement dibuat → log tahapan "placement" (penanggung jawab AM job). */
    public function created(Placement $placement): void
    {
        $job = $placement->jobRequirement;
        $this->progres->catat($job, StatusTahapan::Placement, $placement->id_talent, $job->id_am);
    }
}
