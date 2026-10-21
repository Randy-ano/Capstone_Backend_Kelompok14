<?php

use App\Enums\Role;
use App\Models\Talent;
use App\Models\TalentPool;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Storage::fake('local'));

/**
 * Talent yang CV-nya sudah ada di disk private.
 */
function talentDenganCv(string $nama = 'Budi Santoso'): Talent
{
    $talent = Talent::factory()->lengkap()->create(['nama_lengkap' => $nama]);
    $path = "cv/talent-{$talent->id_talent}.pdf";
    Storage::disk('local')->put($path, "%PDF-1.4 CV {$nama}");
    $talent->update(['file_cv' => $path]);

    return $talent;
}

describe('GET /talent/cv/download', function () {
    it('mengirim file PDF CV milik talent yang sedang login', function () {
        talentDenganCv('Talent Lain');
        $talent = talentDenganCv('Budi Santoso');
        Sanctum::actingAs($talent->user);

        $response = $this->get('/api/v1/talent/cv/download')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('CV-budi-santoso.pdf');

        expect($response->streamedContent())->toBe('%PDF-1.4 CV Budi Santoso');
    });

    it('membalas 404 jika CV belum dibuat', function () {
        loginSebagai(Role::Talent);

        $this->getJson('/api/v1/talent/cv/download')
            ->assertNotFound()
            ->assertExactJson(['message' => 'CV belum tersedia. Generate CV terlebih dahulu.']);
    });

    it('membalas 404 jika file CV hilang dari storage', function () {
        $talent = talentDenganCv();
        Storage::disk('local')->delete($talent->file_cv);
        Sanctum::actingAs($talent->user);

        $this->getJson('/api/v1/talent/cv/download')
            ->assertNotFound()
            ->assertExactJson(['message' => 'CV belum tersedia. Generate CV terlebih dahulu.']);
    });

    it('menolak role selain talent (403)', function (Role $role) {
        loginSebagai($role);

        $this->getJson('/api/v1/talent/cv/download')
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::Talent));
});

describe('GET /talent-pool/{id}/cv', function () {
    it('mengirim file PDF CV talent untuk role internal', function (Role $role) {
        $pool = TalentPool::factory()->for(talentDenganCv('Rina Kartika'))->create();
        loginSebagai($role);

        $this->get("/api/v1/talent-pool/{$pool->id_talent_pool}/cv")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload('CV-rina-kartika.pdf');
    })->with([Role::AccountManager, Role::TalentSupport, Role::Manager]);

    it('membalas 404 jika talent belum membuat CV', function () {
        $pool = TalentPool::factory()->create();
        loginSebagai(Role::TalentSupport);

        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}/cv")
            ->assertNotFound()
            ->assertExactJson(['message' => 'CV belum tersedia. Generate CV terlebih dahulu.']);
    });

    it('membalas 404 jika data talent pool tidak ada', function () {
        loginSebagai(Role::TalentSupport);

        $this->getJson('/api/v1/talent-pool/99999/cv')
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    });

    it('menolak talent (termasuk pemilik CV) dan HR Client (403)', function (Role $role) {
        $pool = TalentPool::factory()->for(talentDenganCv())->create();
        loginSebagai($role);

        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}/cv")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::AccountManager, Role::TalentSupport, Role::Manager));

    it('menolak talent pemilik CV mengunduh lewat endpoint internal (403)', function () {
        $talent = talentDenganCv();
        $pool = TalentPool::factory()->for($talent)->create();
        Sanctum::actingAs($talent->user);

        $this->getJson("/api/v1/talent-pool/{$pool->id_talent_pool}/cv")->assertForbidden();
    });
});
