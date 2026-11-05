<?php

use App\Enums\Role;
use App\Enums\StatusPlacement;
use App\Models\MonitoringTalent;
use App\Models\Placement;
use Illuminate\Support\Carbon;

// Status tampil penempatan (revisi 2026-11-05): field turunan, bukan kolom database.
beforeEach(fn () => $this->travelTo(Carbon::parse('2026-11-05 09:00')));

it('menurunkan status tampil dari status dan tanggal penempatan', function (array $atribut, string $status, string $label) {
    $placement = Placement::factory()->create($atribut);
    MonitoringTalent::factory()->for($placement)->create();
    loginTalentPlacement($placement);

    $this->getJson('/api/v1/placement')
        ->assertOk()
        ->assertJsonPath('data.0.status_tampil', $status)
        ->assertJsonPath('data.0.label_status_tampil', $label);
    $this->getJson("/api/v1/placement/{$placement->id_placement}")
        ->assertJsonPath('data.status_tampil', $status);
    $this->getJson('/api/v1/monitoring')
        ->assertOk()
        ->assertJsonPath('data.0.status_tampil', $status)
        ->assertJsonPath('data.0.label_status_tampil', $label)
        ->assertJsonPath('data.0.placement.status_tampil', $status);
})->with([
    'aktif, sedang berjalan' => [['tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2027-03-31'], 'aktif', 'Aktif'],
    'aktif, mulai hari ini' => [['tanggal_mulai' => '2026-11-05'], 'aktif', 'Aktif'],
    'aktif, selesai hari ini' => [['tanggal_mulai' => '2026-10-01', 'tanggal_selesai' => '2026-11-05'], 'aktif', 'Aktif'],
    'aktif, belum mulai' => [['tanggal_mulai' => '2026-11-06'], 'belum_aktif', 'Belum Aktif'],
    'aktif, tanggal selesai lewat' => [['tanggal_mulai' => '2026-01-01', 'tanggal_selesai' => '2026-11-04'], 'selesai', 'Selesai'],
    'selesai' => [['status_placement' => StatusPlacement::Selesai], 'selesai', 'Selesai'],
    'dihentikan' => [['status_placement' => StatusPlacement::Dihentikan, 'tanggal_mulai' => '2026-12-01'], 'selesai', 'Selesai'],
]);

it('role lain menerima field yang sama', function () {
    Placement::factory()->create(['tanggal_mulai' => '2026-12-01']);
    loginSebagai(Role::Manager);

    $this->getJson('/api/v1/placement')->assertJsonPath('data.0.status_tampil', 'belum_aktif');
});
