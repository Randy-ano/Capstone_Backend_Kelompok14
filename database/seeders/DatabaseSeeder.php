<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Data demo presentasi. Deterministik: tanpa data acak, tanggal relatif terhadap hari seed, dan nama folder
 * file dokumen tetap (sehingga seed ulang menimpa file lama, bukan menambah). Model event sengaja aktif agar
 * Observer mencatat talent pool (R5) dan log progres (R8, R12) seperti saat aplikasi dipakai.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Linimasa::mulai();

        $urutan = 0;
        Str::createUuidsUsing(function () use (&$urutan) {
            $urutan++;

            return Uuid::uuid5(Uuid::NAMESPACE_URL, "intellisys-talent-hub/seed/{$urutan}");
        });

        try {
            // Master data & akun internal sudah ada jauh sebelum proses rekrutmen pertama.
            Linimasa::pada(Linimasa::hari(-420), fn () => $this->call([
                KompetensiSeeder::class,
                KlienSeeder::class,
                AkunDemoSeeder::class,
                TalentSeeder::class,
                RekrutmenSeeder::class,
                OperasionalSeeder::class,
                DokumenSeeder::class,
                NotifikasiSeeder::class,
            ]));
        } finally {
            Str::createUuidsNormally();
        }
    }
}
