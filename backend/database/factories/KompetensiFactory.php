<?php

namespace Database\Factories;

use App\Models\Kompetensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kompetensi>
 */
class KompetensiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_kompetensi' => fake()->unique()->bothify('Kompetensi ####'),
            'kategori' => fake()->randomElement(['Backend', 'Frontend', 'Database', 'DevOps', 'Mobile']),
        ];
    }
}
