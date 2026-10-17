<?php

namespace Database\Factories;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Models\Dokumen;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dokumen>
 */
class DokumenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user_uploader' => User::factory()->role(Role::AccountManager),
            'id_klien' => null,
            'id_talent' => null,
            'id_placement' => null,
            'jenis_dokumen' => JenisDokumen::Lainnya,
            'file_path' => 'dokumen/'.Str::uuid().'.pdf',
            'status_dokumen' => StatusDokumen::Draft,
            'tanggal_unggah' => now(),
        ];
    }
}
