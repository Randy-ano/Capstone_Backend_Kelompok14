<?php

namespace App\Models;

use App\Enums\StatusPengajuan;
use App\Observers\PengajuanKandidatObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([PengajuanKandidatObserver::class])]
class PengajuanKandidat extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_kandidat';

    protected $primaryKey = 'id_pengajuan';

    protected $fillable = [
        'id_screening',
        'id_talent',
        'id_job_req',
        'id_am',
        'tanggal_pengajuan',
        'status_pengajuan',
    ];

    protected $attributes = [
        'status_pengajuan' => StatusPengajuan::Diajukan->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pengajuan' => 'datetime',
            'status_pengajuan' => StatusPengajuan::class,
        ];
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(ScreeningTalent::class, 'id_screening', 'id_screening');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function jobRequirement(): BelongsTo
    {
        return $this->belongsTo(JobRequirement::class, 'id_job_req', 'id_job_req');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_am', 'id_pegawai');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(JadwalInterview::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function evaluasi(): HasOne
    {
        return $this->hasOne(EvaluasiKandidat::class, 'id_pengajuan', 'id_pengajuan');
    }
}
