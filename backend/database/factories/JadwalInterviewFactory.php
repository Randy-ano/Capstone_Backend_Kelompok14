<?php

namespace Database\Factories;

use App\Enums\StatusInterview;
use App\Models\JadwalInterview;
use App\Models\PengajuanKandidat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JadwalInterview>
 */
class JadwalInterviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_pengajuan' => PengajuanKandidat::factory()->lanjutInterview(),
            'tanggal_interview' => now()->addDays(3)->toDateString(),
            'waktu_interview' => '10:00:00',
            'link_lokasi_meeting' => 'https://meet.google.com/'.fake()->bothify('???-????-???'),
            'instruksi' => null,
            'status_interview' => StatusInterview::Dijadwalkan,
        ];
    }
}
