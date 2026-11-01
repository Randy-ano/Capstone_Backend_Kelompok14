<?php

namespace Database\Seeders;

use App\Enums\StatusApproval;
use App\Enums\StatusPlacement;
use App\Models\Placement;
use App\Models\User;
use App\Services\CutiService;
use App\Services\LaporanKeluhanService;
use App\Services\MonitoringService;
use App\Services\ShiftService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Cuti (semua status), shift 4 minggu, monitoring kinerja, dan tiket komplain untuk penempatan yang ada.
 * Memakai Service asli agar notifikasi & aturan R21–R24 berlaku.
 */
class OperasionalSeeder extends Seeder
{
    public function __construct(
        private readonly ShiftService $shift,
        private readonly CutiService $cuti,
        private readonly MonitoringService $monitoring,
        private readonly LaporanKeluhanService $laporan,
    ) {}

    public function run(): void
    {
        $this->shiftEmpatMinggu();
        $this->cuti();
        $this->monitoringKinerja();
        $this->tiketKomplain();
    }

    /** Minggu lalu sampai dua minggu ke depan. Senin–Jumat sesuai pola tiap talent, Sabtu–Minggu libur. */
    private function shiftEmpatMinggu(): void
    {
        $pola = [
            'Budi Santoso' => ['hr', ['pagi', 'pagi', 'pagi', 'pagi'], 'WFO 08.00–17.00'],
            'Dimas Prasetyo' => ['hr', ['pagi', 'siang', 'pagi', 'siang'], 'Hybrid, sesuai jadwal tim'],
            'Salsabila Putri' => ['hr02', ['pagi', 'pagi', 'pagi', 'pagi'], 'WFH Senin & Jumat'],
            'Yoga Pratama' => ['hr03', ['siang', 'siang', 'pagi', 'pagi'], 'Menyesuaikan jadwal rilis'],
            'Wahyu Nugroho' => ['manager', ['pagi', 'malam', 'pagi', 'malam'], 'Piket infrastruktur bergantian'],
        ];
        $seninLalu = Linimasa::hari(-7)->startOfWeek();

        foreach ($pola as $nama => [$pengelola, $mingguan, $keterangan]) {
            $placement = $this->placementAktif($nama);
            $user = $this->user($pengelola);

            Linimasa::pada(Linimasa::hari(-8), function () use ($placement, $user, $seninLalu, $mingguan, $keterangan) {
                foreach ($mingguan as $ke => $jenis) {
                    $senin = $seninLalu->copy()->addWeeks($ke);
                    $this->shift->simpanMassal($user, [
                        'id_placement' => $placement->id_placement,
                        'tanggal' => collect(range(0, 4))->map(fn (int $i) => $senin->copy()->addDays($i)->toDateString())->all(),
                        'jenis_shift' => $jenis,
                        'keterangan' => $keterangan,
                    ]);
                    $this->shift->simpanMassal($user, [
                        'id_placement' => $placement->id_placement,
                        'tanggal' => [$senin->copy()->addDays(5)->toDateString(), $senin->copy()->addDays(6)->toDateString()],
                        'jenis_shift' => 'libur',
                    ]);
                }
            });
        }
    }

    private function cuti(): void
    {
        // Disetujui kedua pihak (sudah lewat).
        $selesai = $this->ajukanCuti('Budi Santoso', -45, -44, 'Mengurus keperluan keluarga di luar kota.', -50);
        $this->putuskan('hr', $selesai, StatusApproval::Disetujui, -49);
        $this->putuskan('manager', $selesai, StatusApproval::Disetujui, -48);

        // Menunggu HR Client & Manager.
        $this->ajukanCuti('Budi Santoso', 10, 11, 'Menghadiri pernikahan saudara di luar kota.', -2);

        // Disetujui sebagian: klien setuju, menunggu Manager.
        $sebagian = $this->ajukanCuti('Dimas Prasetyo', 14, 16, 'Liburan keluarga yang sudah direncanakan.', -6);
        $this->putuskan('hr', $sebagian, StatusApproval::Disetujui, -4);

        // Ditolak Manager karena bertepatan dengan rilis.
        $ditolak = $this->ajukanCuti('Salsabila Putri', 6, 8, 'Keperluan pribadi.', -9);
        $this->putuskan('hr02', $ditolak, StatusApproval::Disetujui, -8);
        $this->putuskan('manager', $ditolak, StatusApproval::Ditolak, -7);

        // Menunggu, klien lain.
        $this->ajukanCuti('Yoga Pratama', 20, 20, 'Mengurus perpanjangan dokumen kependudukan.', -1);

        // Dibatalkan talent.
        $batal = $this->ajukanCuti('Wahyu Nugroho', 12, 13, 'Acara keluarga.', -5);
        Linimasa::pada(Linimasa::hari(-3), fn () => $this->cuti->batal($batal));
    }

    private function monitoringKinerja(): void
    {
        $catatan = [
            ['Budi Santoso', Linimasa::bulan(8, 8), 'Adaptasi baik; tugas sprint pertama selesai tepat waktu.', 'Pantau kembali setelah 3 bulan.'],
            ['Budi Santoso', Linimasa::bulan(5, 10), 'Mulai memimpin pengembangan modul pembayaran. Umpan balik klien positif.', null],
            ['Budi Santoso', Linimasa::hari(-12), 'Kinerja stabil; dokumentasi kode masih perlu ditingkatkan.', 'Diskusikan standar dokumentasi API bersama klien.'],
            ['Dimas Prasetyo', Linimasa::bulan(6, 12), 'Kontribusi teknis tinggi dan aktif membantu anggota tim baru.', null],
            ['Putri Ayu Lestari', Linimasa::bulan(9, 20), 'Kinerja baik selama kontrak; klien puas dengan cakupan pengujian.', 'Masuk daftar prioritas untuk permintaan QA berikutnya.'],
            ['Yoga Pratama', Linimasa::bulan(4, 15), 'Pengujian rilis berjalan lancar, tidak ada bug kritis yang lolos.', null],
            ['Yoga Pratama', Linimasa::hari(-20), 'Beberapa kali terlambat hadir pada rapat harian.', 'Bicarakan dengan talent dan HR klien.'],
            ['Salsabila Putri', Linimasa::bulan(2, 8), 'Cepat beradaptasi dengan sistem desain klien.', null],
            ['Wahyu Nugroho', Linimasa::hari(-10), 'Migrasi pipeline CI/CD selesai lebih cepat dari rencana.', 'Evaluasi beban piket setelah 3 bulan.'],
        ];

        $manager = $this->user('manager');
        foreach ($catatan as [$nama, $waktu, $hasil, $tindakLanjut]) {
            Linimasa::pada($waktu, fn () => $this->monitoring->catat($manager, [
                'id_placement' => Placement::whereHas('talent', fn ($q) => $q->where('nama_lengkap', $nama))->latest('id_placement')->value('id_placement'),
                'hasil_monitoring' => $hasil,
                'catatan_tindak_lanjut' => $tindakLanjut,
            ]));
        }
    }

    private function tiketKomplain(): void
    {
        // Baru.
        $this->lapor('hr', 'Budi Santoso', 'Dokumentasi kode kurang lengkap', 'Beberapa endpoint baru belum memiliki dokumentasi API sehingga menyulitkan tim frontend.', Linimasa::hari(-3));
        $this->lapor('hr03', 'Yoga Pratama', 'Sering terlambat rapat harian', 'Dalam dua minggu terakhir beberapa kali terlambat hadir pada rapat harian tim.', Linimasa::hari(-5));

        // Diproses.
        $diproses = $this->lapor('hr', 'Salsabila Putri', 'Perubahan desain belum diterapkan', 'Revisi desain halaman checkout belum diterapkan sesuai jadwal sprint.', Linimasa::hari(-10));
        Linimasa::pada(Linimasa::hari(-8), fn () => $this->laporan->tindakLanjut($this->user('am'), $diproses, [
            'status_laporan' => 'diproses',
            'hasil_tindak_lanjut' => 'Sudah dibicarakan dengan talent; menunggu penjadwalan ulang bersama desainer klien.',
        ]));

        // Selesai.
        $selesai = $this->lapor('hr', 'Dimas Prasetyo', 'Akses server produksi disalahgunakan', 'Ditemukan perubahan konfigurasi di server produksi tanpa pemberitahuan ke tim.', Linimasa::bulan(3, 14));
        Linimasa::pada(Linimasa::bulan(3, 16), fn () => $this->laporan->tindakLanjut($this->user('manager'), $selesai, [
            'status_laporan' => 'selesai',
            'hasil_tindak_lanjut' => 'Terjadi salah prosedur, bukan kesengajaan. Prosedur perubahan konfigurasi sudah disepakati ulang.',
        ]));
    }

    private function lapor(string $hr, string $talent, string $judul, string $deskripsi, Carbon $waktu)
    {
        return Linimasa::pada($waktu, fn () => $this->laporan->buat($this->user($hr), [
            'id_placement' => $this->placementAktif($talent)->id_placement,
            'judul_laporan' => $judul,
            'deskripsi_keluhan' => $deskripsi,
        ]));
    }

    private function ajukanCuti(string $nama, int $mulai, int $selesai, string $alasan, int $diajukan)
    {
        $placement = $this->placementAktif($nama);

        return Linimasa::pada(Linimasa::hari($diajukan), fn () => $this->cuti->ajukan($placement->talent->user, [
            'id_placement' => $placement->id_placement,
            'tanggal_mulai' => Linimasa::hari($mulai)->toDateString(),
            'tanggal_selesai' => Linimasa::hari($selesai)->toDateString(),
            'alasan_cuti' => $alasan,
        ]));
    }

    private function putuskan(string $pemutus, $cuti, StatusApproval $keputusan, int $hari): void
    {
        Linimasa::pada(Linimasa::hari($hari), fn () => $this->cuti->putuskan($this->user($pemutus), $cuti, $keputusan));
    }

    private function placementAktif(string $nama): Placement
    {
        return Placement::with('talent.user')
            ->where('status_placement', StatusPlacement::Aktif)
            ->whereHas('talent', fn ($q) => $q->where('nama_lengkap', $nama))
            ->firstOrFail();
    }

    private function user(string $nama): User
    {
        return User::where('email', "{$nama}@intellisys.test")->firstOrFail();
    }
}
