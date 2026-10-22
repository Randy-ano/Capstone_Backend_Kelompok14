<?php

namespace App\Jobs;

use App\Enums\StatusPengiriman;
use App\Mail\NotifikasiMail;
use App\Models\Notifikasi;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * R29: kirim notifikasi kanal email lewat queue, lalu perbarui status_pengiriman.
 */
class KirimEmailNotifikasi implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Notifikasi $notifikasi) {}

    public function handle(): void
    {
        $notifikasi = $this->notifikasi->loadMissing('penerima');
        Mail::to($notifikasi->penerima->email)->send(new NotifikasiMail($notifikasi));
        $notifikasi->update(['status_pengiriman' => StatusPengiriman::Terkirim]);
    }

    /** Dipanggil setelah semua percobaan gagal. */
    public function failed(?Throwable $exception): void
    {
        $this->notifikasi->update(['status_pengiriman' => StatusPengiriman::Gagal]);
    }
}
