<?php

namespace Database\Factories;

use App\Enums\IndikatorSla;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\ProgresPemenuhan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgresPemenuhan>
 */
class ProgresPemenuhanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_job_req' => JobRequirement::factory(),
            'id_talent' => null,
            'id_penanggung_jawab' => null,
            'status_tahapan' => StatusTahapan::KebutuhanDibuat,
            'indikator_sla' => IndikatorSla::Aman,
            'log_waktu_pembaruan' => now(),
        ];
    }
}
