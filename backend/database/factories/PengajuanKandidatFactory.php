<?php

namespace Database\Factories;

use App\Enums\StatusPengajuan;
use App\Models\Pegawai;
use App\Models\PengajuanKandidat;
use App\Models\ScreeningTalent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajuanKandidat>
 */
class PengajuanKandidatFactory extends Factory
{
    public function definition(): array
    {
        // id_talent & id_job_req disalin dari screening (R14).
        return [
            'id_screening' => ScreeningTalent::factory()->lolos(),
            'id_talent' => fn (array $attr) => ScreeningTalent::find($attr['id_screening'])->id_talent,
            'id_job_req' => fn (array $attr) => ScreeningTalent::find($attr['id_screening'])->id_job_req,
            'id_am' => Pegawai::factory()->accountManager(),
            'tanggal_pengajuan' => now(),
            'status_pengajuan' => StatusPengajuan::Diajukan,
        ];
    }

    public function lanjutInterview(): static
    {
        return $this->state(['status_pengajuan' => StatusPengajuan::LanjutInterview]);
    }
}
