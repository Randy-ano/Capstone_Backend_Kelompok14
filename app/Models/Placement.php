<?php

namespace App\Models;

use App\Enums\StatusPlacement;
use App\Enums\StatusTampilPlacement;
use App\Observers\PlacementObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Status tampil: Selesai jika status selesai/dihentikan atau tanggal_selesai sudah lewat; Belum Aktif jika
     * tanggal_mulai masih di masa depan; selain itu Aktif.
     */
    public function statusTampil(): StatusTampilPlacement
    {
        $hariIni = now()->startOfDay();

        return match (true) {
            $this->status_placement !== StatusPlacement::Aktif => StatusTampilPlacement::Selesai,
            $this->tanggal_selesai !== null && $this->tanggal_selesai->lt($hariIni) => StatusTampilPlacement::Selesai,
            $this->tanggal_mulai !== null && $this->tanggal_mulai->gt($hariIni) => StatusTampilPlacement::BelumAktif,
            default => StatusTampilPlacement::Aktif,
        };
    }

    /** Padanan query dari statusTampil(). */
    public function scopeStatusTampil(Builder $query, StatusTampilPlacement $status): Builder
    {
        $hariIni = now()->toDateString();
        if ($status === StatusTampilPlacement::Selesai) {
            return $query->where(fn (Builder $q) => $q
                ->where('status_placement', '!=', StatusPlacement::Aktif)
                ->orWhereDate('tanggal_selesai', '<', $hariIni));
        }

        return $query->berjalan()->when(
            $status === StatusTampilPlacement::BelumAktif,
            fn (Builder $q) => $q->whereDate('tanggal_mulai', '>', $hariIni),
            fn (Builder $q) => $q->whereDate('tanggal_mulai', '<=', $hariIni),
        );
    }

    /** Placement berstatus tampil Belum Aktif atau Aktif: status aktif dan tanggal selesai belum lewat. */
    public function scopeBerjalan(Builder $query): Builder
    {
        return $query->where('status_placement', StatusPlacement::Aktif)
            ->where(fn (Builder $q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', now()->toDateString()));
    }
}
