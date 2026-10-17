<?php

namespace Database\Factories;

use App\Enums\StatusEvaluasi;
use App\Models\EvaluasiKandidat;
use App\Models\HrClient;
use App\Models\PengajuanKandidat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluasiKandidat>
 */
class EvaluasiKandidatFactory extends Factory
{
    public function definition(): array
    {
        // Evaluator adalah HR dari klien pemilik job (R17).
        return [
            'id_pengajuan' => PengajuanKandidat::factory()->lanjutInterview(),
            'id_hr_client' => fn (array $attr) => HrClient::factory()->create([
                'id_klien' => PengajuanKandidat::find($attr['id_pengajuan'])->jobRequirement->id_klien,
            ])->id_hr_client,
            'nilai_score' => fake()->numberBetween(60, 100),
            'catatan_evaluasi' => null,
            'status_evaluasi' => StatusEvaluasi::Diterima,
        ];
    }
}
