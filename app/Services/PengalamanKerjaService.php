<?php

namespace App\Services;

use App\Models\PengalamanKerjaTalent;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Lama pengalaman kerja talent dalam tahun. Dipakai detail talent pool
 * dan skor kecocokan R9 (bagian pengalaman), jadi rumusnya cukup ada di sini.
 */
class PengalamanKerjaService
{
    private const HARI_PER_TAHUN = 365.25;

    /**
     * Total tahun dari semua periode kerja. Periode tumpang tindih digabung
     * agar tidak dihitung dua kali; pekerjaan yang masih berjalan dihitung sampai hari ini.
     * Baris tanpa tanggal mulai, atau tanpa tanggal selesai padahal tidak masih bekerja, diabaikan.
     *
     * @param  iterable<PengalamanKerjaTalent>  $pengalaman
     */
    public function lamaTahun(iterable $pengalaman, ?CarbonInterface $hariIni = null): float
    {
        $hariIni = CarbonImmutable::instance($hariIni ?? now())->startOfDay();

        $periode = [];
        foreach ($pengalaman as $baris) {
            if ($baris->tanggal_mulai === null) {
                continue;
            }

            $selesai = $baris->masih_bekerja ? $hariIni : $baris->tanggal_selesai;
            if ($selesai === null) {
                continue;
            }

            $mulai = CarbonImmutable::instance($baris->tanggal_mulai)->startOfDay();
            $selesai = CarbonImmutable::instance($selesai)->startOfDay()->min($hariIni);

            if ($selesai->gt($mulai)) {
                $periode[] = [$mulai, $selesai];
            }
        }

        usort($periode, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $totalHari = 0;
        $gabungan = null;
        foreach ($periode as [$mulai, $selesai]) {
            if ($gabungan !== null && $mulai->lte($gabungan[1])) {
                $gabungan[1] = $gabungan[1]->max($selesai);

                continue;
            }

            if ($gabungan !== null) {
                $totalHari += $gabungan[0]->diffInDays($gabungan[1]);
            }
            $gabungan = [$mulai, $selesai];
        }

        if ($gabungan !== null) {
            $totalHari += $gabungan[0]->diffInDays($gabungan[1]);
        }

        return $totalHari / self::HARI_PER_TAHUN;
    }
}
