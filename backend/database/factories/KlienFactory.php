<?php

namespace Database\Factories;

use App\Models\Klien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Klien>
 */
class KlienFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama_perusahaan' => fake()->company(),
            'alamat' => fake()->address(),
            'no_telepon' => fake()->phoneNumber(),
            'penanggung_jawab' => fake()->name(),
        ];
    }
}
