<?php

use App\Enums\Role;
use App\Enums\StatusKandidat;
use App\Enums\StatusKetersediaan;
use App\Enums\StatusReq;
use App\Models\JobRequirement;
use App\Models\Kompetensi;
use App\Models\PengalamanKerjaTalent;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\TalentPool;
use App\Services\RekomendasiService;
use Illuminate\Support\Carbon;

/** Job diajukan dengan kompetensi tertentu. */
function jobDengan(array $kompetensi, int $pengalamanMin = 0, StatusReq $status = StatusReq::Diajukan): JobRequirement
{
    $job = JobRequirement::factory()->create(['pengalaman_min' => $pengalamanMin, 'status_req' => $status]);
    $job->kompetensi()->attach(collect($kompetensi)->pluck('id_kompetensi'));

    return $job;
}

/** Talent di talent pool (tersedia) dengan kompetensi dan pengalaman kerja (tahun penuh, masih bekerja). */
function kandidat(string $nama, array $kompetensi, float $tahunPengalaman = 0, array $talent = [], array $pool = []): Talent
{
    $t = Talent::factory()->lengkap()->create(['nama_lengkap' => $nama, ...$talent]);
    $t->kompetensi()->attach(collect($kompetensi)->pluck('id_kompetensi'));
    if ($tahunPengalaman > 0) {
        PengalamanKerjaTalent::factory()->for($t)->create([
            'tanggal_mulai' => now()->subDays((int) round($tahunPengalaman * 365.25)),
            'tanggal_selesai' => null,
            'masih_bekerja' => true,
        ]);
    }
    TalentPool::factory()->for($t)->create($pool);

    return $t;
}

describe('RekomendasiService::skor (R9)', function () {
    it('menghitung skor 0%, sebagian, dan 100%', function (int $cocok, float $tahun, int $pengalamanMin, float $skor) {
        $k = Kompetensi::factory()->count(4)->create();
        $job = jobDengan($k->all(), $pengalamanMin);
        $talent = kandidat('Uji', $k->take($cocok)->all(), $tahun);

        $hasil = app(RekomendasiService::class)->skor($job->load('kompetensi'), $talent->load(['kompetensi', 'pengalamanKerjaTalent']));

        expect($hasil['skor_kecocokan'])->toBe($skor);
    })->with([
        '0%: tidak ada kompetensi & tanpa pengalaman' => [0, 0, 2, 0.0],
        'sebagian: 3/4 kompetensi, pengalaman 1/2 tahun (contoh spec)' => [3, 1, 2, 70.0],
        '100%: semua kompetensi & pengalaman cukup' => [4, 3, 2, 100.0],
        'pengalaman_min 0 selalu 100 untuk pengalaman' => [2, 0, 0, 60.0],
    ]);

    it('menggabungkan periode pengalaman yang tumpang tindih sesuai contoh spec', function () {
        Carbon::setTestNow('2024-06-01');
        $k = Kompetensi::factory()->count(4)->create();
        $job = jobDengan($k->all(), 2);
        $talent = kandidat('Uji', $k->take(3)->all());
        PengalamanKerjaTalent::factory()->for($talent)->create(['tanggal_mulai' => '2023-01-01', 'tanggal_selesai' => '2023-06-30', 'masih_bekerja' => false]);
        PengalamanKerjaTalent::factory()->for($talent)->create(['tanggal_mulai' => '2023-04-01', 'tanggal_selesai' => '2023-12-31', 'masih_bekerja' => false]);

        $hasil = app(RekomendasiService::class)->skor($job->load('kompetensi'), $talent->load(['kompetensi', 'pengalamanKerjaTalent']));

        expect($hasil)
            ->lama_pengalaman->toBe(1.0)
            ->skor_kompetensi->toBe(75.0)
            ->skor_kecocokan->toBe(70.0);
    });
});

describe('GET /job-requirements/{id}/rekomendasi', function () {
    it('mengembalikan kandidat terurut skor beserta kompetensi cocok/kurang', function () {
        loginSebagai(Role::TalentSupport);
        [$laravel, $mysql, $docker, $react] = Kompetensi::factory()->count(4)->sequence(
            ['nama_kompetensi' => 'Laravel'], ['nama_kompetensi' => 'MySQL'], ['nama_kompetensi' => 'Docker'], ['nama_kompetensi' => 'React'],
        )->create();
        $job = jobDengan([$laravel, $mysql, $docker], 2);

        kandidat('Sebagian', [$laravel], 3);
        $terbaik = kandidat('Terbaik', [$laravel, $mysql, $docker], 2);
        kandidat('Hanya Pengalaman', [$react], 10);

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/rekomendasi")
            ->assertOk()
            ->assertJsonPath('message', '2 kandidat direkomendasikan')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id_talent', $terbaik->id_talent)
            ->assertJsonPath('data.0.id_talent_pool', $terbaik->talentPool->id_talent_pool)
            ->assertJsonPath('data.0.skor_kecocokan', 100)
            ->assertJsonPath('data.0.kompetensi_kurang', [])
            ->assertJsonPath('data.1.nama_lengkap', 'Sebagian')
            ->assertJsonPath('data.1.kompetensi_cocok', ['Laravel'])
            ->assertJsonPath('data.1.kompetensi_kurang', ['MySQL', 'Docker'])
            ->assertJsonPath('data.1.skor_kompetensi', 33.33)
            ->assertJsonPath('data.1.status_ketersediaan', 'tersedia')
            ->assertJsonStructure(['data' => [[
                'id_talent', 'id_talent_pool', 'nama_lengkap', 'pendidikan', 'skor_kecocokan', 'skor_kompetensi',
                'skor_pengalaman', 'lama_pengalaman', 'kompetensi_cocok', 'kompetensi_kurang', 'status_ketersediaan', 'tag_keahlian',
            ]]]);
    });

    it('tidak menampilkan talent yang tidak tersedia, sedang diproses, di luar pool, atau sudah di-screening', function () {
        loginSebagai(Role::TalentSupport);
        $k = Kompetensi::factory()->create();
        $job = jobDengan([$k]);

        $muncul = kandidat('Muncul', [$k]);
        kandidat('Pool Tidak Tersedia', [$k], pool: ['status_ketersediaan' => StatusKetersediaan::TidakTersedia]);
        kandidat('Dalam Proses', [$k], talent: ['status_kandidat' => StatusKandidat::DalamProses]);
        $sudah = kandidat('Sudah Screening', [$k]);
        ScreeningTalent::factory()->for($job)->for($sudah)->create(['hasil_screening' => 'tidak_lolos']);
        Talent::factory()->lengkap()->create()->kompetensi()->attach($k);

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/rekomendasi")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id_talent', $muncul->id_talent);
    });

    it('menolak job yang belum diajukan atau sudah selesai (422)', function (StatusReq $status) {
        loginSebagai(Role::TalentSupport);
        $job = jobDengan([Kompetensi::factory()->create()], status: $status);

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/rekomendasi")
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_job_req.0', 'Kandidat hanya dapat dicari untuk job requirement berstatus diajukan atau diproses.');
    })->with([StatusReq::Draft, StatusReq::Terpenuhi, StatusReq::Dibatalkan]);

    it('menolak selain Talent Support (403)', function (Role $role) {
        loginSebagai($role);
        $job = jobDengan([Kompetensi::factory()->create()]);

        $this->getJson("/api/v1/job-requirements/{$job->id_job_req}/rekomendasi")
            ->assertForbidden()
            ->assertExactJson(['message' => 'Anda tidak memiliki akses ke fitur ini.']);
    })->with(roleSelain(Role::TalentSupport));
});
