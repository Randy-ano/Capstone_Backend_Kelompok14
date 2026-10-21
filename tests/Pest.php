<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Buat user beserta profil sesuai role-nya (talent / hr_client / pegawai).
 */
function buatUser(App\Enums\Role $role): App\Models\User
{
    return match ($role) {
        App\Enums\Role::Talent => App\Models\Talent::factory()->create()->user,
        App\Enums\Role::HrClient => App\Models\HrClient::factory()->create()->user,
        App\Enums\Role::AccountManager => App\Models\Pegawai::factory()->accountManager()->create()->user,
        App\Enums\Role::TalentSupport => App\Models\Pegawai::factory()->talentSupport()->create()->user,
        App\Enums\Role::Manager => App\Models\Pegawai::factory()->manager()->create()->user,
    };
}

/**
 * Buat user dengan role tertentu lalu login sebagai user tersebut (Sanctum).
 */
function loginSebagai(App\Enums\Role $role): App\Models\User
{
    $user = buatUser($role);
    Laravel\Sanctum\Sanctum::actingAs($user);

    return $user;
}

/**
 * Semua role kecuali yang diberikan, untuk menguji akses lintas role (403).
 *
 * @return array<string, array{App\Enums\Role}>
 */
function roleSelain(App\Enums\Role ...$diizinkan): array
{
    return collect(App\Enums\Role::cases())
        ->reject(fn (App\Enums\Role $role) => in_array($role, $diizinkan, true))
        ->mapWithKeys(fn (App\Enums\Role $role) => [$role->value => [$role]])
        ->all();
}

/*
|--------------------------------------------------------------------------
| Fase 5: rantai rekrutmen (pengajuan → interview → evaluasi → placement)
|--------------------------------------------------------------------------
| Job berstatus diproses + talent di pool yang sedang diproses, lalu tiap tahap
| dibuat lewat factory dengan status yang diminta.
*/

function rekrutmenJob(?int $idKlien = null, array $atribut = []): App\Models\JobRequirement
{
    return App\Models\JobRequirement::factory()->create([
        'status_req' => App\Enums\StatusReq::Diproses,
        'id_klien' => $idKlien ?? App\Models\Klien::factory(),
        ...$atribut,
    ]);
}

function rekrutmenScreeningLolos(App\Models\JobRequirement $job, ?App\Models\Talent $talent = null): App\Models\ScreeningTalent
{
    $talent ??= App\Models\Talent::factory()->lengkap()->create();
    $talent->update(['status_kandidat' => App\Enums\StatusKandidat::DalamProses]);
    if (! $talent->talentPool) {
        App\Models\TalentPool::factory()->for($talent)->create();
    }

    return App\Models\ScreeningTalent::factory()->for($job)->for($talent)->lolos()->create();
}

function rekrutmenPengajuan(App\Models\JobRequirement $job, App\Enums\StatusPengajuan $status = App\Enums\StatusPengajuan::Diajukan, ?App\Models\Talent $talent = null): App\Models\PengajuanKandidat
{
    return App\Models\PengajuanKandidat::factory()->create([
        'id_screening' => rekrutmenScreeningLolos($job, $talent)->id_screening,
        'status_pengajuan' => $status,
    ]);
}

function rekrutmenInterview(App\Models\PengajuanKandidat $pengajuan, App\Enums\StatusInterview $status = App\Enums\StatusInterview::Dijadwalkan, ?string $tanggal = null): App\Models\JadwalInterview
{
    return App\Models\JadwalInterview::factory()->for($pengajuan, 'pengajuan')->create([
        'status_interview' => $status,
        'tanggal_interview' => $tanggal ?? now()->addDays(3)->toDateString(),
    ]);
}

function rekrutmenEvaluasi(App\Models\PengajuanKandidat $pengajuan, App\Enums\StatusEvaluasi $status = App\Enums\StatusEvaluasi::Diterima): App\Models\EvaluasiKandidat
{
    return App\Models\EvaluasiKandidat::factory()->create(['id_pengajuan' => $pengajuan->id_pengajuan, 'status_evaluasi' => $status]);
}
