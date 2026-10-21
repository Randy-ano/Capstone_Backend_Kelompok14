<?php

use App\Models\PengalamanKerjaTalent;
use App\Services\PengalamanKerjaService;
use Illuminate\Support\Carbon;

/**
 * White box: perhitungan lama pengalaman (dipakai detail talent pool dan R9).
 *
 * @param  list<array{0: ?string, 1: ?string, 2?: bool}>  $periode  [tanggal_mulai, tanggal_selesai, masih_bekerja]
 */
function lamaPengalaman(array $periode, string $hariIni = '2026-10-21'): float
{
    $baris = array_map(fn (array $p) => new PengalamanKerjaTalent([
        'tanggal_mulai' => $p[0],
        'tanggal_selesai' => $p[1],
        'masih_bekerja' => $p[2] ?? false,
    ]), $periode);

    return round(app(PengalamanKerjaService::class)->lamaTahun($baris, Carbon::parse($hariIni)), 2);
}

it('menghitung 0 jika tidak ada pengalaman', function () {
    expect(lamaPengalaman([]))->toBe(0.0);
});

it('menggabungkan periode tumpang tindih (contoh spec R9)', function () {
    // Jan–Jun 2023 dan Apr–Des 2023 → Jan–Des 2023 ≈ 1 tahun, bukan 1,5.
    expect(lamaPengalaman([['2023-01-01', '2023-06-30'], ['2023-04-01', '2024-01-01']]))->toBe(1.0);
});

it('tidak menghitung periode yang berada di dalam periode lain', function () {
    expect(lamaPengalaman([['2020-01-01', '2024-01-01'], ['2021-01-01', '2022-01-01']]))->toBe(4.0);
});

it('menjumlahkan periode terpisah tanpa menghitung jeda', function () {
    expect(lamaPengalaman([['2022-01-01', '2023-01-01'], ['2020-01-01', '2021-01-01']]))->toBe(2.0);
});

it('menghitung pekerjaan yang masih berjalan sampai hari ini', function () {
    expect(lamaPengalaman([['2024-10-21', null, true]]))->toBe(2.0)
        // tanggal_selesai diabaikan jika masih_bekerja.
        ->and(lamaPengalaman([['2024-10-21', '2025-01-01', true]]))->toBe(2.0);
});

it('mengabaikan baris tanpa tanggal mulai atau tanpa tanggal selesai yang tidak masih bekerja', function () {
    expect(lamaPengalaman([[null, '2024-01-01'], ['2020-01-01', null], ['2023-01-01', '2024-01-01']]))->toBe(1.0);
});

it('memotong tanggal di masa depan sampai hari ini', function () {
    expect(lamaPengalaman([['2025-10-21', '2030-01-01']]))->toBe(1.0)
        ->and(lamaPengalaman([['2027-01-01', null, true]]))->toBe(0.0);
});
