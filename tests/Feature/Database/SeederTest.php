<?php

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(fn () => $this->seed());

it('membuat 1 akun demo per role dengan password "password"', function (string $email, Role $role, string $profil) {
    $user = User::where('email', $email)->firstOrFail();

    expect($user->role)->toBe($role)
        ->and($user->status_aktif)->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->{$profil})->not->toBeNull();
})->with([
    ['talent@intellisys.test', Role::Talent, 'talent'],
    ['hr@intellisys.test', Role::HrClient, 'hrClient'],
    ['am@intellisys.test', Role::AccountManager, 'pegawai'],
    ['ts@intellisys.test', Role::TalentSupport, 'pegawai'],
    ['manager@intellisys.test', Role::Manager, 'pegawai'],
]);

it('membuat master data dan data contoh sesuai jumlah di spec', function () {
    expect(Kompetensi::count())->toBe(30)
        ->and(Klien::count())->toBe(3)
        ->and(Talent::count())->toBe(20)
        ->and(JobRequirement::count())->toBe(5);
});

it('memasukkan hanya talent berprofil lengkap ke talent pool', function () {
    $lengkap = Talent::where('status_kelengkapan', StatusKelengkapan::Lengkap)->get();

    expect($lengkap)->not->toBeEmpty()
        ->and(Talent::where('status_kelengkapan', StatusKelengkapan::BelumLengkap)->count())->toBeGreaterThan(0)
        ->and(Talent::has('talentPool')->count())->toBe($lengkap->count());

    $lengkap->each(fn (Talent $talent) => expect($talent->pendidikan)->not->toBeEmpty()
        ->and($talent->pengalaman_kerja)->not->toBeEmpty()
        ->and($talent->kompetensi)->not->toBeEmpty());
});

it('membuat job requirement dengan minimal 1 kompetensi dan log progres untuk yang diajukan', function () {
    JobRequirement::with('kompetensi', 'progres')->get()->each(function (JobRequirement $job) {
        expect($job->kompetensi)->not->toBeEmpty()
            ->and($job->progres)->toHaveCount($job->status_req === StatusReq::Diajukan ? 1 : 0);
    });
});
