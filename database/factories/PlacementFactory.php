<?php

namespace Database\Factories;

use App\Enums\StatusPlacement;
use App\Models\EvaluasiKandidat;
use App\Models\Placement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Placement>
 */
class PlacementFactory extends Factory
{
    public function definition(): array
    {
        // id_talent, id_job_req, id_klien disalin dari evaluasi → pengajuan → job (R18).
        $pengajuan = fn (array $attr) => EvaluasiKandidat::find($attr['id_evaluasi'])->pengajuan;

        return [
            'id_evaluasi' => EvaluasiKandidat::factory(),
            'id_talent' => fn (array $attr) => $pengajuan($attr)->id_talent,
            'id_job_req' => fn (array $attr) => $pengajuan($attr)->id_job_req,
            'id_klien' => fn (array $attr) => $pengajuan($attr)->jobRequirement->id_klien,
            'posisi_penempatan' => fn (array $attr) => $pengajuan($attr)->jobRequirement->posisi,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => null,
            'status_placement' => StatusPlacement::Aktif,
        ];
    }
}
