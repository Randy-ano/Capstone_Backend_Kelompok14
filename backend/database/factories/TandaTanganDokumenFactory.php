<?php

namespace Database\Factories;

use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TandaTanganDokumen>
 */
class TandaTanganDokumenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_dokumen' => Dokumen::factory(),
            'id_user_penandatangan' => User::factory(),
            'file_tanda_tangan' => null,
            'tanggal_ttd' => null,
            'status_ttd' => StatusTtd::Menunggu,
        ];
    }
}
