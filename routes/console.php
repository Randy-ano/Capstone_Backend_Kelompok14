<?php

use App\Services\PengingatService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
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

Schedule::command('notifikasi:pengingat')->dailyAt('07:00')->timezone('Asia/Jakarta');
