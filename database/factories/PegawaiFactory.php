<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pegawai>
 */
class PegawaiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user' => User::factory()->role(Role::AccountManager),
            'nama_pegawai' => fake()->name(),
            'jabatan' => 'Account Manager',
            'no_telepon' => fake()->phoneNumber(),
        ];
    }

    public function accountManager(): static
    {
        return $this->state([
            'id_user' => User::factory()->role(Role::AccountManager),
            'jabatan' => 'Account Manager',
        ]);
    }

    public function talentSupport(): static
    {
        return $this->state([
            'id_user' => User::factory()->role(Role::TalentSupport),
            'jabatan' => 'Talent Support',
        ]);
    }

    public function manager(): static
    {
        return $this->state([
            'id_user' => User::factory()->role(Role::Manager),
            'jabatan' => 'Manager',
        ]);
    }
}
