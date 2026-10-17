<?php

namespace App\Models;

use App\Enums\IndikatorSla;
use App\Enums\StatusTahapan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgresPemenuhan extends Model
{
    use HasFactory;

    protected $table = 'progres_pemenuhan';

    protected $primaryKey = 'id_progres';

    protected $fillable = [
        'id_job_req',
        'id_talent',
        'id_penanggung_jawab',
        'status_tahapan',
        'indikator_sla',
        'log_waktu_pembaruan',
    ];

    protected function casts(): array
    {
        return [
            'status_tahapan' => StatusTahapan::class,
            'indikator_sla' => IndikatorSla::class,
            'log_waktu_pembaruan' => 'datetime',
        ];
    }

    public function jobRequirement(): BelongsTo
    {
        return $this->belongsTo(JobRequirement::class, 'id_job_req', 'id_job_req');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_penanggung_jawab', 'id_pegawai');
    }
}
