<?php

namespace Database\Factories;

use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use App\Models\PengajuanCuti;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanCuti>
 */
class PengajuanCutiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_placement' => Placement::factory(),
            'id_talent' => fn (array $attr) => Placement::find($attr['id_placement'])->id_talent,
            'id_approver_klien' => null,
            'id_approver_manager' => null,
            'status_approval_klien' => StatusApproval::Menunggu,
            'status_approval_manager' => StatusApproval::Menunggu,
            'tanggal_mulai' => now()->addDays(7)->toDateString(),
            'tanggal_selesai' => now()->addDays(8)->toDateString(),
            'alasan_cuti' => fake()->sentence(),
            'status_cuti' => StatusCuti::Menunggu,
        ];
    }
}
