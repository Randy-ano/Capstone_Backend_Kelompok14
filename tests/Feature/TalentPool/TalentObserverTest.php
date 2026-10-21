<?php

use App\Enums\StatusKelengkapan;
use App\Enums\StatusKetersediaan;
use App\Models\Talent;
use App\Models\TalentPool;
use Database\Seeders\AkunDemoSeeder;
use Database\Seeders\KlienSeeder;
use Database\Seeders\KompetensiSeeder;
use Database\Seeders\TalentSeeder;

/**
 * R5 lewat TalentObserver (white box: event updated saja, firstOrCreate).
 */
describe('TalentObserver (R5)', function () {
    it('membuat entri talent pool saat status berubah menjadi lengkap', function () {
        $talent = Talent::factory()->create();

        $talent->update(['status_kelengkapan' => StatusKelengkapan::Lengkap]);

        $pool = TalentPool::sole();
        expect($pool->id_talent)->toBe($talent->id_talent)
            ->and($pool->status_ketersediaan)->toBe(StatusKetersediaan::Tersedia)
            ->and($pool->tanggal_masuk->isToday())->toBeTrue();
    });

    it('tidak membuat entri ganda jika talent sudah ada di pool', function () {
        $pool = TalentPool::factory()->create(['tag_keahlian' => 'Tetap']);
        $talent = $pool->talent;

        $talent->update(['status_kelengkapan' => StatusKelengkapan::BelumLengkap]);
        $talent->update(['status_kelengkapan' => StatusKelengkapan::Lengkap]);

        expect(TalentPool::count())->toBe(1)
            ->and($pool->fresh()->tag_keahlian)->toBe('Tetap');
    });

    it('tidak bereaksi pada pembuatan talent, perubahan ke belum lengkap, atau perubahan kolom lain', function () {
        Talent::factory()->create(['status_kelengkapan' => StatusKelengkapan::Lengkap]);
        $talent = Talent::factory()->create();

        $talent->update(['nama_lengkap' => 'Nama Baru']);
        $talent->update(['status_kelengkapan' => StatusKelengkapan::BelumLengkap]);

        expect(TalentPool::count())->toBe(0);
    });

    it('tidak membuat entri ganda saat seeder dijalankan dengan model event aktif', function () {
        // Tanpa WithoutModelEvents (DatabaseSeeder memakainya), observer ikut aktif.
        $this->seed([KompetensiSeeder::class, KlienSeeder::class, AkunDemoSeeder::class, TalentSeeder::class]);

        $lengkap = Talent::where('status_kelengkapan', StatusKelengkapan::Lengkap)->count();
        expect(TalentPool::count())->toBe($lengkap)
            ->and(TalentPool::distinct()->count('id_talent'))->toBe($lengkap);

        // Talent seed yang sudah lengkap diperbarui lagi: tetap satu entri.
        Talent::where('status_kelengkapan', StatusKelengkapan::Lengkap)->get()
            ->each(fn (Talent $t) => $t->update(['status_kelengkapan' => StatusKelengkapan::BelumLengkap]))
            ->each(fn (Talent $t) => $t->update(['status_kelengkapan' => StatusKelengkapan::Lengkap]));

        expect(TalentPool::count())->toBe($lengkap);
    });
});
