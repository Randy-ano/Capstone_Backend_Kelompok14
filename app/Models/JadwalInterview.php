<?php

namespace App\Models;

use App\Enums\StatusInterview;
use App\Observers\JadwalInterviewObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([JadwalInterviewObserver::class])]
class JadwalInterview extends Model
{
    use HasFactory;

    protected $table = 'jadwal_interview';

    protected $primaryKey = 'id_interview';

    protected $fillable = [
        'id_pengajuan',
        'tanggal_interview',
        'waktu_interview',
        'link_lokasi_meeting',
        'instruksi',
        'status_interview',
    ];

    protected $attributes = [
        'status_interview' => StatusInterview::Dijadwalkan->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_interview' => 'date',
            'status_interview' => StatusInterview::class,
        ];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanKandidat::class, 'id_pengajuan', 'id_pengajuan');
    }
}
