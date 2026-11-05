<?php

namespace Database\Seeders;

use App\Enums\HasilScreening;
use App\Enums\StatusInterview;
use App\Enums\StatusPengajuan;
use App\Enums\StatusPlacement;
use App\Enums\StatusReq;
use App\Models\EvaluasiKandidat;
use App\Models\JadwalInterview;
use App\Models\JobRequirement;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\PengajuanKandidat;
use App\Models\Placement;
use App\Models\ScreeningTalent;
use App\Models\Talent;
use App\Models\User;
use App\Services\EvaluasiService;
use App\Services\InterviewService;
use App\Services\JobRequirementService;
use App\Services\PengajuanService;
use App\Services\PlacementService;
use App\Services\ScreeningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 8 permintaan kebutuhan dengan semua status & indikator SLA, kandidat di setiap tahap, dan penempatan yang
 * tersebar 11 bulan. Semua lewat Service asli sehingga aturan bisnis (R7–R20), Observer (log progres), dan
 * notifikasi (R30) berjalan seperti saat aplikasi dipakai.
 */
class RekrutmenSeeder extends Seeder
{
    private const LINK_MEET = 'https://meet.google.com/abc-defg-hij';

    public function __construct(
        private readonly JobRequirementService $job,
        private readonly ScreeningService $screening,
        private readonly PengajuanService $pengajuan,
        private readonly InterviewService $interview,
        private readonly EvaluasiService $evaluasi,
        private readonly PlacementService $placement,
    ) {}

    public function run(): void
    {
        $this->backendDeveloper();
        $this->qaEngineer();
        $this->frontendDeveloper();
        $this->devopsEngineer();
        $this->dataAnalyst();
        $this->fullstackDeveloper();
        $this->uiUxDesigner();
        $this->networkEngineer();
    }

    /** Terpenuhi 2/2: Budi & Dimas ditempatkan; satu tidak lolos screening, satu ditolak di evaluasi. */
    private function backendDeveloper(): void
    {
        $t = Linimasa::bulan(10, 4);
        $job = $this->buatJob('am', 'PT Maju Jaya', $t, [
            'posisi' => 'Backend Developer',
            'gaji_min' => 10_000_000,
            'gaji_maks' => 15_000_000,
            'jumlah_kebutuhan' => 2,
            'pengalaman_min' => 2,
            'sla_deadline' => Linimasa::bulan(8, 28, 17),
            'kualifikasi' => 'Membangun REST API dengan Laravel dan MySQL. Terbiasa bekerja dengan Git dan code review.',
        ], ['Laravel', 'MySQL', 'REST API', 'Git']);

        $budi = $this->screen($job, 'Budi Santoso', $this->tambah($t, 3), HasilScreening::Lolos, 'Pengalaman backend sesuai; lolos tes teknis.');
        $dimas = $this->screen($job, 'Dimas Prasetyo', $this->tambah($t, 3, 11), HasilScreening::Lolos, 'Skor tinggi; berpengalaman di layanan pembayaran.');
        $rizky = $this->screen($job, 'Rizky Firmansyah', $this->tambah($t, 4), HasilScreening::Lolos, 'Kompetensi lengkap; lanjutkan ke klien.');
        $this->screen($job, 'Fajar Ramadhan', $this->tambah($t, 4, 11), HasilScreening::TidakLolos, 'Belum memenuhi minimal pengalaman 2 tahun saat itu.');

        $pBudi = $this->ajukan($budi, $this->tambah($t, 6));
        $pDimas = $this->ajukan($dimas, $this->tambah($t, 6, 10));
        $pRizky = $this->ajukan($rizky, $this->tambah($t, 7));
        foreach ([$pBudi, $pDimas, $pRizky] as $p) {
            $this->tinjau($p, StatusPengajuan::LanjutInterview, $this->tambah($t, 8));
        }

        $this->interviewSelesai($pBudi, $this->tambah($t, 12), 'Interview teknis Laravel bersama tim engineering.');
        $this->interviewSelesai($pDimas, $this->tambah($t, 13), 'Interview teknis dan live coding.');
        $this->interviewSelesai($pRizky, $this->tambah($t, 14), 'Interview teknis bersama tim engineering.');

        $evBudi = $this->nilai('hr', $pBudi, 88, 'diterima', 'Kemampuan teknis kuat, komunikasi baik.', $this->tambah($t, 16));
        $evDimas = $this->nilai('hr', $pDimas, 91, 'diterima', 'Pengalaman sangat relevan dengan kebutuhan tim.', $this->tambah($t, 17));
        $this->nilai('hr', $pRizky, 68, 'ditolak', 'Teknis cukup, tetapi kurang berpengalaman pada skala sistem kami.', $this->tambah($t, 17, 14));

        $this->tempatkan($evBudi, Linimasa::bulan(9, 6), Linimasa::bulan(-5, 6));
        $this->tempatkan($evDimas, Linimasa::bulan(8, 12), Linimasa::bulan(-6, 12));
    }

    /**
     * Diproses 1/2, SLA terlambat: Putri kontraknya sudah selesai (kembali ke pool), Yoga aktif,
     * Nadia diterima menunggu penempatan, Hafiz ditolak klien.
     */
    private function qaEngineer(): void
    {
        $t = Linimasa::bulan(12, 15);
        $job = $this->buatJob('am', 'PT Sinar Digital Nusantara', $t, [
            'posisi' => 'QA Engineer',
            'gaji_min' => 8_000_000,
            'gaji_maks' => 12_000_000,
            'jumlah_kebutuhan' => 2,
            'pengalaman_min' => 1,
            'sla_deadline' => Linimasa::hari(-3, 17),
            'kualifikasi' => 'Menyusun skenario uji manual dan otomatis (Selenium) serta memeriksa data dengan SQL.',
        ], ['QA Manual', 'Selenium', 'SQL']);

        $putri = $this->screen($job, 'Putri Ayu Lestari', $this->tambah($t, 3), HasilScreening::Lolos, 'Sesuai kebutuhan; berpengalaman Selenium.');
        $pPutri = $this->ajukan($putri, $this->tambah($t, 5));
        $this->tinjau($pPutri, StatusPengajuan::LanjutInterview, $this->tambah($t, 6));
        $this->interviewSelesai($pPutri, $this->tambah($t, 9), 'Interview bersama QA Lead.');
        $evPutri = $this->nilai('hr03', $pPutri, 84, 'diterima', 'Teliti dan cepat memahami alur produk.', $this->tambah($t, 11));
        $plPutri = $this->tempatkan($evPutri, Linimasa::bulan(11, 8), Linimasa::bulan(7, 8));
        // Kontrak jangka pendek selesai; talent kembali tersedia di pool (R20).
        Linimasa::pada(Linimasa::bulan(7, 8, 17), fn () => $this->placement->ubahStatus($plPutri, StatusPlacement::Selesai, Linimasa::bulan(7, 8)->toDateString()));

        $s = Linimasa::bulan(7, 12);
        $yoga = $this->screen($job, 'Yoga Pratama', $s, HasilScreening::Lolos, 'Pengalaman Scrum dan Selenium sesuai.');
        $pYoga = $this->ajukan($yoga, $this->tambah($s, 2));
        $this->tinjau($pYoga, StatusPengajuan::LanjutInterview, $this->tambah($s, 3));
        $this->interviewSelesai($pYoga, $this->tambah($s, 6), 'Interview bersama QA Lead.');
        $evYoga = $this->nilai('hr03', $pYoga, 86, 'diterima', 'Mampu menyusun rencana uji end-to-end.', $this->tambah($s, 8));
        $this->tempatkan($evYoga, Linimasa::bulan(6, 18), Linimasa::bulan(-6, 18));

        $hafiz = $this->screen($job, 'Hafiz Akbar', Linimasa::hari(-30), HasilScreening::Lolos, 'Kandidat alternatif dengan latar pengujian keamanan.');
        $pHafiz = $this->ajukan($hafiz, Linimasa::hari(-28));
        $this->tinjau($pHafiz, StatusPengajuan::Ditolak, Linimasa::hari(-26));

        $nadia = $this->screen($job, 'Nadia Rahmawati', Linimasa::hari(-22), HasilScreening::Lolos, 'Sesuai kebutuhan pengujian manual.');
        $pNadia = $this->ajukan($nadia, Linimasa::hari(-20));
        $this->tinjau($pNadia, StatusPengajuan::LanjutInterview, Linimasa::hari(-18));
        $this->interviewSelesai($pNadia, Linimasa::hari(-12), 'Interview bersama QA Lead.');
        $this->nilai('hr03', $pNadia, 82, 'diterima', 'Teliti; siap mulai bulan depan.', Linimasa::hari(-2));
    }

    /**
     * Diproses 1/2, SLA mendekati, klien hr@: kandidat di setiap tahap agar peninjauan, evaluasi, dan penjadwalan bisa didemokan.
     */
    private function frontendDeveloper(): void
    {
        $t = Linimasa::bulan(4, 6);
        $job = $this->buatJob('am', 'PT Maju Jaya', $t, [
            'posisi' => 'Frontend Developer',
            'gaji_min' => 9_000_000,
            'gaji_maks' => 13_000_000,
            'jumlah_kebutuhan' => 2,
            'pengalaman_min' => 1,
            'sla_deadline' => Linimasa::hari(2, 17),
            'kualifikasi' => 'Membangun antarmuka dengan React atau Vue, terbiasa dengan Git dan bekerja sama dengan desainer.',
        ], ['React', 'Vue', 'Git', 'UI/UX']);

        $salsa = $this->screen($job, 'Salsabila Putri', $this->tambah($t, 3), HasilScreening::Lolos, 'Portofolio antarmuka kuat.');
        $pSalsa = $this->ajukan($salsa, $this->tambah($t, 5));
        $this->tinjau($pSalsa, StatusPengajuan::LanjutInterview, $this->tambah($t, 7));
        $this->interviewSelesai($pSalsa, $this->tambah($t, 12), 'Interview teknis React.');
        $ev = $this->nilai('hr', $pSalsa, 90, 'diterima', 'Hasil tes sangat baik.', $this->tambah($t, 14));
        $this->tempatkan($ev, Linimasa::bulan(3, 8), Linimasa::bulan(-9, 8));

        // Interview selesai, menunggu evaluasi hr@ (ronde pertama sempat dibatalkan).
        $reza = $this->screen($job, 'Reza Mahendra', Linimasa::hari(-24), HasilScreening::Lolos, 'Pengalaman dashboard sesuai kebutuhan.');
        $pReza = $this->ajukan($reza, Linimasa::hari(-21));
        $this->tinjau($pReza, StatusPengajuan::LanjutInterview, Linimasa::hari(-19));
        $batal = $this->jadwalkan($pReza, Linimasa::hari(-15), '10:00', 'Interview teknis Vue.', Linimasa::hari(-18));
        Linimasa::pada(Linimasa::hari(-16), fn () => $this->interview->ubah($batal, ['status_interview' => StatusInterview::Dibatalkan->value]));
        $this->interviewSelesai($pReza, Linimasa::hari(-3), 'Interview teknis Vue (jadwal pengganti).');

        // Interview terjadwal minggu ini.
        $anisa = $this->screen($job, 'Anisa Fitriani', Linimasa::hari(-12), HasilScreening::Lolos, 'Perhatian pada aksesibilitas menjadi nilai tambah.');
        $pAnisa = $this->ajukan($anisa, Linimasa::hari(-10));
        $this->tinjau($pAnisa, StatusPengajuan::LanjutInterview, Linimasa::hari(-7));
        $this->jadwalkan($pAnisa, Linimasa::hari(3), '13:30', 'Interview teknis React bersama tim produk.', Linimasa::hari(-5));

        // Menunggu tinjauan hr@.
        $kevin = $this->screen($job, 'Kevin Wijaya', Linimasa::hari(-6), HasilScreening::Lolos, 'React dan integrasi API kuat.');
        $this->ajukan($kevin, Linimasa::hari(-1, 14));

        // Diterima hr@, menunggu dibuatkan penempatan oleh TS.
        $maya = $this->screen($job, 'Maya Sari', Linimasa::hari(-28), HasilScreening::Lolos, 'Latar desain antarmuka sesuai.');
        $pMaya = $this->ajukan($maya, Linimasa::hari(-26));
        $this->tinjau($pMaya, StatusPengajuan::LanjutInterview, Linimasa::hari(-24));
        $this->interviewSelesai($pMaya, Linimasa::hari(-14), 'Interview teknis React.');
        $this->nilai('hr', $pMaya, 84, 'diterima', 'Hasil tes baik; siap bergabung setelah penempatan dibuat.', Linimasa::hari(-2, 15));

        // Menunggu keputusan TS.
        $this->screen($job, 'Bagas Saputra', Linimasa::hari(-1), HasilScreening::Pending, 'Lulusan baru; perlu tes teknis.');
    }

    /** Terpenuhi 1/1: Wahyu ditempatkan; Galih ditolak di evaluasi. */
    private function devopsEngineer(): void
    {
        $t = Linimasa::bulan(3, 2);
        $job = $this->buatJob('am02', 'PT Nusantara Retail Indonesia', $t, [
            'posisi' => 'DevOps Engineer',
            'gaji_min' => 14_000_000,
            'gaji_maks' => 20_000_000,
            'jumlah_kebutuhan' => 1,
            'pengalaman_min' => 3,
            'sla_deadline' => Linimasa::bulan(1, 25, 17),
            'kualifikasi' => 'Mengelola Docker dan Kubernetes di AWS serta pipeline CI/CD.',
        ], ['Docker', 'Kubernetes', 'AWS', 'Linux']);

        $wahyu = $this->screen($job, 'Wahyu Nugroho', $this->tambah($t, 4), HasilScreening::Lolos, 'Sertifikasi Kubernetes dan pengalaman panjang.', 'ts02');
        $galih = $this->screen($job, 'Galih Wicaksono', $this->tambah($t, 4, 13), HasilScreening::Lolos, 'Pengalaman Docker dan AWS memadai.', 'ts02');
        $pWahyu = $this->ajukan($wahyu, $this->tambah($t, 7), 'am02');
        $pGalih = $this->ajukan($galih, $this->tambah($t, 7, 11), 'am02');
        $this->tinjau($pWahyu, StatusPengajuan::LanjutInterview, $this->tambah($t, 9));
        $this->tinjau($pGalih, StatusPengajuan::LanjutInterview, $this->tambah($t, 9));
        $this->interviewSelesai($pWahyu, $this->tambah($t, 14), 'Interview teknis bersama tim infrastruktur.');
        $this->interviewSelesai($pGalih, $this->tambah($t, 15), 'Interview teknis bersama tim infrastruktur.');
        $this->nilai('hr06', $pGalih, 70, 'ditolak', 'Belum berpengalaman mengelola Kubernetes di produksi.', $this->tambah($t, 18));
        $ev = $this->nilai('hr06', $pWahyu, 92, 'diterima', 'Penguasaan infrastruktur sangat baik.', $this->tambah($t, 19));
        $this->tempatkan($ev, Linimasa::bulan(1, 5), Linimasa::bulan(-11, 5));
    }

    /** Diproses 0/1, SLA aman: lolos, menunggu, tidak lolos, dan interview yang dijadwal ulang. */
    private function dataAnalyst(): void
    {
        $t = Linimasa::hari(-16);
        $job = $this->buatJob('am', 'PT Sinar Digital Nusantara', $t, [
            'posisi' => 'Data Analyst',
            'gaji_min' => 8_500_000,
            'gaji_maks' => 12_500_000,
            'jumlah_kebutuhan' => 1,
            'pengalaman_min' => 2,
            'sla_deadline' => Linimasa::hari(25, 17),
            'kualifikasi' => 'Mengolah data dengan SQL dan Python serta menyusun dashboard Power BI untuk tim manajemen.',
        ], ['SQL', 'Python', 'Power BI', 'Excel', 'Data Analysis']);

        $arif = $this->screen($job, 'Arif Hidayat', Linimasa::hari(-13), HasilScreening::Lolos, 'Pengalaman BI sesuai kebutuhan.');
        $pArif = $this->ajukan($arif, Linimasa::hari(-11));
        $this->tinjau($pArif, StatusPengajuan::LanjutInterview, Linimasa::hari(-9));
        $iv = $this->jadwalkan($pArif, Linimasa::hari(1), '10:00', 'Studi kasus analisis data.', Linimasa::hari(-8));
        Linimasa::pada(Linimasa::hari(-4), fn () => $this->interview->ubah($iv, ['tanggal_interview' => Linimasa::hari(5)->toDateString(), 'waktu_interview' => '14:00']));

        $this->screen($job, 'Intan Permata', Linimasa::hari(-5), HasilScreening::Lolos, 'Sertifikasi Power BI; siap diajukan.');
        $this->screen($job, 'Citra Dewi', Linimasa::hari(-5, 11), HasilScreening::TidakLolos, 'Pengalaman belum mencapai 2 tahun.');
        $this->screen($job, 'Farhan Maulana', Linimasa::hari(-2), HasilScreening::Pending, 'Menunggu hasil studi kasus.');
    }

    /** Diajukan oleh HR Client, belum dicarikan kandidat: dipakai demo Rekomendasi Kandidat. */
    private function fullstackDeveloper(): void
    {
        $this->buatJob('hr06', 'PT Nusantara Retail Indonesia', Linimasa::hari(-4), [
            'posisi' => 'Fullstack Developer',
            'gaji_min' => 12_000_000,
            'gaji_maks' => 18_000_000,
            'jumlah_kebutuhan' => 1,
            'pengalaman_min' => 3,
            'sla_deadline' => Linimasa::hari(30, 17),
            'kualifikasi' => 'Mengembangkan aplikasi internal dengan Laravel dan React, termasuk perancangan basis data MySQL.',
        ], ['Laravel', 'React', 'MySQL', 'REST API', 'Git']);
    }

    private function uiUxDesigner(): void
    {
        $this->buatJob('hr05', 'CV Teknologi Andalan', Linimasa::hari(-1), [
            'posisi' => 'UI/UX Designer',
            'gaji_min' => 8_000_000,
            'gaji_maks' => 11_000_000,
            'jumlah_kebutuhan' => 1,
            'pengalaman_min' => 1,
            'sla_deadline' => Linimasa::hari(45, 17),
            'kualifikasi' => 'Merancang alur dan prototipe aplikasi internal menggunakan Figma.',
        ], ['UI/UX', 'Figma'], StatusReq::Draft);
    }

    /** Dibatalkan klien saat kandidat masih di-screening; talent dilepas kembali. */
    private function networkEngineer(): void
    {
        $t = Linimasa::hari(-60);
        $job = $this->buatJob('am02', 'CV Teknologi Andalan', $t, [
            'posisi' => 'Network Engineer',
            'jumlah_kebutuhan' => 1,
            'pengalaman_min' => 3,
            'sla_deadline' => Linimasa::hari(-20, 17),
            'kualifikasi' => 'Mengelola jaringan kantor dan keamanan server Linux.',
        ], ['Network', 'Linux', 'Cyber Security']);

        $this->screen($job, 'Hafiz Akbar', Linimasa::hari(-55), HasilScreening::Pending, 'Sertifikasi CCNA sesuai.', 'ts02');
        Linimasa::pada(Linimasa::hari(-45), fn () => $this->job->ubahStatus($job->refresh(), StatusReq::Dibatalkan));
    }

    /* ---------- Satu langkah alur = satu pemanggilan Service pada waktu kejadiannya ---------- */

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $kompetensi
     */
    private function buatJob(string $pembuat, string $klien, Carbon $waktu, array $data, array $kompetensi, StatusReq $status = StatusReq::Diajukan): JobRequirement
    {
        return Linimasa::pada($waktu, fn () => $this->job->buat($this->user($pembuat), [
            ...$data,
            'id_klien' => Klien::where('nama_perusahaan', $klien)->value('id_klien'),
            'status_req' => $status->value,
            'kompetensi' => Kompetensi::whereIn('nama_kompetensi', $kompetensi)->pluck('id_kompetensi')->all(),
        ]));
    }

    private function screen(JobRequirement $job, string $nama, Carbon $waktu, HasilScreening $hasil, string $catatan, string $ts = 'ts'): ScreeningTalent
    {
        $screening = Linimasa::pada($waktu, fn () => $this->screening->buat($this->user($ts), [
            'id_job_req' => $job->id_job_req,
            'id_talent' => Talent::where('nama_lengkap', $nama)->value('id_talent'),
            'catatan_screening' => $hasil === HasilScreening::Pending ? $catatan : null,
        ]));

        if ($hasil !== HasilScreening::Pending) {
            $screening = Linimasa::pada($this->tambah($waktu, 0, $waktu->hour + 3), fn () => $this->screening->ubah($screening, [
                'hasil_screening' => $hasil->value,
                'catatan_screening' => $catatan,
            ]));
        }

        return $screening;
    }

    private function ajukan(ScreeningTalent $screening, Carbon $waktu, string $am = 'am'): PengajuanKandidat
    {
        return Linimasa::pada($waktu, fn () => $this->pengajuan->buat($this->user($am), $screening->id_screening));
    }

    private function tinjau(PengajuanKandidat $pengajuan, StatusPengajuan $status, Carbon $waktu): void
    {
        Linimasa::pada($waktu, fn () => $this->pengajuan->ubahStatus($pengajuan, $status));
    }

    private function jadwalkan(PengajuanKandidat $pengajuan, Carbon $tanggal, string $jam, string $instruksi, Carbon $waktu): JadwalInterview
    {
        return Linimasa::pada($waktu, fn () => $this->interview->buat([
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'tanggal_interview' => $tanggal->toDateString(),
            'waktu_interview' => $jam,
            'link_lokasi_meeting' => self::LINK_MEET,
            'instruksi' => $instruksi,
        ]));
    }

    /** Dijadwalkan 3 hari sebelumnya, lalu ditandai selesai pada hari interview. */
    private function interviewSelesai(PengajuanKandidat $pengajuan, Carbon $tanggal, string $instruksi): void
    {
        $jadwal = $this->jadwalkan($pengajuan, $tanggal, '10:00', $instruksi, $tanggal->copy()->subDays(3));
        Linimasa::pada($tanggal->copy()->setTime(16, 0), fn () => $this->interview->ubah($jadwal, ['status_interview' => StatusInterview::Selesai->value]));
    }

    private function nilai(string $hr, PengajuanKandidat $pengajuan, int $skor, string $status, string $catatan, Carbon $waktu): EvaluasiKandidat
    {
        return Linimasa::pada($waktu, fn () => $this->evaluasi->buat($this->user($hr), [
            'id_pengajuan' => $pengajuan->id_pengajuan,
            'nilai_score' => $skor,
            'catatan_evaluasi' => $catatan,
            'status_evaluasi' => $status,
        ]));
    }

    private function tempatkan(EvaluasiKandidat $evaluasi, Carbon $mulai, Carbon $selesai): Placement
    {
        return Linimasa::pada($mulai->copy()->subDays(4), fn () => $this->placement->buat([
            'id_evaluasi' => $evaluasi->id_evaluasi,
            'tanggal_mulai' => $mulai->toDateString(),
            'tanggal_selesai' => $selesai->toDateString(),
        ]));
    }

    private function tambah(Carbon $waktu, int $hari, ?int $jam = null): Carbon
    {
        return $waktu->copy()->addDays($hari)->setTime($jam ?? $waktu->hour, 0);
    }

    private function user(string $nama): User
    {
        return User::where('email', "{$nama}@intellisys.test")->firstOrFail();
    }
}
