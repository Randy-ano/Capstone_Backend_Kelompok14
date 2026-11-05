<?php

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusPengajuan;
use App\Models\Dokumen;
use App\Models\HrClient;
use App\Models\PengajuanKandidat;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Revisi 2026-11-06: dokumen kerja sama berurutan (R34–R36).
 * Kandidat diterima → Quotation (AM) → Purchase Order (HR Client) → PKS (AM/Manager) → Kontrak Kerja (AM) → penempatan (TS).
 */
beforeEach(fn () => Storage::fake('local'));

/** Kandidat diterima di evaluasi + HR Client kliennya. */
function kandidatDiterima(): array
{
    $pengajuan = rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview);
    rekrutmenEvaluasi($pengajuan);
    $hr = HrClient::factory()->create(['id_klien' => $pengajuan->jobRequirement->id_klien])->user;

    return [$pengajuan, $hr];
}

function unggahBerurutan(User $user, string $jenis, array $tujuan): Illuminate\Testing\TestResponse
{
    Sanctum::actingAs($user);

    return test()->post('/api/v1/dokumen/upload', [
        'file' => UploadedFile::fake()->create("{$jenis}.pdf", 20, 'application/pdf'),
        'jenis_dokumen' => $jenis,
        ...$tujuan,
    ], ['Accept' => 'application/json']);
}

/** Tentukan penandatangan lalu semuanya menandatangani lewat API. */
function selesaikanTandaTangan(User $pengunggah, int $idDokumen, array $penandatangan): void
{
    Sanctum::actingAs($pengunggah);
    test()->postJson("/api/v1/dokumen/{$idDokumen}/penandatangan", ['id_user' => array_map(fn (User $u) => $u->id_user, $penandatangan)])->assertOk();
    foreach ($penandatangan as $u) {
        Sanctum::actingAs($u);
        test()->post("/api/v1/dokumen/{$idDokumen}/tanda-tangan", [
            'keputusan' => 'ditandatangani',
            'file_tanda_tangan' => UploadedFile::fake()->create('ttd.png', 10, 'image/png'),
        ], ['Accept' => 'application/json'])->assertOk();
    }
}

/** Peran penandatangan yang disarankan (unik, terurut). */
function perannya(Illuminate\Support\Collection $calon): array
{
    return $calon->where('disarankan', true)->pluck('role')->unique()->sort()->values()->all();
}

describe('Alur lengkap satu kandidat', function () {
    it('evaluasi diterima → Quotation → PO → PKS → Kontrak Kerja → penempatan, dengan penandatangan yang disarankan', function () {
        [$pengajuan, $hr] = kandidatDiterima();
        $am = buatUser(Role::AccountManager);
        $manager = buatUser(Role::Manager);
        $ts = buatUser(Role::TalentSupport);
        $talent = $pengajuan->talent->user;
        $job = ['id_job_req' => $pengajuan->id_job_req];

        // Quotation oleh AM; saran penandatangan = HR Client.
        $q = unggahBerurutan($am, 'quotation', $job)->assertCreated()
            ->assertJsonPath('data.permintaan.id_job_req', $pengajuan->id_job_req)
            ->assertJsonPath('data.klien.id_klien', $pengajuan->jobRequirement->id_klien)
            ->json('data.id_dokumen');
        $calon = collect($this->getJson("/api/v1/dokumen/{$q}/calon-penandatangan")->assertOk()->json('data'));
        expect(perannya($calon))->toBe(['hr_client'])->and($calon->where('disarankan', true)->pluck('id_user'))->toContain($hr->id_user);
        selesaikanTandaTangan($am, $q, [$hr]);

        // PO oleh HR Client; saran = Manager.
        $po = unggahBerurutan($hr, 'purchase_order', $job)->assertCreated()->json('data.id_dokumen');
        $calon = collect($this->getJson("/api/v1/dokumen/{$po}/calon-penandatangan")->json('data'));
        expect(perannya($calon))->toBe(['manager']);
        selesaikanTandaTangan($hr, $po, [$manager]);

        // PKS oleh Manager; saran = Manager + HR Client.
        $pks = unggahBerurutan($manager, 'pks', $job)->assertCreated()->json('data.id_dokumen');
        $calon = collect($this->getJson("/api/v1/dokumen/{$pks}/calon-penandatangan")->json('data'));
        expect(perannya($calon))->toBe(['hr_client', 'manager']);
        selesaikanTandaTangan($manager, $pks, [$manager, $hr]);

        // Penempatan masih ditolak: Kontrak Kerja belum ada.
        Sanctum::actingAs($ts);
        $this->postJson('/api/v1/placement', ['id_evaluasi' => $pengajuan->evaluasi->id_evaluasi, 'tanggal_mulai' => now()->addWeek()->toDateString()])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_evaluasi.0', 'Penempatan belum bisa dibuat. Dokumen yang belum selesai ditandatangani: Kontrak Kerja.');

        // Kontrak Kerja oleh AM untuk kandidat; saran = talent + Manager. Talent belum ditempatkan tetap bisa menandatangani.
        $kk = unggahBerurutan($am, 'kontrak_kerja', ['id_pengajuan' => $pengajuan->id_pengajuan])->assertCreated()
            ->assertJsonPath('data.talent.id_talent', $pengajuan->id_talent)
            ->assertJsonPath('data.id_pengajuan', $pengajuan->id_pengajuan)
            ->json('data.id_dokumen');
        $calon = collect($this->getJson("/api/v1/dokumen/{$kk}/calon-penandatangan")->json('data'));
        expect(perannya($calon))->toBe(['manager', 'talent'])->and($calon->where('disarankan', true)->pluck('id_user'))->toContain($talent->id_user);
        selesaikanTandaTangan($am, $kk, [$talent, $manager]);

        // TS membuat penempatan; Kontrak Kerja ditautkan.
        Sanctum::actingAs($ts);
        $idPlacement = $this->postJson('/api/v1/placement', ['id_evaluasi' => $pengajuan->evaluasi->id_evaluasi, 'tanggal_mulai' => now()->addWeek()->toDateString()])
            ->assertCreated()->json('data.id_placement');
        expect(Dokumen::find($kk)->id_placement)->toBe($idPlacement);

        Sanctum::actingAs($am);
        $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")
            ->assertJsonPath('data.kelengkapan_dokumen.selesai', true)
            ->assertJsonPath('data.kelengkapan_dokumen.kurang', []);
    });
});

describe('Urutan dipaksa sistem (R34)', function () {
    it('Quotation ditolak jika permintaan belum punya kandidat diterima', function () {
        $am = buatUser(Role::AccountManager);
        $job = rekrutmenJob();
        rekrutmenPengajuan($job, StatusPengajuan::LanjutInterview);

        unggahBerurutan($am, 'quotation', ['id_job_req' => $job->id_job_req])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_job_req.0', 'Quotation belum bisa diunggah: permintaan ini belum memiliki kandidat yang diterima di evaluasi.');
    });

    it('menolak dokumen yang dokumen sebelumnya belum selesai ditandatangani (422)', function (string $jenis, ?JenisDokumen $ada, string $pesan) {
        [$pengajuan, $hr] = kandidatDiterima();
        if ($ada) {
            rekrutmenDokumen($pengajuan, $ada, StatusDokumen::MenungguTtd);
        }
        $user = $jenis === 'purchase_order' ? $hr : buatUser(Role::AccountManager);
        $tujuan = $jenis === 'kontrak_kerja' ? ['id_pengajuan' => $pengajuan->id_pengajuan] : ['id_job_req' => $pengajuan->id_job_req];
        $field = array_key_first($tujuan);

        unggahBerurutan($user, $jenis, $tujuan)
            ->assertUnprocessable()
            ->assertJsonPath("errors.{$field}.0", $pesan);
        expect(Dokumen::where('jenis_dokumen', $jenis)->count())->toBe(0);
    })->with([
        'PO tanpa Quotation' => ['purchase_order', null, 'Purchase Order belum bisa diunggah: Quotation untuk permintaan ini belum selesai ditandatangani.'],
        'PO saat Quotation menunggu' => ['purchase_order', JenisDokumen::Quotation, 'Purchase Order belum bisa diunggah: Quotation untuk permintaan ini belum selesai ditandatangani.'],
        'PKS saat PO menunggu' => ['pks', JenisDokumen::PurchaseOrder, 'PKS belum bisa diunggah: Purchase Order untuk permintaan ini belum selesai ditandatangani.'],
        'Kontrak saat PKS menunggu' => ['kontrak_kerja', JenisDokumen::Pks, 'Kontrak Kerja belum bisa diunggah: PKS untuk permintaan ini belum selesai ditandatangani.'],
    ]);

    it('Quotation dari template juga mengikuti urutan dan PO tidak bisa dibuat AM dari template', function () {
        $am = loginSebagai(Role::AccountManager);
        $job = rekrutmenJob();
        $data = ['jenis_dokumen' => 'quotation', 'id_job_req' => $job->id_job_req, 'nomor_dokumen' => 'Q/1', 'tanggal_dokumen' => now()->toDateString(), 'perihal' => 'Penawaran', 'items' => [['deskripsi' => 'Talent', 'jumlah' => 1, 'harga_satuan' => 1000]]];

        $this->postJson('/api/v1/dokumen/generate', $data)->assertUnprocessable()->assertJsonValidationErrors(['id_job_req']);
        $this->postJson('/api/v1/dokumen/generate', [...$data, 'jenis_dokumen' => 'purchase_order'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Purchase Order hanya dapat diunggah oleh HR Client.');
        expect($am->id_user)->toBeInt();
    });

    it('menolak dokumen jenis sama yang masih dalam proses tanda tangan', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::Quotation, StatusDokumen::MenungguTtd);

        unggahBerurutan(buatUser(Role::AccountManager), 'quotation', ['id_job_req' => $pengajuan->id_job_req])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_job_req.0', 'Quotation untuk permintaan ini masih dalam proses tanda tangan. Selesaikan dokumen tersebut terlebih dahulu.');
    });

    it('Quotation yang ditolak boleh diganti dengan Quotation baru', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::Quotation, StatusDokumen::Ditolak);

        unggahBerurutan(buatUser(Role::AccountManager), 'quotation', ['id_job_req' => $pengajuan->id_job_req])->assertCreated();
    });

    it('mewajibkan tujuan: permintaan untuk Quotation/PO/PKS, kandidat diterima untuk Kontrak Kerja', function () {
        $am = buatUser(Role::AccountManager);
        unggahBerurutan($am, 'quotation', [])->assertUnprocessable()->assertJsonPath('errors.id_job_req.0', 'Pilih permintaan kebutuhan untuk Quotation.');
        unggahBerurutan($am, 'kontrak_kerja', [])->assertUnprocessable()->assertJsonPath('errors.id_pengajuan.0', 'Pilih kandidat yang diterima untuk Kontrak Kerja.');

        $belum = rekrutmenPengajuan(rekrutmenJob(), StatusPengajuan::LanjutInterview);
        unggahBerurutan($am, 'kontrak_kerja', ['id_pengajuan' => $belum->id_pengajuan])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_pengajuan.0', 'Kontrak Kerja hanya dapat dibuat untuk kandidat yang diterima di evaluasi.');
    });

    it('Kontrak Kerja perpanjangan lewat id_placement memakai pengajuan penempatan itu', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan);
        $placement = App\Models\Placement::factory()->create(['id_evaluasi' => $pengajuan->evaluasi->id_evaluasi, 'id_talent' => $pengajuan->id_talent, 'id_job_req' => $pengajuan->id_job_req, 'id_klien' => $pengajuan->jobRequirement->id_klien]);

        unggahBerurutan(buatUser(Role::AccountManager), 'kontrak_kerja', ['id_placement' => $placement->id_placement])
            ->assertCreated()
            ->assertJsonPath('data.id_pengajuan', $pengajuan->id_pengajuan)
            ->assertJsonPath('data.id_placement', $placement->id_placement);
    });
});

describe('Hak unggah per role (R35)', function () {
    it('menolak role yang tidak berhak mengunggah (403)', function (string $jenis, Role $role, string $pesan) {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::Pks);
        $tujuan = $jenis === 'kontrak_kerja' ? ['id_pengajuan' => $pengajuan->id_pengajuan] : ['id_job_req' => $pengajuan->id_job_req];
        $user = $role === Role::HrClient ? HrClient::factory()->create(['id_klien' => $pengajuan->jobRequirement->id_klien])->user : buatUser($role);

        unggahBerurutan($user, $jenis, $tujuan)->assertForbidden()->assertJsonPath('message', $pesan);
    })->with([
        'Quotation oleh HR Client' => ['quotation', Role::HrClient, 'Quotation hanya dapat diunggah oleh Account Manager.'],
        'Quotation oleh Manager' => ['quotation', Role::Manager, 'Quotation hanya dapat diunggah oleh Account Manager.'],
        'PO oleh AM' => ['purchase_order', Role::AccountManager, 'Purchase Order hanya dapat diunggah oleh HR Client.'],
        'PO oleh Manager' => ['purchase_order', Role::Manager, 'Purchase Order hanya dapat diunggah oleh HR Client.'],
        'PKS oleh HR Client' => ['pks', Role::HrClient, 'PKS hanya dapat diunggah oleh Account Manager atau Manager.'],
        'Kontrak oleh Manager' => ['kontrak_kerja', Role::Manager, 'Kontrak Kerja hanya dapat diunggah oleh Account Manager.'],
        'Kontrak oleh HR Client' => ['kontrak_kerja', Role::HrClient, 'Kontrak Kerja hanya dapat diunggah oleh Account Manager.'],
    ]);

    it('HR Client tidak bisa mengunggah PO untuk permintaan klien lain (403)', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::Quotation);

        unggahBerurutan(buatUser(Role::HrClient), 'purchase_order', ['id_job_req' => $pengajuan->id_job_req])
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke permintaan kebutuhan ini.');
    });

    it('Talent Support tidak bisa mengunggah dokumen (403)', function () {
        [$pengajuan] = kandidatDiterima();

        unggahBerurutan(buatUser(Role::TalentSupport), 'quotation', ['id_job_req' => $pengajuan->id_job_req])->assertForbidden();
    });
});

describe('GET /dokumen/tujuan', function () {
    it('hanya menampilkan permintaan / kandidat yang memenuhi urutan', function () {
        [$siapPo, $hr] = kandidatDiterima();
        rekrutmenDokumen($siapPo, JenisDokumen::Quotation);
        [$siapKontrak] = kandidatDiterima();
        rekrutmenDokumen($siapKontrak, JenisDokumen::Pks);
        [$baru] = kandidatDiterima();
        rekrutmenJob(); // tanpa kandidat diterima
        $am = loginSebagai(Role::AccountManager);

        $this->getJson('/api/v1/dokumen/tujuan?jenis=quotation')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_job_req', $baru->id_job_req);
        $this->getJson('/api/v1/dokumen/tujuan?jenis=pks')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/dokumen/tujuan?jenis=kontrak_kerja')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_pengajuan', $siapKontrak->id_pengajuan)
            ->assertJsonPath('data.0.nama_lengkap', $siapKontrak->talent->nama_lengkap);

        Sanctum::actingAs($hr);
        $this->getJson('/api/v1/dokumen/tujuan?jenis=purchase_order')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id_job_req', $siapPo->id_job_req);
        $this->getJson('/api/v1/dokumen/tujuan?jenis=quotation')->assertForbidden();
        $this->getJson('/api/v1/dokumen/tujuan?jenis=lainnya')->assertUnprocessable();
        expect($am->id_user)->toBeInt();
    });
});

describe('Kelengkapan dokumen terlihat (R36)', function () {
    it('detail pengajuan menampilkan daftar periksa untuk HR Client & internal, tidak untuk talent', function () {
        [$pengajuan, $hr] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::Pks, StatusDokumen::MenungguTtd);

        Sanctum::actingAs($hr);
        $data = $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")->assertOk()->json('data.kelengkapan_dokumen');
        expect(collect($data['langkah'])->map(fn ($l) => [$l['jenis'], $l['label_status']])->all())->toBe([
            ['quotation', 'Selesai'],
            ['purchase_order', 'Selesai'],
            ['pks', 'Menunggu tanda tangan'],
            ['kontrak_kerja', 'Belum ada'],
        ])->and($data['kurang'])->toBe(['PKS', 'Kontrak Kerja'])
            ->and($data['langkah'][0]['dokumen']['id_dokumen'])->toBeInt();

        // Tautan daftar periksa: GET /dokumen/{id} sesuai scope.
        $idQuotation = $data['langkah'][0]['dokumen']['id_dokumen'];
        $this->getJson("/api/v1/dokumen/{$idQuotation}")->assertOk()->assertJsonPath('data.jenis_dokumen', 'quotation');
        Sanctum::actingAs(buatUser(Role::HrClient));
        $this->getJson("/api/v1/dokumen/{$idQuotation}")->assertForbidden();

        Sanctum::actingAs($pengajuan->talent->user);
        $this->getJson("/api/v1/pengajuan/{$pengajuan->id_pengajuan}")->assertOk()->assertJsonMissingPath('data.kelengkapan_dokumen');
    });

    it('detail permintaan & monitoring progres menampilkan tahap dokumen', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan, JenisDokumen::PurchaseOrder);
        $lain = rekrutmenPengajuan($pengajuan->jobRequirement, StatusPengajuan::LanjutInterview);
        rekrutmenEvaluasi($lain);
        loginSebagai(Role::Manager);

        $this->getJson("/api/v1/job-requirements/{$pengajuan->id_job_req}")
            ->assertJsonPath('data.kelengkapan_dokumen.tahap.label', 'PKS: Belum ada')
            ->assertJsonPath('data.kelengkapan_dokumen.tahap.langkah_selesai', 2)
            ->assertJsonCount(2, 'data.kelengkapan_dokumen.kontrak_kerja')
            ->assertJsonPath('data.kelengkapan_dokumen.kontrak_kerja.0.label_status', 'Belum ada');

        $progres = collect($this->getJson('/api/v1/progres?per_page=100')->json('data'))->keyBy('id_job_req');
        expect($progres[$pengajuan->id_job_req]['tahap_dokumen']['label'])->toBe('PKS: Belum ada')
            ->and($progres->firstWhere('id_job_req', '!=', $pengajuan->id_job_req)['tahap_dokumen'] ?? null)->toBeNull();
    });

    it('ringkasan tahap menghitung Kontrak Kerja per kandidat', function () {
        [$pengajuan] = kandidatDiterima();
        rekrutmenDokumen($pengajuan);
        $lain = rekrutmenPengajuan($pengajuan->jobRequirement, StatusPengajuan::LanjutInterview);
        rekrutmenEvaluasi($lain);
        loginSebagai(Role::AccountManager);

        $this->getJson("/api/v1/job-requirements/{$pengajuan->id_job_req}")
            ->assertJsonPath('data.kelengkapan_dokumen.tahap.label', 'Kontrak Kerja: 1 dari 2 selesai')
            ->assertJsonPath('data.kelengkapan_dokumen.tahap.selesai', false);
    });
});

describe('Talent & Kontrak Kerja sebelum ditempatkan', function () {
    it('talent yang punya Kontrak Kerja bisa membuka dokumen walau belum ditempatkan; tanpa kontrak ditolak', function () {
        [$pengajuan] = kandidatDiterima();
        $talent = $pengajuan->talent->user;
        Sanctum::actingAs($talent);
        $this->getJson('/api/v1/dokumen')->assertForbidden();
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.akses_dokumen', false);

        rekrutmenDokumen($pengajuan, JenisDokumen::KontrakKerja, StatusDokumen::MenungguTtd);
        $this->getJson('/api/v1/dokumen')->assertOk()->assertJsonPath('data.0.jenis_dokumen', 'kontrak_kerja');
        $this->getJson('/api/v1/auth/me')->assertJsonPath('data.akses_dokumen', true)->assertJsonPath('data.sudah_ditempatkan', false);
        $this->getJson('/api/v1/cuti')->assertForbidden();
    });
});
