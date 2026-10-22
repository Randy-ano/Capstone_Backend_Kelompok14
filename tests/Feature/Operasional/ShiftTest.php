<?php

use App\Enums\JenisShift;
use App\Enums\Role;
use App\Enums\StatusPlacement;
use App\Models\Placement;
use App\Models\ShiftingTalent;

function tgl(int $hari): string
{
    return now()->addDays($hari)->toDateString();
}

describe('GET /shift', function () {
    it('talent hanya melihat shift miliknya, urut tanggal, dan bisa difilter rentang tanggal', function () {
        $placement = Placement::factory()->create();
        foreach ([3, 1, 2] as $hari) {
            ShiftingTalent::factory()->for($placement)->create(['tanggal_shift' => tgl($hari)]);
        }
        ShiftingTalent::factory()->create();
        loginTalentPlacement($placement);

        $this->getJson('/api/v1/shift')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.tanggal_shift', tgl(1))
            ->assertJsonPath('data.2.tanggal_shift', tgl(3))
            ->assertJsonStructure(['data' => [['id_shift', 'tanggal_shift', 'jenis_shift', 'keterangan', 'diatur_oleh', 'placement' => ['id_placement', 'talent', 'klien']]]]);

        $this->getJson('/api/v1/shift?dari='.tgl(2).'&sampai='.tgl(3))->assertJsonCount(2, 'data');
    });

    it('HR Client melihat shift talent di kliennya, Manager semua', function () {
        $placement = Placement::factory()->create();
        ShiftingTalent::factory()->for($placement)->create();
        ShiftingTalent::factory()->count(2)->create();

        loginHrKlien($placement);
        $this->getJson('/api/v1/shift')->assertJsonCount(1, 'data');

        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/shift')->assertJsonCount(3, 'data');
    });

    it('menolak AM dan TS (403) serta rentang tanggal terbalik (422)', function () {
        loginSebagai(Role::TalentSupport);
        $this->getJson('/api/v1/shift')->assertForbidden();

        loginSebagai(Role::Manager);
        $this->getJson('/api/v1/shift?dari='.tgl(5).'&sampai='.tgl(1))->assertUnprocessable()->assertJsonValidationErrors(['sampai']);
    });
});

describe('POST /shift (input massal, R22)', function () {
    it('HR Client membuat shift untuk banyak tanggal sekaligus dan memperbarui tanggal yang sudah ada', function () {
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString()]);
        ShiftingTalent::factory()->for($placement)->create(['tanggal_shift' => tgl(1), 'jenis_shift' => JenisShift::Malam]);
        $hr = loginHrKlien($placement);

        $this->postJson('/api/v1/shift', [
            'id_placement' => $placement->id_placement,
            'tanggal' => [tgl(1), tgl(2), tgl(3)],
            'jenis_shift' => 'pagi',
            'keterangan' => 'WFO',
        ])
            ->assertCreated()
            ->assertExactJson(['data' => ['dibuat' => 2, 'diperbarui' => 1], 'message' => '2 shift dibuat, 1 shift diperbarui.']);

        $shift = $placement->shifts()->orderBy('tanggal_shift')->get();
        expect($shift)->toHaveCount(3)
            ->and($shift->pluck('jenis_shift')->unique()->all())->toBe([JenisShift::Pagi])
            ->and($shift->pluck('id_pengelola')->unique()->all())->toBe([$hr->id_user]);
    });

    it('Manager boleh mengatur shift placement klien mana pun', function () {
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString()]);
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/shift', ['id_placement' => $placement->id_placement, 'tanggal' => [tgl(1)], 'jenis_shift' => 'libur'])
            ->assertCreated()
            ->assertJsonPath('message', '1 shift dibuat.');
    });

    it('menolak data tidak valid (422)', function (Closure $body, string $kolom, ?string $pesan) {
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => tgl(30)]);
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/shift', $body($placement))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($pesan ? [$kolom => $pesan] : [$kolom]);
    })->with([
        'kosong' => [fn () => [], 'tanggal', 'Pilih minimal 1 tanggal shift.'],
        'jenis tidak dikenal' => [fn ($p) => ['id_placement' => $p->id_placement, 'tanggal' => [tgl(1)], 'jenis_shift' => 'sore'], 'jenis_shift', null],
        'format tanggal' => [fn ($p) => ['id_placement' => $p->id_placement, 'tanggal' => ['01/11/2026'], 'jenis_shift' => 'pagi'], 'tanggal.0', 'Format tanggal shift harus YYYY-MM-DD.'],
        'tanggal ganda' => [fn ($p) => ['id_placement' => $p->id_placement, 'tanggal' => [tgl(1), tgl(1)], 'jenis_shift' => 'pagi'], 'tanggal.0', 'Tanggal shift tidak boleh ganda.'],
        'di luar periode' => [fn ($p) => ['id_placement' => $p->id_placement, 'tanggal' => [tgl(1), tgl(31)], 'jenis_shift' => 'pagi'], 'tanggal', null],
        'sebelum mulai' => [fn ($p) => ['id_placement' => $p->id_placement, 'tanggal' => [now()->subDay()->toDateString()], 'jenis_shift' => 'pagi'], 'tanggal', null],
    ]);

    it('menolak placement yang tidak aktif (422)', function () {
        $placement = Placement::factory()->create(['status_placement' => StatusPlacement::Selesai]);
        loginSebagai(Role::Manager);

        $this->postJson('/api/v1/shift', ['id_placement' => $placement->id_placement, 'tanggal' => [tgl(1)], 'jenis_shift' => 'pagi'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.id_placement.0', 'Shift hanya dapat diatur untuk placement yang masih aktif.');
    });

    it('menolak HR Client dari klien lain dan role lain (403)', function () {
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString()]);
        $body = ['id_placement' => $placement->id_placement, 'tanggal' => [tgl(1)], 'jenis_shift' => 'pagi'];

        loginSebagai(Role::HrClient);
        $this->postJson('/api/v1/shift', $body)->assertForbidden()->assertExactJson(['message' => 'Anda tidak memiliki akses ke placement ini.']);

        foreach ([Role::Talent, Role::AccountManager, Role::TalentSupport] as $role) {
            loginSebagai($role);
            $this->postJson('/api/v1/shift', $body)->assertForbidden();
        }

        expect(ShiftingTalent::count())->toBe(0);
    });
});

describe('PUT & DELETE /shift/{id}', function () {
    it('mengubah jenis dan tanggal shift, menolak tanggal yang sudah terisi', function () {
        $placement = Placement::factory()->create(['tanggal_mulai' => now()->toDateString()]);
        $shift = ShiftingTalent::factory()->for($placement)->create(['tanggal_shift' => tgl(1), 'jenis_shift' => JenisShift::Pagi]);
        ShiftingTalent::factory()->for($placement)->create(['tanggal_shift' => tgl(2)]);
        loginHrKlien($placement);

        $this->putJson("/api/v1/shift/{$shift->id_shift}", ['jenis_shift' => 'malam', 'tanggal_shift' => tgl(3)])
            ->assertOk()
            ->assertJsonPath('message', 'Shift berhasil diperbarui.')
            ->assertJsonPath('data.jenis_shift', 'malam')
            ->assertJsonPath('data.tanggal_shift', tgl(3));

        $this->putJson("/api/v1/shift/{$shift->id_shift}", ['tanggal_shift' => tgl(2)])
            ->assertUnprocessable()
            ->assertJsonPath('errors.tanggal_shift.0', 'Talent sudah memiliki shift pada tanggal tersebut.');
        $this->putJson("/api/v1/shift/{$shift->id_shift}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis_shift']);
    });

    it('menghapus shift', function () {
        $shift = ShiftingTalent::factory()->create();
        loginSebagai(Role::Manager);

        $this->deleteJson("/api/v1/shift/{$shift->id_shift}")
            ->assertOk()
            ->assertExactJson(['data' => null, 'message' => 'Shift berhasil dihapus.']);
        expect(ShiftingTalent::count())->toBe(0);
    });

    it('menolak HR Client dari klien lain, talent, dan 404', function () {
        $shift = ShiftingTalent::factory()->create();

        loginSebagai(Role::HrClient);
        $this->putJson("/api/v1/shift/{$shift->id_shift}", ['jenis_shift' => 'malam'])->assertForbidden();
        $this->deleteJson("/api/v1/shift/{$shift->id_shift}")->assertForbidden();

        loginTalentPlacement($shift->placement);
        $this->deleteJson("/api/v1/shift/{$shift->id_shift}")->assertForbidden();

        loginSebagai(Role::Manager);
        $this->deleteJson('/api/v1/shift/999')->assertNotFound();

        expect(ShiftingTalent::count())->toBe(1);
    });
});
