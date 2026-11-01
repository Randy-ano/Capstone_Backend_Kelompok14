<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;

/**
 * Jam data demo: semua kejadian seed diberi waktu relatif terhadap hari seeder dijalankan, lalu dijalankan
 * dengan jam aplikasi dimundurkan ke waktu itu. Dengan begitu Service, Observer, created_at, dan indikator SLA
 * mencatat waktu yang sama seperti jika kejadiannya dilakukan lewat aplikasi, dan dua kali seed di hari
 * yang sama menghasilkan data yang sama.
 */
final class Linimasa
{
    private static ?Carbon $acuan = null;

    public static function mulai(): void
    {
        self::$acuan = Carbon::now()->startOfDay();
    }

    /** Waktu `$selisih` hari dari hari seed (negatif = lalu). */
    public static function hari(int $selisih, int $jam = 9, int $menit = 0): Carbon
    {
        return self::acuan()->addDays($selisih)->setTime($jam, $menit);
    }

    /** Tanggal `$tanggal` pada `$lalu` bulan sebelum bulan seed. */
    public static function bulan(int $lalu, int $tanggal = 10, int $jam = 9): Carbon
    {
        return self::acuan()->subMonthsNoOverflow($lalu)->setDay($tanggal)->setTime($jam, 0);
    }

    /**
     * Jalankan aksi dengan jam aplikasi berada di `$waktu`.
     *
     * @template T
     *
     * @param  callable(): T  $aksi
     * @return T
     */
    public static function pada(Carbon $waktu, callable $aksi): mixed
    {
        $sebelumnya = Carbon::getTestNow();
        Carbon::setTestNow($waktu);

        try {
            return $aksi();
        } finally {
            Carbon::setTestNow($sebelumnya);
        }
    }

    private static function acuan(): Carbon
    {
        self::$acuan ??= Carbon::now()->startOfDay();

        return self::$acuan->copy();
    }
}
