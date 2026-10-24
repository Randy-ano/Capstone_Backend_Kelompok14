<?php

namespace Database\Seeders;

use App\Enums\HasilScreening;
use App\Enums\StatusPengajuan;
use App\Enums\StatusTahapan;
use App\Models\JobRequirement;
use App\Models\User;
use App\Services\EvaluasiService;
use App\Services\InterviewService;
use App\Services\PengajuanService;
use App\Services\ProgresService;
use Illuminate\Database\Seeder;

/**
 * Demo Fase 5 untuk job "Backend Developer": kandidat yang lolos screening diajukan AM,
 * dilanjutkan HR ke interview (kemarin, selesai), lalu dievaluasi diterima — siap dibuatkan placement oleh TS.
 * Memakai service asli; log progres dicatat langsung karena model event dimatikan saat seeding.
 */
class RekrutmenSeeder extends Seeder
{
    public function run(PengajuanService $pengajuan, InterviewService $interview, EvaluasiService $evaluasi, ProgresService $progres): void
    {
        $am = User::where('email', 'am@intellisys.test')->firstOrFail();
        $hr = User::where('email', 'hr@intellisys.test')->firstOrFail();
        $job = JobRequirement::where('posisi', 'Backend Developer')->firstOrFail();
        $screening = $job->screenings()->where('hasil_screening', HasilScreening::Lolos)->firstOrFail();

        $diajukan = $pengajuan->buat($am, $screening->id_screening);
        $progres->catat($job, StatusTahapan::Diajukan, $diajukan->id_talent, $diajukan->id_am);
        $pengajuan->ubahStatus($diajukan, StatusPengajuan::LanjutInterview);

        $jadwal = $interview->buat([
            'id_pengajuan' => $diajukan->id_pengajuan,
            'tanggal_interview' => now()->subDay()->toDateString(),
            'waktu_interview' => '10:00',
            'link_lokasi_meeting' => 'https://meet.google.com/abc-defg-hij',
            'instruksi' => 'Interview teknis Laravel bersama tim engineering klien.',
        ]);
        $progres->catat($job, StatusTahapan::Interview, $diajukan->id_talent, $diajukan->id_am);
        $interview->ubah($jadwal, ['status_interview' => 'selesai']);

        $evaluasi->buat($hr, [
            'id_pengajuan' => $diajukan->id_pengajuan,
            'nilai_score' => 88,
            'catatan_evaluasi' => 'Kemampuan teknis kuat, komunikasi baik.',
            'status_evaluasi' => 'diterima',
        ]);
        $progres->catat($job, StatusTahapan::Evaluasi, $diajukan->id_talent, $diajukan->id_am);
    }
}
