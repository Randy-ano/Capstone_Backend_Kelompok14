<?php

namespace Database\Factories;

use App\Enums\HasilScreening;
use App\Models\JobRequirement;
use App\Models\Pegawai;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScreeningTalent>
 */
class ScreeningTalentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_talent' => Talent::factory()->lengkap(),
            'id_job_req' => JobRequirement::factory(),
            'id_ts' => Pegawai::factory()->talentSupport(),
            'skor_kecocokan' => fake()->randomFloat(2, 0, 100),
            'tanggal_screening' => now(),
            'catatan_screening' => null,
            'hasil_screening' => HasilScreening::Pending,
        ];
    }

    public function lolos(): static
    {
        return $this->state(['hasil_screening' => HasilScreening::Lolos]);
    }
}
