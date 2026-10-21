<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\HrClient;
use App\Models\Klien;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HrClient>
 */
class HrClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user' => User::factory()->role(Role::HrClient),
            'id_klien' => Klien::factory(),
            'nama_hr' => fake()->name(),
            'jabatan' => 'HR Manager',
        ];
    }
}
