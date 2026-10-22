<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Seed tetap agar data acak (kompetensi talent) sama setiap migrate:fresh --seed.
        fake()->seed(2026);

        $this->call([
            KompetensiSeeder::class,
            KlienSeeder::class,
            AkunDemoSeeder::class,
            TalentSeeder::class,
            JobRequirementSeeder::class,
            ScreeningSeeder::class,
            RekrutmenSeeder::class,
            OperasionalSeeder::class,
        ]);
    }
}
