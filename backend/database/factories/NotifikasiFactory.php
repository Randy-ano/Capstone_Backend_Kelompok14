<?php

namespace Database\Factories;

use App\Enums\KanalNotifikasi;
use App\Enums\StatusPengiriman;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notifikasi>
 */
class NotifikasiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_user_penerima' => User::factory(),
            'pesan' => fake()->sentence(),
            'tipe_notifikasi' => 'interview_dijadwalkan',
            'kanal' => KanalNotifikasi::Sistem,
            'jadwal_kirim' => null,
            'status_pengiriman' => StatusPengiriman::Menunggu,
            'status_baca' => false,
        ];
    }
}
