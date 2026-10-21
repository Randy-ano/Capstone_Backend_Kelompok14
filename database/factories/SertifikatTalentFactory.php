<?php

namespace Database\Factories;

use App\Models\SertifikatTalent;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SertifikatTalent>
 */
class SertifikatTalentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_talent' => Talent::factory(),
            'nama_sertifikat' => fake()->randomElement(['AWS Cloud Practitioner', 'Oracle Java SE', 'Google Data Analytics']),
            'penerbit' => fake()->company(),
            'tanggal_terbit' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'tanggal_kedaluwarsa' => null,
            'file_sertifikat' => null,
            'url_sertifikat' => fake()->url(),
        ];
    }
}
