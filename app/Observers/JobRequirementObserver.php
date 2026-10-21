<?php

namespace App\Observers;

use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Services\ProgresService;

class JobRequirementObserver
{
    public function __construct(private readonly ProgresService $progres) {}

    /**
     * R8: job yang langsung dibuat berstatus diajukan dicatat "kebutuhan_dibuat".
     */
    public function created(JobRequirement $job): void
    {
        if ($job->status_req === StatusReq::Diajukan) {
            $this->catatKebutuhanDibuat($job);
        }
    }

    /**
     * R8: job yang berubah menjadi diajukan (dari draft) dicatat "kebutuhan_dibuat".
     */
    public function updated(JobRequirement $job): void
    {
        if ($job->wasChanged('status_req') && $job->status_req === StatusReq::Diajukan) {
            $this->catatKebutuhanDibuat($job);
        }
    }

    private function catatKebutuhanDibuat(JobRequirement $job): void
    {
        $this->progres->catat($job, StatusTahapan::KebutuhanDibuat, idPenanggungJawab: $job->id_am);
    }
}
