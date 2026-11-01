<?php

namespace Database\Seeders;

use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Notifikasi sudah dibuat Service selama seeding (R30). Di sini hanya menandai yang lama sudah dibaca,
 * sehingga tiap akun demo punya beberapa notifikasi terbaru yang belum dibaca.
 */
class NotifikasiSeeder extends Seeder
{
    /** Jumlah notifikasi terbaru yang dibiarkan belum dibaca. */
    private const BELUM_DIBACA = [
        'manager@intellisys.test' => 6,
        'am@intellisys.test' => 5,
        'ts@intellisys.test' => 4,
        'hr@intellisys.test' => 5,
        'talent@intellisys.test' => 3,
    ];

    /** Akun selain akun demo. */
    private const BELUM_DIBACA_LAIN = 2;

    public function run(): void
    {
        User::whereHas('notifikasi')->each(function (User $user) {
            $sisa = self::BELUM_DIBACA[$user->email] ?? self::BELUM_DIBACA_LAIN;
            $terbaru = $user->notifikasi()->orderByDesc('created_at')->orderByDesc('id_notifikasi')->limit($sisa)->pluck('id_notifikasi');

            // Lewat query langsung agar waktu notifikasi tidak berubah.
            Notifikasi::where('id_user_penerima', $user->id_user)
                ->whereNotIn('id_notifikasi', $terbaru)
                ->toBase()
                ->update(['status_baca' => true]);
        });
    }
}
