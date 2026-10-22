<?php

namespace Database\Seeders;

use App\Enums\HasilScreening;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\User;
use App\Services\ProgresService;
use App\Services\RekomendasiService;
use App\Services\ScreeningService;
use Illuminate\Database\Seeder;

/**
 * Screening demo untuk job "Backend Developer": 3 kandidat teratas hasil rekomendasi (R9),
 * masing-masing lolos, pending, dan tidak lolos. Memakai service asli agar R10/R11 berlaku.
 * Akun demo talent selalu yang lolos (dipakai RekrutmenSeeder & OperasionalSeeder), walau skornya seri dengan talent lain.
 */
class ScreeningSeeder extends Seeder
{
    public function run(RekomendasiService $rekomendasi, ScreeningService $screening, ProgresService $progres): void
    {
        $ts = User::where('email', 'ts@intellisys.test')->firstOrFail();
        $demo = User::where('email', 'talent@intellisys.test')->firstOrFail()->talent;
        $job = JobRequirement::where('posisi', 'Backend Developer')->firstOrFail();

        $hasil = [HasilScreening::Lolos, HasilScreening::Pending, HasilScreening::TidakLolos];
        $kandidat = $rekomendasi->daftar($job)
            ->sortBy(fn (array $item) => $item['talent']->id_talent === $demo->id_talent ? 0 : 1)
            ->take(count($hasil));

        foreach ($kandidat->values() as $i => $item) {
            $baru = $screening->buat($ts, [
                'id_job_req' => $job->id_job_req,
                'id_talent' => $item['talent']->id_talent,
                'catatan_screening' => 'Profil sesuai kebutuhan; lanjut tes teknis.',
            ]);
            // R12 dicatat observer; model event dimatikan saat seeding, jadi dicatat langsung.
            $progres->catat($job, StatusTahapan::Screening, $baru->id_talent, $baru->id_ts);

            if ($hasil[$i] !== HasilScreening::Pending) {
                $screening->ubah($baru, [
                    'hasil_screening' => $hasil[$i]->value,
                    'catatan_screening' => $hasil[$i] === HasilScreening::Lolos ? 'Lolos tes teknis.' : 'Belum memenuhi kebutuhan pengalaman.',
                ]);
                if ($hasil[$i] === HasilScreening::TidakLolos) {
                    $progres->catat($job, StatusTahapan::Ditolak, $baru->id_talent, $baru->id_ts);
                }
            }
        }
    }
}
