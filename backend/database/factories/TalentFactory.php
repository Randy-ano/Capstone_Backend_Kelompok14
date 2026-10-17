<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Models\Kompetensi;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Talent>
 */
class TalentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user' => User::factory()->role(Role::Talent),
            'nama_lengkap' => fake()->name(),
            'pendidikan' => null,
            'pengalaman_kerja' => null,
            'file_cv' => null,
            'status_kelengkapan' => StatusKelengkapan::BelumLengkap,
            'status_kandidat' => StatusKandidat::Tersedia,
        ];
    }

    /**
     * Profil lengkap sesuai R4: data diri terisi dan minimal 1 kompetensi.
     */
    public function lengkap(): static
    {
        return $this->state([
            'pendidikan' => 'S1 '.fake()->randomElement(['Informatika', 'Sistem Informasi', 'Teknik Komputer']),
            'pengalaman_kerja' => fake()->paragraph(),
            'status_kelengkapan' => StatusKelengkapan::Lengkap,
        ])->afterCreating(function (Talent $talent) {
            if ($talent->kompetensi()->doesntExist()) {
                $talent->kompetensi()->attach(Kompetensi::factory()->count(2)->create());
            }
        });
    }
}
