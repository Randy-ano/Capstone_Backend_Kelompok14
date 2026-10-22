<?php

namespace Database\Seeders;

use App\Enums\StatusTahapan;
use App\Models\EvaluasiKandidat;
use App\Models\User;
use App\Services\CutiService;
use App\Services\LaporanKeluhanService;
use App\Services\MonitoringService;
use App\Services\PlacementService;
use App\Services\ProgresService;
use App\Services\ShiftService;
use Illuminate\Database\Seeder;

/**
 * Demo Fase 6: Budi (akun talent@) ditempatkan dari evaluasi diterima (RekrutmenSeeder), lalu punya
 * shift 2 minggu, 1 cuti menunggu, 1 monitoring, dan 1 laporan keluhan baru. Memakai service asli.
 */
class OperasionalSeeder extends Seeder
{
    public function run(
        PlacementService $placement,
        ShiftService $shift,
        CutiService $cuti,
        MonitoringService $monitoring,
        LaporanKeluhanService $laporan,
        ProgresService $progres,
    ): void {
        $hr = User::where('email', 'hr@intellisys.test')->firstOrFail();
        $manager = User::where('email', 'manager@intellisys.test')->firstOrFail();
        $talent = User::where('email', 'talent@intellisys.test')->firstOrFail();
        $evaluasi = EvaluasiKandidat::whereHas('pengajuan', fn ($q) => $q->where('id_talent', $talent->talent->id_talent))->firstOrFail();

        $budi = $placement->buat([
            'id_evaluasi' => $evaluasi->id_evaluasi,
            'tanggal_mulai' => now()->subWeeks(2)->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
        ]);
        // R12 dicatat observer; model event dimatikan saat seeding, jadi dicatat langsung.
        $progres->catat($budi->jobRequirement, StatusTahapan::Placement, $budi->id_talent);

        // Shift minggu ini dan minggu depan: Senin–Jumat pagi, Sabtu–Minggu libur.
        $senin = now()->startOfWeek();
        $hari = collect(range(0, 13))->map(fn (int $i) => $senin->copy()->addDays($i));
        foreach (['pagi' => fn ($t) => ! $t->isWeekend(), 'libur' => fn ($t) => $t->isWeekend()] as $jenis => $cocok) {
            $shift->simpanMassal($hr, [
                'id_placement' => $budi->id_placement,
                'tanggal' => $hari->filter($cocok)->map->toDateString()->values()->all(),
                'jenis_shift' => $jenis,
                'keterangan' => $jenis === 'pagi' ? 'WFO 08.00–17.00' : null,
            ]);
        }

        $cuti->ajukan($talent, [
            'id_placement' => $budi->id_placement,
            'tanggal_mulai' => now()->addDays(10)->toDateString(),
            'tanggal_selesai' => now()->addDays(11)->toDateString(),
            'alasan_cuti' => 'Menghadiri pernikahan saudara di luar kota.',
        ]);

        $monitoring->catat($manager, [
            'id_placement' => $budi->id_placement,
            'tanggal_monitoring' => now()->subDays(3)->setTime(10, 0),
            'hasil_monitoring' => 'Adaptasi baik; tugas sprint pertama selesai tepat waktu.',
            'catatan_tindak_lanjut' => 'Pantau kembali setelah 1 bulan.',
        ]);

        $laporan->buat($hr, [
            'id_placement' => $budi->id_placement,
            'judul_laporan' => 'Dokumentasi kode kurang lengkap',
            'deskripsi_keluhan' => 'Beberapa endpoint baru belum memiliki dokumentasi API sehingga menyulitkan tim frontend.',
        ]);
    }
}
