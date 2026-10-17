<?php

namespace Database\Factories;

use App\Enums\JenisShift;
use App\Models\Placement;
use App\Models\ShiftingTalent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftingTalent>
 */
class ShiftingTalentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_placement' => Placement::factory(),
            'id_pengelola' => null,
            'tanggal_shift' => now()->addDays(fake()->unique()->numberBetween(0, 365))->toDateString(),
            'jenis_shift' => fake()->randomElement(JenisShift::cases()),
            'keterangan' => null,
        ];
    }
}
