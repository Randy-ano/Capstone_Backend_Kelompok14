<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\Placement;
use App\Models\TandaTanganDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

/**
 * Dokumen draft milik AM yang login, terkait satu placement (klien + talent), beserta semua kandidat penandatangan.
 *
 * @return array{dokumen: Dokumen, am: App\Models\User, manager: App\Models\User, hr: App\Models\User, hrLain: App\Models\User, ts: App\Models\User, talent: App\Models\User}
 */
function siapkanDokumen(JenisDokumen $jenis): array
{
    $placement = Placement::factory()->create();
    $am = loginSebagai(Role::AccountManager);

    return [
        'dokumen' => Dokumen::factory()->create([
            'id_user_uploader' => $am->id_user,
            'jenis_dokumen' => $jenis,
            'id_klien' => $placement->id_klien,
            'id_talent' => $placement->id_talent,
            'id_placement' => $placement->id_placement,
        ]),
        'am' => $am,
        'manager' => buatUser(Role::Manager),
        'hr' => HrClient::factory()->create(['id_klien' => $placement->id_klien])->user,
        'hrLain' => buatUser(Role::HrClient),
        'ts' => buatUser(Role::TalentSupport),
        'talent' => $placement->talent->user,
    ];
}

describe('GET /dokumen/{id}/calon-penandatangan sesuai jenis dokumen', function () {
    it('quotation, PO, PKS: hanya AM, Manager, dan HR Client klien dokumen', function (JenisDokumen $jenis) {
        $d = siapkanDokumen($jenis);

        $calon = $this->getJson("/api/v1/dokumen/{$d['dokumen']->id_dokumen}/calon-penandatangan")->assertOk()->json('data.*.id_user');

        expect($calon)->toContain($d['am']->id_user, $d['manager']->id_user, $d['hr']->id_user)
            ->not->toContain($d['ts']->id_user)
            ->not->toContain($d['talent']->id_user)
            ->not->toContain($d['hrLain']->id_user);
    })->with([JenisDokumen::Quotation, JenisDokumen::PurchaseOrder, JenisDokumen::Pks]);

    it('kontrak kerja & surat peringatan: talent terkait, AM, dan Manager', function (JenisDokumen $jenis) {
        $d = siapkanDokumen($jenis);

        $calon = $this->getJson("/api/v1/dokumen/{$d['dokumen']->id_dokumen}/calon-penandatangan")->assertOk()->json('data.*.id_user');

        expect($calon)->toContain($d['am']->id_user, $d['manager']->id_user, $d['talent']->id_user)
            ->not->toContain($d['ts']->id_user)
            ->not->toContain($d['hr']->id_user);
    })->with([JenisDokumen::KontrakKerja, JenisDokumen::SuratPeringatan]);

    it('lainnya: AM, Manager, HR Client klien, dan talent terkait; tanpa Talent Support', function () {
        $d = siapkanDokumen(JenisDokumen::Lainnya);

        $calon = $this->getJson("/api/v1/dokumen/{$d['dokumen']->id_dokumen}/calon-penandatangan")->assertOk()->json('data.*.id_user');

        expect($calon)->toContain($d['am']->id_user, $d['manager']->id_user, $d['hr']->id_user, $d['talent']->id_user)
            ->not->toContain($d['ts']->id_user)
            ->not->toContain($d['hrLain']->id_user);
    });
});

describe('POST /dokumen/{id}/penandatangan mengikuti aturan yang sama', function () {
    it('menolak penandatangan di luar aturan jenis dokumen (422)', function (JenisDokumen $jenis, string $kunci) {
        $d = siapkanDokumen($jenis);

        $this->postJson("/api/v1/dokumen/{$d['dokumen']->id_dokumen}/penandatangan", ['id_user' => [$d['am']->id_user, $d[$kunci]->id_user]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_user.0', 'Penandatangan tidak sesuai jenis dokumen. Pilih dari daftar calon penandatangan.');

        expect($d['dokumen']->fresh()->status_dokumen)->toBe(StatusDokumen::Draft);
    })->with([
        'quotation + talent' => [JenisDokumen::Quotation, 'talent'],
        'quotation + talent support' => [JenisDokumen::Quotation, 'ts'],
        'PKS + HR klien lain' => [JenisDokumen::Pks, 'hrLain'],
        'kontrak kerja + HR klien' => [JenisDokumen::KontrakKerja, 'hr'],
        'surat peringatan + talent support' => [JenisDokumen::SuratPeringatan, 'ts'],
    ]);

    it('menerima penandatangan yang sesuai jenis dokumen', function (JenisDokumen $jenis, array $kunci) {
        $d = siapkanDokumen($jenis);

        $this->postJson("/api/v1/dokumen/{$d['dokumen']->id_dokumen}/penandatangan", ['id_user' => array_map(fn ($k) => $d[$k]->id_user, $kunci)])
            ->assertOk()
            ->assertJsonPath('data.status_dokumen', 'menunggu_ttd')
            ->assertJsonCount(count($kunci), 'data.penandatangan');
    })->with([
        'quotation' => [JenisDokumen::Quotation, ['am', 'manager', 'hr']],
        'kontrak kerja' => [JenisDokumen::KontrakKerja, ['talent', 'am', 'manager']],
    ]);
});

describe('Dokumen unggahan talent', function () {
    it('penandatangan otomatis talent itu sendiri, langsung menunggu tanda tangan', function () {
        $talent = loginTalentPlacement(Placement::factory()->create());

        $this->post('/api/v1/dokumen/upload', ['file' => UploadedFile::fake()->create('surat-pernyataan.pdf', 20, 'application/pdf'), 'jenis_dokumen' => 'lainnya'], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.status_dokumen', 'menunggu_ttd')
            ->assertJsonCount(1, 'data.penandatangan')
            ->assertJsonPath('data.penandatangan.0.id_user', $talent->id_user)
            ->assertJsonPath('data.aksi', ['boleh_atur_penandatangan' => false, 'perlu_tanda_tangan_saya' => true]);

        $dokumen = Dokumen::sole();
        $this->post("/api/v1/dokumen/{$dokumen->id_dokumen}/tanda-tangan", ['keputusan' => 'ditandatangani', 'file_tanda_tangan' => UploadedFile::fake()->create('ttd.png', 20, 'image/png')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.status_dokumen', 'ditandatangani');
    });

    it('talent tidak boleh mengatur penandatangan maupun melihat calon (403)', function () {
        $placement = Placement::factory()->create();
        $talent = loginTalentPlacement($placement);
        $dokumen = Dokumen::factory()->create(['id_user_uploader' => $talent->id_user, 'id_talent' => $placement->id_talent, 'jenis_dokumen' => JenisDokumen::Cv]);
        $am = buatUser(Role::AccountManager);
        $pesan = ['message' => 'Penandatangan dokumen yang diunggah talent ditentukan otomatis (talent itu sendiri).'];

        $this->getJson("/api/v1/dokumen/{$dokumen->id_dokumen}/calon-penandatangan")->assertForbidden()->assertExactJson($pesan);
        $this->postJson("/api/v1/dokumen/{$dokumen->id_dokumen}/penandatangan", ['id_user' => [$am->id_user]])->assertForbidden()->assertExactJson($pesan);

        expect(TandaTanganDokumen::count())->toBe(0);
    });
});
