<?php

namespace Database\Factories;

use App\Enums\StatusLaporan;
use App\Models\HrClient;
use App\Models\LaporanKeluhan;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanKeluhan>
 */
class LaporanKeluhanFactory extends Factory
{
    public function definition(): array
    {
        // Pelapor adalah HR dari klien tempat talent ditempatkan (R24).
        return [
            'id_placement' => Placement::factory(),
            'id_hr_client' => fn (array $attr) => HrClient::factory()->create([
                'id_klien' => Placement::find($attr['id_placement'])->id_klien,
            ])->id_hr_client,
            'id_penindak_lanjut' => null,
            'judul_laporan' => fake()->sentence(4),
            'deskripsi_keluhan' => fake()->paragraph(),
            'tanggal_laporan' => now(),
            'status_laporan' => StatusLaporan::Baru,
            'hasil_tindak_lanjut' => null,
            'tanggal_tindak_lanjut' => null,
        ];
    }
}
