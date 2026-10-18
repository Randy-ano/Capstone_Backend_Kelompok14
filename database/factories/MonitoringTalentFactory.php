<?php

namespace Database\Factories;

use App\Models\MonitoringTalent;
use App\Models\Pegawai;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitoringTalent>
 */
class MonitoringTalentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_placement' => Placement::factory(),
            'id_manager' => Pegawai::factory()->manager(),
            'tanggal_monitoring' => now(),
            'hasil_monitoring' => fake()->paragraph(),
            'catatan_tindak_lanjut' => null,
        ];
    }
}
