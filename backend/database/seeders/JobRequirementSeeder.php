<?php

namespace Database\Seeders;

use App\Enums\IndikatorSla;
use App\Enums\StatusReq;
use App\Enums\StatusTahapan;
use App\Models\Klien;
use App\Models\Kompetensi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * 5 job requirement dengan variasi SLA (aman, mendekati, terlambat) untuk keperluan demo.
 */
class JobRequirementSeeder extends Seeder
{
    public function run(): void
    {
        $am = User::where('email', 'am@intellisys.test')->firstOrFail()->pegawai;

        $data = [
            ['PT Maju Jaya', 'Backend Developer', 2, 2, ['Laravel', 'MySQL', 'REST API', 'Docker'], StatusReq::Diajukan, 30],
            ['PT Maju Jaya', 'Frontend Developer', 1, 1, ['React', 'Figma', 'Git'], StatusReq::Diajukan, 2],
            ['PT Sinar Digital Nusantara', 'QA Engineer', 1, 1, ['QA Manual', 'Selenium', 'SQL'], StatusReq::Diajukan, -3],
            ['PT Sinar Digital Nusantara', 'Mobile Developer', 2, 2, ['Flutter', 'Kotlin', 'REST API', 'Git'], StatusReq::Diajukan, 45],
            ['CV Teknologi Andalan', 'Data Analyst', 1, 0, ['Data Analysis', 'Power BI', 'Excel', 'SQL'], StatusReq::Draft, 60],
        ];

        foreach ($data as [$klien, $posisi, $jumlah, $pengalamanMin, $kompetensi, $status, $hariSla]) {
            $job = Klien::where('nama_perusahaan', $klien)->firstOrFail()->jobRequirements()->create([
                'id_am' => $am->id_pegawai,
                'posisi' => $posisi,
                'jumlah_kebutuhan' => $jumlah,
                'kualifikasi' => "Dibutuhkan $posisi yang menguasai ".implode(', ', $kompetensi).'.',
                'pengalaman_min' => $pengalamanMin,
                'status_req' => $status,
                'sla_deadline' => now()->addDays($hariSla)->setTime(17, 0),
            ]);
            $job->kompetensi()->attach(Kompetensi::whereIn('nama_kompetensi', $kompetensi)->pluck('id_kompetensi'));

            // R8: job berstatus diajukan punya log progres "kebutuhan_dibuat".
            if ($status === StatusReq::Diajukan) {
                $job->progres()->create([
                    'id_penanggung_jawab' => $am->id_pegawai,
                    'status_tahapan' => StatusTahapan::KebutuhanDibuat,
                    'indikator_sla' => $this->indikatorSla($job->sla_deadline),
                    'log_waktu_pembaruan' => now(),
                ]);
            }
        }
    }

    /**
     * Versi sederhana R13 untuk data seed; logika resminya dibuat di Fase 4.
     */
    private function indikatorSla(Carbon $deadline): IndikatorSla
    {
        return match (true) {
            now()->greaterThan($deadline) => IndikatorSla::Terlambat,
            now()->diffInDays($deadline) <= 3 => IndikatorSla::Mendekati,
            default => IndikatorSla::Aman,
        };
    }
}
