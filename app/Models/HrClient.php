<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrClient extends Model
{
    use HasFactory;

    protected $table = 'hr_client';

    protected $primaryKey = 'id_hr_client';

    protected $fillable = [
        'id_user',
        'id_klien',
        'nama_hr',
        'jabatan',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'id_klien', 'id_klien');
    }

    public function evaluasi(): HasMany
    {
        return $this->hasMany(EvaluasiKandidat::class, 'id_hr_client', 'id_hr_client');
    }

    public function cutiDiputuskan(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class, 'id_approver_klien', 'id_hr_client');
    }

    public function laporanKeluhan(): HasMany
    {
        return $this->hasMany(LaporanKeluhan::class, 'id_hr_client', 'id_hr_client');
    }
}
