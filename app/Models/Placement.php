<?php

namespace App\Models;

use App\Enums\StatusPlacement;
use App\Observers\PlacementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([PlacementObserver::class])]
class Placement extends Model
{
    use HasFactory;

    protected $table = 'placement';

    protected $primaryKey = 'id_placement';

    protected $fillable = [
        'id_evaluasi',
        'id_talent',
        'id_job_req',
        'id_klien',
        'posisi_penempatan',
        'tanggal_mulai',
        'tanggal_selesai',
        'status_placement',
    ];

    protected $attributes = [
        'status_placement' => StatusPlacement::Aktif->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_placement' => StatusPlacement::class,
        ];
    }

    public function evaluasi(): BelongsTo
    {
        return $this->belongsTo(EvaluasiKandidat::class, 'id_evaluasi', 'id_evaluasi');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function jobRequirement(): BelongsTo
    {
        return $this->belongsTo(JobRequirement::class, 'id_job_req', 'id_job_req');
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'id_klien', 'id_klien');
    }

    public function cuti(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class, 'id_placement', 'id_placement');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(ShiftingTalent::class, 'id_placement', 'id_placement');
    }

    public function monitorings(): HasMany
    {
        return $this->hasMany(MonitoringTalent::class, 'id_placement', 'id_placement');
    }

    public function laporanKeluhan(): HasMany
    {
        return $this->hasMany(LaporanKeluhan::class, 'id_placement', 'id_placement');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'id_placement', 'id_placement');
    }
}
