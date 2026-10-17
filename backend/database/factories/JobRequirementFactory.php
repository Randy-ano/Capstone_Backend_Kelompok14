<?php

namespace Database\Factories;

use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Pegawai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRequirement>
 */
class JobRequirementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_klien' => Klien::factory(),
            'id_am' => Pegawai::factory()->accountManager(),
            'posisi' => fake()->randomElement(['Backend Developer', 'Frontend Developer', 'QA Engineer', 'DevOps Engineer', 'Mobile Developer']),
            'jumlah_kebutuhan' => fake()->numberBetween(1, 3),
            'kualifikasi' => fake()->sentence(),
            'pengalaman_min' => fake()->numberBetween(0, 5),
            'status_req' => StatusReq::Draft,
            'sla_deadline' => now()->addDays(30),
        ];
    }
}
