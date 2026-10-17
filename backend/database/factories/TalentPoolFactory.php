<?php

namespace Database\Factories;

use App\Enums\StatusKetersediaan;
use App\Models\Talent;
use App\Models\TalentPool;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TalentPool>
 */
class TalentPoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_talent' => Talent::factory()->lengkap(),
            'tanggal_masuk' => now(),
            'status_ketersediaan' => StatusKetersediaan::Tersedia,
            'tag_keahlian' => null,
        ];
    }
}
