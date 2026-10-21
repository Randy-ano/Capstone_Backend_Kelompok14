<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Models\Dokumen;
use App\Models\Talent;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * Login sebagai talent berprofil lengkap (R4) dan kembalikan profilnya.
 */
function loginSebagaiTalentLengkap(): Talent
{
    $talent = Talent::factory()->lengkap()->create();
    Sanctum::actingAs($talent->user);

    return $talent;
}

describe('POST /talent/cv/generate', function () {
    it('membuat CV PDF di disk private, mengisi file_cv, dan mencatat dokumen jenis cv (R6)', function () {
        $talent = loginSebagaiTalentLengkap();
        $path = "cv/talent-{$talent->id_talent}.pdf";

        $response = $this->postJson('/api/v1/talent/cv/generate')
            ->assertOk()
            ->assertJsonPath('message', 'CV berhasil dibuat.')
            ->assertJsonPath('data.id_talent', $talent->id_talent)
            ->assertJsonPath('data.ada_cv', true)
            ->assertJsonMissingPath('data.file_cv');

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
        expect(Storage::disk('local')->get($path))->toStartWith('%PDF');

        $dokumen = Dokumen::sole();
        expect($talent->fresh()->file_cv)->toBe($path)
            ->and($dokumen)
            ->jenis_dokumen->toBe(JenisDokumen::Cv)
            ->status_dokumen->toBe(StatusDokumen::Draft)
            ->id_talent->toBe($talent->id_talent)
            ->id_user_uploader->toBe($talent->id_user)
            ->file_path->toBe($path)
            ->and($response->json('data.id_dokumen'))->toBe($dokumen->id_dokumen);
    });

    it('generate ulang menimpa CV lama tanpa membuat record dokumen baru', function () {
        loginSebagaiTalentLengkap();

        $this->postJson('/api/v1/talent/cv/generate')->assertOk();
        $this->postJson('/api/v1/talent/cv/generate')->assertOk();

        expect(Dokumen::where('jenis_dokumen', JenisDokumen::Cv)->count())->toBe(1);
    });

    it('menolak jika profil belum lengkap (422)', function () {
        $talent = loginSebagai(Role::Talent)->talent;

        $this->postJson('/api/v1/talent/cv/generate')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profil']);

        expect($talent->fresh()->file_cv)->toBeNull()
            ->and(Dokumen::count())->toBe(0)
            ->and(Storage::disk('local')->allFiles())->toBeEmpty();
    });

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->postJson('/api/v1/talent/cv/generate')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);

        expect(Dokumen::count())->toBe(0);
    })->with(roleSelain(Role::Talent));

    it('hanya membuat CV untuk talent yang sedang login, bukan talent lain', function () {
        $lain = Talent::factory()->lengkap()->create();
        $talent = loginSebagaiTalentLengkap();

        $this->postJson('/api/v1/talent/cv/generate')
            ->assertOk()
            ->assertJsonPath('data.id_talent', $talent->id_talent);

        expect($lain->fresh()->file_cv)->toBeNull()
            ->and($lain->dokumen()->exists())->toBeFalse();
        Storage::disk('local')->assertMissing("cv/talent-{$lain->id_talent}.pdf");
    });

    it('menolak request tanpa login (401)', function () {
        $this->postJson('/api/v1/talent/cv/generate')->assertUnauthorized();
    });
});
