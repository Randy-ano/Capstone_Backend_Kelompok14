<?php

namespace Database\Factories;

use App\Models\RiwayatPendidikan;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatPendidikan>
 */
class RiwayatPendidikanFactory extends Factory
{
    public function definition(): array
    {
        $mulai = fake()->numberBetween(2010, 2020);

        return [
            'id_talent' => Talent::factory(),
            'jenjang' => fake()->randomElement(['D3', 'S1', 'S2']),
            'nama_institusi' => 'Universitas '.fake()->city(),
            'jurusan' => fake()->randomElement(['Teknik Informatika', 'Sistem Informasi', 'Ilmu Komputer']),
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 4,
            'deskripsi' => null,
        ];
    }
}
