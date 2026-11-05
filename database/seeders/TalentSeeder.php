<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\StatusKelengkapan;
use App\Models\Bahasa;
use App\Models\Kompetensi;
use App\Models\Talent;
use App\Models\User;
use App\Services\TalentCvService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 22 talent: 20 profil lengkap (masuk talent pool lewat TalentObserver, R5) dan 2 belum lengkap (R4).
 * Waktu pengalaman kerja dalam bulan sebelum hari seed, agar lama pengalaman (R9) tetap sama kapan pun seed dijalankan.
 */
class TalentSeeder extends Seeder
{
    /** Talent yang CV-nya sudah dibuat. */
    private const PUNYA_CV = ['Budi Santoso', 'Dimas Prasetyo', 'Yoga Pratama', 'Intan Permata', 'Rizky Firmansyah'];

    public function run(TalentCvService $cv): void
    {
        foreach ($this->data() as $email => $t) {
            $talent = Linimasa::pada(Linimasa::hari(-$t['daftar_hari_lalu']), fn () => $this->buat($email, $t));

            if (in_array($t['nama'], self::PUNYA_CV, true)) {
                Linimasa::pada(Linimasa::hari(-$t['daftar_hari_lalu'] + 1), fn () => $cv->generate($talent));
            }
        }

        // Dua profil belum lengkap: masing-masing kurang satu syarat R4.
        Linimasa::pada(Linimasa::hari(-6), function () {
            $this->profil('talent20@intellisys.test', 'Lina Marlina', 'Semarang')->riwayatPendidikan()->create([
                'jenjang' => 'S1',
                'nama_institusi' => 'Universitas Negeri Semarang',
                'jurusan' => 'Teknik Informatika',
                'tahun_mulai' => now()->year - 5,
                'tahun_selesai' => now()->year - 1,
            ]);
        });
        Linimasa::pada(Linimasa::hari(-3), function () {
            $this->profil('talent21@intellisys.test', 'Tegar Aditya', 'Depok')->kompetensi()->attach(
                Kompetensi::whereIn('nama_kompetensi', ['Python', 'SQL'])->pluck('id_kompetensi'),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $t
     */
    private function buat(string $email, array $t): Talent
    {
        [$jenjang, $jurusan, $institusi, $lulusTahunLalu] = $t['pendidikan'];
        $tahunLulus = now()->year - $lulusTahunLalu;
        $slug = Str::slug($t['nama']);

        $talent = $this->profil($email, $t['nama'], $t['domisili']);
        $talent->update([
            'tanggal_lahir' => now()->subYears($t['umur'])->startOfYear()->addDays(strlen($slug) * 9)->toDateString(),
            'nomor_telepon' => '0813-2000-'.substr($email, 6, 2).'00',
            'alamat' => "{$t['domisili']}, Indonesia",
            'tentang_saya' => $t['tentang'],
            'pendidikan' => "{$jenjang} {$jurusan}",
            'nama_institusi' => $institusi,
            'jurusan' => $jurusan,
            'tahun_lulus' => $tahunLulus,
            'pengalaman_kerja' => collect($t['pengalaman'])->map(fn (array $p) => "{$p[0]} di {$p[1]}")->implode('; ').'.',
            'gaji_harapan' => $t['gaji'],
            'linkedin' => "https://www.linkedin.com/in/{$slug}",
            'github' => "https://github.com/{$slug}",
        ]);

        $talent->riwayatPendidikan()->create([
            'jenjang' => $jenjang,
            'nama_institusi' => $institusi,
            'jurusan' => $jurusan,
            'tahun_mulai' => $tahunLulus - ($jenjang === 'D3' ? 3 : 4),
            'tahun_selesai' => $tahunLulus,
        ]);

        foreach ($t['pengalaman'] as [$posisi, $perusahaan, $kota, $mulaiBulanLalu, $selesaiBulanLalu]) {
            $talent->pengalamanKerjaTalent()->create([
                'nama_perusahaan' => $perusahaan,
                'posisi' => $posisi,
                'jenis_pekerjaan' => 'Full-time',
                'lokasi' => $kota,
                'tanggal_mulai' => Linimasa::bulan($mulaiBulanLalu, 1)->toDateString(),
                'tanggal_selesai' => $selesaiBulanLalu === null ? null : Linimasa::bulan($selesaiBulanLalu, 1)->toDateString(),
                'masih_bekerja' => $selesaiBulanLalu === null,
                'deskripsi' => self::deskripsiPekerjaan($posisi),
            ]);
        }

        $talent->kompetensi()->attach(Kompetensi::whereIn('nama_kompetensi', $t['kompetensi'])->pluck('id_kompetensi'));

        foreach ($t['sertifikat'] ?? [] as [$nama, $penerbit, $terbitBulanLalu]) {
            $talent->sertifikat()->create([
                'nama_sertifikat' => $nama,
                'penerbit' => $penerbit,
                'tanggal_terbit' => Linimasa::bulan($terbitBulanLalu, 15)->toDateString(),
            ]);
        }

        foreach ($t['bahasa'] ?? [] as $nama => $tingkat) {
            $talent->bahasa()->attach(Bahasa::firstOrCreate(['nama_bahasa' => $nama])->id_bahasa, ['tingkat_kemampuan' => $tingkat]);
        }

        // Observer R5 membuat entri talent pool saat status berubah menjadi lengkap.
        $talent->update(['status_kelengkapan' => StatusKelengkapan::Lengkap]);
        // Tag keahlian = label bidang kerja untuk pencarian cepat, bukan nama kompetensi (agar tidak tampil dobel
        // dengan daftar kompetensi di talent pool & rekomendasi).
        $talent->talentPool->update(['tag_keahlian' => self::tagBidang(end($t['pengalaman'])[0])]);

        return $talent->refresh();
    }

    private function profil(string $email, string $nama, string $domisili): Talent
    {
        return User::create(['email' => $email, 'password' => 'password', 'role' => Role::Talent])
            ->talent()->create(['nama_lengkap' => $nama, 'domisili' => $domisili]);
    }

    /**
     * pengalaman: [posisi, perusahaan, kota, mulai (bulan lalu), selesai (bulan lalu, null = masih bekerja)].
     *
     * @return array<string, array<string, mixed>>
     */
    private function data(): array
    {
        return [
            'talent@intellisys.test' => [
                'nama' => 'Budi Santoso', 'domisili' => 'Jakarta Selatan', 'umur' => 28, 'gaji' => 13000000, 'daftar_hari_lalu' => 345,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Universitas Gunadarma', 5],
                'tentang' => 'Backend developer yang berfokus pada Laravel dan perancangan REST API untuk aplikasi e-commerce.',
                'pengalaman' => [['Junior Backend Developer', 'PT Karya Aplikasi Mandiri', 'Depok', 48, 30], ['Backend Developer', 'PT Niaga Digital', 'Jakarta', 30, 11]],
                'kompetensi' => ['Laravel', 'MySQL', 'REST API', 'Git', 'Docker'],
                'sertifikat' => [['Laravel Certified Developer', 'Laravel', 20]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent01@intellisys.test' => [
                'nama' => 'Dimas Prasetyo', 'domisili' => 'Bandung', 'umur' => 30, 'gaji' => 15000000, 'daftar_hari_lalu' => 340,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Institut Teknologi Bandung', 7],
                'tentang' => 'Backend engineer berpengalaman membangun layanan pembayaran dan integrasi API.',
                'pengalaman' => [['Software Engineer', 'PT Arta Teknologi Indonesia', 'Bandung', 70, 12]],
                'kompetensi' => ['Laravel', 'MySQL', 'REST API', 'Docker', 'Git'],
                'sertifikat' => [['AWS Certified Developer', 'Amazon Web Services', 24]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Fasih'],
            ],
            'talent02@intellisys.test' => [
                'nama' => 'Putri Ayu Lestari', 'domisili' => 'Depok', 'umur' => 27, 'gaji' => 10000000, 'daftar_hari_lalu' => 380,
                'pendidikan' => ['S1', 'Sistem Informasi', 'Universitas Indonesia', 4],
                'tentang' => 'QA engineer dengan pengalaman pengujian manual dan otomatis menggunakan Selenium.',
                'pengalaman' => [['QA Engineer', 'PT Inovasi Sistem Prima', 'Jakarta', 44, 12]],
                'kompetensi' => ['QA Manual', 'Selenium', 'SQL', 'Python', 'Git'],
                'sertifikat' => [['ISTQB Foundation Level', 'ISTQB', 30]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent03@intellisys.test' => [
                'nama' => 'Yoga Pratama', 'domisili' => 'Yogyakarta', 'umur' => 29, 'gaji' => 11000000, 'daftar_hari_lalu' => 300,
                'pendidikan' => ['S1', 'Ilmu Komputer', 'Universitas Gadjah Mada', 6],
                'tentang' => 'QA engineer yang terbiasa bekerja dalam tim Scrum dan menyusun skenario uji end-to-end.',
                'pengalaman' => [['QA Engineer', 'PT Bumi Data Nusantara', 'Yogyakarta', 60, 8]],
                'kompetensi' => ['QA Manual', 'Selenium', 'SQL', 'Git', 'Scrum'],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent04@intellisys.test' => [
                'nama' => 'Salsabila Putri', 'domisili' => 'Bandung', 'umur' => 26, 'gaji' => 11000000, 'daftar_hari_lalu' => 200,
                'pendidikan' => ['S1', 'Informatika', 'Telkom University', 3],
                'tentang' => 'Frontend developer yang menyukai antarmuka rapi dan komponen React yang mudah dipakai ulang.',
                'pengalaman' => [['Frontend Developer', 'CV Kode Kreatif', 'Bandung', 34, 5]],
                'kompetensi' => ['React', 'Vue', 'Figma', 'UI/UX', 'Git'],
                'sertifikat' => [['Meta Front-End Developer', 'Meta', 16]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Fasih'],
            ],
            'talent05@intellisys.test' => [
                'nama' => 'Wahyu Nugroho', 'domisili' => 'Surabaya', 'umur' => 31, 'gaji' => 17000000, 'daftar_hari_lalu' => 120,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Institut Teknologi Sepuluh Nopember', 8],
                'tentang' => 'DevOps engineer dengan pengalaman mengelola klaster Kubernetes dan pipeline CI/CD.',
                'pengalaman' => [['System Administrator', 'PT Mitra Perangkat Lunak', 'Surabaya', 90, 50], ['DevOps Engineer', 'PT Garuda Cloud Indonesia', 'Surabaya', 50, 3]],
                'kompetensi' => ['Docker', 'Kubernetes', 'AWS', 'Linux', 'Git'],
                'sertifikat' => [['Certified Kubernetes Administrator', 'Cloud Native Computing Foundation', 14]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent06@intellisys.test' => [
                'nama' => 'Nadia Rahmawati', 'domisili' => 'Malang', 'umur' => 26, 'gaji' => 9500000, 'daftar_hari_lalu' => 60,
                'pendidikan' => ['S1', 'Sistem Informasi', 'Universitas Brawijaya', 3],
                'tentang' => 'QA engineer yang teliti dan terbiasa mendokumentasikan kasus uji serta hasil pengujian.',
                'pengalaman' => [['QA Tester', 'PT Inovasi Sistem Prima', 'Malang', 32, 2]],
                'kompetensi' => ['QA Manual', 'Selenium', 'Excel', 'SQL', 'Scrum'],
            ],
            'talent07@intellisys.test' => [
                'nama' => 'Kevin Wijaya', 'domisili' => 'Jakarta Barat', 'umur' => 27, 'gaji' => 12000000, 'daftar_hari_lalu' => 90,
                'pendidikan' => ['S1', 'Ilmu Komputer', 'Universitas Bina Nusantara', 4],
                'tentang' => 'Frontend developer dengan dasar Node.js, terbiasa bekerja dekat dengan tim backend.',
                'pengalaman' => [['Frontend Developer', 'PT Niaga Digital', 'Jakarta', 42, 1]],
                'kompetensi' => ['React', 'Node.js', 'Git', 'REST API', 'Figma'],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Fasih', 'Mandarin' => 'Dasar'],
            ],
            'talent08@intellisys.test' => [
                'nama' => 'Anisa Fitriani', 'domisili' => 'Semarang', 'umur' => 25, 'gaji' => 9000000, 'daftar_hari_lalu' => 85,
                'pendidikan' => ['S1', 'Teknik Komputer', 'Universitas Diponegoro', 2],
                'tentang' => 'Frontend developer yang fokus pada aksesibilitas dan pengalaman pengguna.',
                'pengalaman' => [['Frontend Developer', 'CV Kode Kreatif', 'Semarang', 22, 2]],
                'kompetensi' => ['React', 'Vue', 'Git', 'UI/UX'],
            ],
            'talent09@intellisys.test' => [
                'nama' => 'Reza Mahendra', 'domisili' => 'Sumedang', 'umur' => 28, 'gaji' => 12000000, 'daftar_hari_lalu' => 95,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Universitas Padjadjaran', 5],
                'tentang' => 'Frontend developer dengan pengalaman membangun dashboard data dan integrasi REST API.',
                'pengalaman' => [['Web Developer', 'PT Arta Teknologi Indonesia', 'Bandung', 52, 2]],
                'kompetensi' => ['Vue', 'React', 'Git', 'REST API'],
                'sertifikat' => [['Google UX Design Certificate', 'Google', 18]],
            ],
            'talent10@intellisys.test' => [
                'nama' => 'Maya Sari', 'domisili' => 'Jakarta Timur', 'umur' => 24, 'gaji' => 7500000, 'daftar_hari_lalu' => 45,
                'pendidikan' => ['D3', 'Manajemen Informatika', 'Politeknik Negeri Jakarta', 2],
                'tentang' => 'Frontend developer yang berlatar desain antarmuka dan prototipe Figma.',
                'pengalaman' => [['UI Developer', 'PT Karya Aplikasi Mandiri', 'Jakarta', 20, null]],
                'kompetensi' => ['React', 'Figma', 'UI/UX'],
            ],
            'talent11@intellisys.test' => [
                'nama' => 'Bagas Saputra', 'domisili' => 'Yogyakarta', 'umur' => 23, 'gaji' => 6500000, 'daftar_hari_lalu' => 30,
                'pendidikan' => ['S1', 'Informatika', 'Universitas Atma Jaya Yogyakarta', 1],
                'tentang' => 'Lulusan baru dengan pengalaman magang frontend dan proyek open source.',
                'pengalaman' => [['Frontend Developer Intern', 'PT Bumi Data Nusantara', 'Yogyakarta', 14, 8]],
                'kompetensi' => ['React', 'Git'],
            ],
            'talent12@intellisys.test' => [
                'nama' => 'Intan Permata', 'domisili' => 'Bogor', 'umur' => 27, 'gaji' => 11000000, 'daftar_hari_lalu' => 40,
                'pendidikan' => ['S1', 'Ilmu Komputer', 'Institut Pertanian Bogor', 4],
                'tentang' => 'Data analyst yang terbiasa mengolah data penjualan dan menyusun dashboard Power BI.',
                'pengalaman' => [['Data Analyst', 'PT Inovasi Sistem Prima', 'Jakarta', 40, null]],
                'kompetensi' => ['Python', 'SQL', 'Power BI', 'Excel', 'Data Analysis'],
                'sertifikat' => [['Microsoft Certified: Power BI Data Analyst', 'Microsoft', 10]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent13@intellisys.test' => [
                'nama' => 'Farhan Maulana', 'domisili' => 'Surabaya', 'umur' => 25, 'gaji' => 9000000, 'daftar_hari_lalu' => 35,
                'pendidikan' => ['S1', 'Sistem Informasi', 'Universitas Airlangga', 3],
                'tentang' => 'Analis data dengan minat pada pemodelan statistik dan otomatisasi laporan.',
                'pengalaman' => [['Junior Data Analyst', 'PT Bumi Data Nusantara', 'Surabaya', 26, null]],
                'kompetensi' => ['SQL', 'Python', 'Data Analysis', 'Excel'],
            ],
            'talent14@intellisys.test' => [
                'nama' => 'Arif Hidayat', 'domisili' => 'Surakarta', 'umur' => 29, 'gaji' => 12500000, 'daftar_hari_lalu' => 33,
                'pendidikan' => ['S1', 'Informatika', 'Universitas Sebelas Maret', 6],
                'tentang' => 'Business intelligence analyst berpengalaman menyusun laporan manajemen.',
                'pengalaman' => [['BI Analyst', 'PT Mitra Perangkat Lunak', 'Surakarta', 58, 1]],
                'kompetensi' => ['SQL', 'Power BI', 'Excel', 'Python', 'Data Analysis'],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent15@intellisys.test' => [
                'nama' => 'Citra Dewi', 'domisili' => 'Makassar', 'umur' => 24, 'gaji' => 7000000, 'daftar_hari_lalu' => 28,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Universitas Hasanuddin', 2],
                'tentang' => 'Staf data yang sedang mendalami analisis data dan visualisasi.',
                'pengalaman' => [['Staf Data', 'CV Kode Kreatif', 'Makassar', 12, null]],
                'kompetensi' => ['Excel', 'Power BI', 'SQL', 'Data Analysis'],
            ],
            'talent16@intellisys.test' => [
                'nama' => 'Fajar Ramadhan', 'domisili' => 'Malang', 'umur' => 26, 'gaji' => 9500000, 'daftar_hari_lalu' => 320,
                'pendidikan' => ['S1', 'Informatika', 'Universitas Muhammadiyah Malang', 3],
                'tentang' => 'Backend developer yang terbiasa mengembangkan aplikasi internal dengan Laravel.',
                'pengalaman' => [['Backend Developer', 'CV Kode Kreatif', 'Malang', 18, null]],
                'kompetensi' => ['Laravel', 'MySQL', 'SQL', 'Git', 'REST API', 'QA Manual'],
            ],
            'talent17@intellisys.test' => [
                'nama' => 'Rizky Firmansyah', 'domisili' => 'Bandung', 'umur' => 28, 'gaji' => 13000000, 'daftar_hari_lalu' => 315,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Universitas Komputer Indonesia', 5],
                'tentang' => 'Fullstack developer dengan pengalaman Laravel dan React untuk aplikasi perusahaan.',
                'pengalaman' => [['Fullstack Developer', 'PT Arta Teknologi Indonesia', 'Bandung', 54, null]],
                'kompetensi' => ['Laravel', 'React', 'SQL', 'REST API', 'Git', 'Selenium'],
                'sertifikat' => [['Oracle Certified Associate', 'Oracle', 26]],
                'bahasa' => ['Indonesia' => 'Penutur Asli', 'Inggris' => 'Menengah'],
            ],
            'talent18@intellisys.test' => [
                'nama' => 'Galih Wicaksono', 'domisili' => 'Semarang', 'umur' => 27, 'gaji' => 12000000, 'daftar_hari_lalu' => 110,
                'pendidikan' => ['S1', 'Teknik Informatika', 'Universitas Dian Nuswantoro', 4],
                'tentang' => 'Infrastructure engineer yang mengotomatisasi deployment dengan Docker dan skrip Python.',
                'pengalaman' => [['Infrastructure Engineer', 'PT Garuda Cloud Indonesia', 'Semarang', 30, null]],
                'kompetensi' => ['Docker', 'Linux', 'AWS', 'Python', 'Git'],
            ],
            'talent19@intellisys.test' => [
                'nama' => 'Hafiz Akbar', 'domisili' => 'Surabaya', 'umur' => 30, 'gaji' => 11000000, 'daftar_hari_lalu' => 100,
                'pendidikan' => ['D4', 'Teknik Komputer', 'Politeknik Elektronika Negeri Surabaya', 7],
                'tentang' => 'Network engineer dengan pengalaman keamanan jaringan dan administrasi server Linux.',
                'pengalaman' => [['Network Engineer', 'PT Mitra Perangkat Lunak', 'Surabaya', 76, null]],
                'kompetensi' => ['Network', 'Linux', 'Cyber Security', 'Excel', 'Git'],
                'sertifikat' => [['Cisco Certified Network Associate', 'Cisco', 40]],
            ],
        ];
    }

    /** Deskripsi pekerjaan contoh sesuai bidang posisi (tampil di detail talent dan CV). */
    private static function deskripsiPekerjaan(string $posisi): string
    {
        return match (true) {
            str_contains($posisi, 'Backend'), str_contains($posisi, 'Software') => 'Membangun dan memelihara REST API, merancang skema database, serta menulis pengujian otomatis untuk layanan utama.',
            str_contains($posisi, 'Frontend') => 'Mengembangkan antarmuka web yang responsif, mengintegrasikan REST API, dan menjaga konsistensi komponen desain.',
            str_contains($posisi, 'QA') => 'Menyusun skenario uji, menjalankan pengujian manual dan otomatis, serta melaporkan dan memverifikasi perbaikan bug.',
            str_contains($posisi, 'DevOps'), str_contains($posisi, 'System') => 'Mengelola server dan pipeline CI/CD, memantau layanan produksi, serta mengotomasi proses deployment.',
            str_contains($posisi, 'Data') => 'Mengolah dan menganalisis data operasional, membuat dashboard laporan, serta menyiapkan rekomendasi bisnis.',
            default => 'Mengerjakan tugas sesuai posisi bersama tim dan berkoordinasi dengan pemangku kepentingan.',
        };
    }

    /** Label bidang kerja dari posisi terakhir talent. */
    private static function tagBidang(string $posisi): string
    {
        return match (true) {
            str_contains($posisi, 'Backend'), str_contains($posisi, 'Software') => 'Pengembangan Backend, Integrasi API',
            str_contains($posisi, 'Fullstack') => 'Pengembangan Fullstack, Aplikasi Web',
            str_contains($posisi, 'Frontend'), str_contains($posisi, 'UI Developer') => 'Pengembangan Frontend, Antarmuka Web',
            str_contains($posisi, 'Web Developer') => 'Pengembangan Fullstack, Aplikasi Web',
            str_contains($posisi, 'Network') => 'Jaringan, Keamanan Sistem',
            str_contains($posisi, 'Infrastructure') => 'Infrastruktur, Otomasi Deployment',
            str_contains($posisi, 'UI/UX'), str_contains($posisi, 'Designer') => 'Desain Antarmuka, Riset Pengguna',
            str_contains($posisi, 'QA') => 'Pengujian Perangkat Lunak',
            str_contains($posisi, 'DevOps'), str_contains($posisi, 'System') => 'Infrastruktur, Otomasi Deployment',
            str_contains($posisi, 'Data'), str_contains($posisi, 'Analyst') => 'Analisis Data',
            default => 'Umum',
        };
    }
}
