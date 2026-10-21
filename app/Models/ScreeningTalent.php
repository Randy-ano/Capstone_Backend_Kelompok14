<?php

namespace App\Models;

use App\Enums\HasilScreening;
use App\Observers\ScreeningTalentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([ScreeningTalentObserver::class])]
class ScreeningTalent extends Model
{
    use HasFactory;

    protected $table = 'screening_talent';

    protected $primaryKey = 'id_screening';

    protected $fillable = [
        'id_talent',
        'id_job_req',
        'id_ts',
        'skor_kecocokan',
        'tanggal_screening',
        'catatan_screening',
        'hasil_screening',
    ];

    protected $attributes = [
        'hasil_screening' => HasilScreening::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'skor_kecocokan' => 'decimal:2',
            'tanggal_screening' => 'datetime',
            'hasil_screening' => HasilScreening::class,
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function jobRequirement(): BelongsTo
    {
        return $this->belongsTo(JobRequirement::class, 'id_job_req', 'id_job_req');
    }

    public function talentSupport(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_ts', 'id_pegawai');
    }

    public function pengajuan(): HasOne
    {
        return $this->hasOne(PengajuanKandidat::class, 'id_screening', 'id_screening');
    }
}
