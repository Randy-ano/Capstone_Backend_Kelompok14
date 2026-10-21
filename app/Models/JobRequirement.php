<?php

namespace App\Models;

use App\Enums\StatusReq;
use App\Observers\JobRequirementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([JobRequirementObserver::class])]
class JobRequirement extends Model
{
    use HasFactory;

    protected $table = 'job_requirement';

    protected $primaryKey = 'id_job_req';

    protected $fillable = [
        'id_klien',
        'id_am',
        'posisi',
        'jumlah_kebutuhan',
        'kualifikasi',
        'pengalaman_min',
        'status_req',
        'sla_deadline',
    ];

    protected $attributes = [
        'pengalaman_min' => 0,
        'status_req' => StatusReq::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'jumlah_kebutuhan' => 'integer',
            'pengalaman_min' => 'integer',
            'status_req' => StatusReq::class,
            'sla_deadline' => 'datetime',
        ];
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'id_klien', 'id_klien');
    }

    public function accountManager(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_am', 'id_pegawai');
    }

    public function kompetensi(): BelongsToMany
    {
        return $this->belongsToMany(Kompetensi::class, 'job_req_kompetensi', 'id_job_req', 'id_kompetensi');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(ScreeningTalent::class, 'id_job_req', 'id_job_req');
    }

    public function progres(): HasMany
    {
        return $this->hasMany(ProgresPemenuhan::class, 'id_job_req', 'id_job_req');
    }

    /** Log progres terbaru (id terbesar), untuk tabel monitoring. */
    public function progresTerakhir(): HasOne
    {
        return $this->hasOne(ProgresPemenuhan::class, 'id_job_req', 'id_job_req')->latestOfMany('id_progres');
    }

    public function pengajuanKandidat(): HasMany
    {
        return $this->hasMany(PengajuanKandidat::class, 'id_job_req', 'id_job_req');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'id_job_req', 'id_job_req');
    }
}
