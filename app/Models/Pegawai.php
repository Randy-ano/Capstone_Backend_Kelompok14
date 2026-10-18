<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';

    protected $primaryKey = 'id_pegawai';

    protected $fillable = [
        'id_user',
        'nama_pegawai',
        'jabatan',
        'no_telepon',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function jobRequirements(): HasMany
    {
        return $this->hasMany(JobRequirement::class, 'id_am', 'id_pegawai');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(ScreeningTalent::class, 'id_ts', 'id_pegawai');
    }

    public function progresDitangani(): HasMany
    {
        return $this->hasMany(ProgresPemenuhan::class, 'id_penanggung_jawab', 'id_pegawai');
    }

    public function pengajuanKandidat(): HasMany
    {
        return $this->hasMany(PengajuanKandidat::class, 'id_am', 'id_pegawai');
    }

    public function cutiDiputuskan(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class, 'id_approver_manager', 'id_pegawai');
    }

    public function monitorings(): HasMany
    {
        return $this->hasMany(MonitoringTalent::class, 'id_manager', 'id_pegawai');
    }

    public function laporanDitindaklanjuti(): HasMany
    {
        return $this->hasMany(LaporanKeluhan::class, 'id_penindak_lanjut', 'id_pegawai');
    }
}
