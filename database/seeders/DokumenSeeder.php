<?php

namespace Database\Seeders;

use App\Models\Klien;
use App\Models\User;
use App\Services\DokumenService;
use Illuminate\Database\Seeder;

/**
 * Demo Fase 7: AM membuat quotation PT Maju Jaya yang menunggu tanda tangan Manager & HR,
 * dan purchase order PT Sinar Digital Nusantara yang masih draft. Memakai service asli.
 */
class DokumenSeeder extends Seeder
{
    public function run(DokumenService $dokumen): void
    {
        $am = User::where('email', 'am@intellisys.test')->firstOrFail();
        $hr = User::where('email', 'hr@intellisys.test')->firstOrFail();
        $manager = User::where('email', 'manager@intellisys.test')->firstOrFail();

        $quotation = $dokumen->generate($am, [
            'jenis_dokumen' => 'quotation',
            'id_klien' => Klien::where('nama_perusahaan', 'PT Maju Jaya')->firstOrFail()->id_klien,
            'nomor_dokumen' => 'QUO/'.now()->format('Y/m').'/001',
            'tanggal_dokumen' => now()->toDateString(),
            'berlaku_sampai' => now()->addDays(30)->toDateString(),
            'perihal' => 'Penawaran penyediaan 2 Backend Developer',
            'items' => [
                ['deskripsi' => 'Backend Developer (Laravel) — per bulan', 'jumlah' => 2, 'harga_satuan' => 15000000],
                ['deskripsi' => 'Biaya rekrutmen & onboarding', 'jumlah' => 1, 'harga_satuan' => 5000000],
            ],
            'catatan' => 'Harga belum termasuk PPN 11%. Kontrak minimal 6 bulan.',
        ]);
        $dokumen->aturPenandatangan($quotation, [$manager->id_user, $hr->id_user]);

        $dokumen->generate($am, [
            'jenis_dokumen' => 'purchase_order',
            'id_klien' => Klien::where('nama_perusahaan', 'PT Sinar Digital Nusantara')->firstOrFail()->id_klien,
            'nomor_dokumen' => 'PO/'.now()->format('Y/m').'/014',
            'tanggal_dokumen' => now()->toDateString(),
            'perihal' => 'Pemesanan 1 QA Engineer',
            'items' => [['deskripsi' => 'QA Engineer — per bulan', 'jumlah' => 1, 'harga_satuan' => 11000000]],
        ]);
    }
}
