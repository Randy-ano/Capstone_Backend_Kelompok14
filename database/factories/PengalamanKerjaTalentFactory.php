<?php

namespace Database\Factories;

use App\Models\PengalamanKerjaTalent;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengalamanKerjaTalent>
 */
class PengalamanKerjaTalentFactory extends Factory
{
    public function definition(): array
    {
        $mulai = fake()->dateTimeBetween('-6 years', '-2 years');

        return [
            'id_talent' => Talent::factory(),
            'nama_perusahaan' => 'PT '.fake()->company(),
            'posisi' => fake()->randomElement(['Backend Developer', 'Frontend Developer', 'QA Engineer', 'Data Analyst']),
            'jenis_pekerjaan' => 'Full-time',
            'lokasi' => fake()->city(),
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => fake()->dateTimeBetween($mulai, '-1 month'),
            'masih_bekerja' => false,
            'deskripsi' => null,
        ];
    }

    public function masihBekerja(): static
    {
        return $this->state(['tanggal_selesai' => null, 'masih_bekerja' => true]);
    }
}
