<?php

namespace Database\Factories;

use App\Models\Bahasa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bahasa>
 */
class BahasaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_bahasa' => fake()->unique()->bothify('Bahasa ####'),
        ];
    }
}
