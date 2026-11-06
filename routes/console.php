<?php

use App\Services\PengingatService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-16, R31: pengingat harian (SLA, interview besok, cuti & tanda tangan tertunda, notifikasi terjadwal).
Artisan::command('notifikasi:pengingat', function (PengingatService $pengingat) {
    $hasil = $pengingat->jalankan();
    $this->info(sprintf(
        'Pengingat terkirim — SLA: %d, interview: %d, cuti: %d, tanda tangan: %d; notifikasi terjadwal: %d.',
        $hasil['sla'], $hasil['interview'], $hasil['cuti'], $hasil['tanda_tangan'], $hasil['terjadwal'],
    ));
})->purpose('Kirim pengingat harian (R31)');

// Jatuh tempo kapan saja pada jam 07.xx WIB, tetapi hanya dijalankan sekali per hari. Dengan begitu pengingat tetap
// terkirim walaupun `schedule:run` hanya dipanggil tiap jam oleh cron Railway (UTC, bisa terlambat beberapa menit),
// dan tidak berulang tiap menit saat `schedule:work` dipakai di lokal. PengingatService sendiri juga idempoten per hari.
Schedule::command('notifikasi:pengingat')
    ->cron('* 7 * * *')
    ->timezone('Asia/Jakarta')
    ->when(fn () => Cache::add('pengingat-harian:'.now('Asia/Jakarta')->toDateString(), true, now()->addDays(2)));
