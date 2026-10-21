<?php

namespace App\Models;

use App\Enums\StatusEvaluasi;
use App\Observers\EvaluasiKandidatObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([EvaluasiKandidatObserver::class])]
class EvaluasiKandidat extends Model
{
    use HasFactory;

    protected $table = 'evaluasi_kandidat';

    protected $primaryKey = 'id_evaluasi';

    protected $fillable = [
        'id_pengajuan',
        'id_hr_client',
        'nilai_score',
        'catatan_evaluasi',
        'status_evaluasi',
    ];

    protected function casts(): array
    {
        return [
            'nilai_score' => 'integer',
            'status_evaluasi' => StatusEvaluasi::class,
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanKandidat::class, 'id_pengajuan', 'id_pengajuan');
    }

    public function hrClient(): BelongsTo
    {
        return $this->belongsTo(HrClient::class, 'id_hr_client', 'id_hr_client');
    }

    public function placement(): HasOne
    {
        return $this->hasOne(Placement::class, 'id_evaluasi', 'id_evaluasi');
    }
}
