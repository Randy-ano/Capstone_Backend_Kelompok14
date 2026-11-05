<?php

namespace App\Models;

use App\Enums\HasilScreening;
use App\Observers\ScreeningTalentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu baris = satu usulan kandidat dari Talent Support ke Account Manager (revisi 2026-11-05).
 * Hasil `lolos`/`pending` = usulan masih berlaku, `tidak_lolos` = tidak diteruskan AM. Alasan AM disimpan di
 * `catatan_screening` setelah penanda {@see self::PENANDA_ALASAN} (tanpa kolom baru).
 */
#[ObservedBy([ScreeningTalentObserver::class])]
class ScreeningTalent extends Model
{
    use HasFactory;

    public const PENANDA_ALASAN = '[Tidak diteruskan AM] ';

    public const STATUS_USULAN = [
        'menunggu_am' => 'Menunggu Tinjauan AM',
        'diteruskan' => 'Diteruskan ke Klien',
        'tidak_diteruskan' => 'Tidak Diteruskan',
    ];

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

    /** Catatan Talent Support (tanpa alasan AM). */
    public function catatanTs(): ?string
    {
        $catatan = $this->catatan_screening;
        if ($catatan === null) {
            return null;
        }
        $posisi = mb_strrpos($catatan, self::PENANDA_ALASAN);

        return $posisi === false ? $catatan : (rtrim(mb_substr($catatan, 0, $posisi)) ?: null);
    }

    /** Alasan Account Manager tidak meneruskan usulan, null jika tidak ada. */
    public function alasanTidakDiteruskan(): ?string
    {
        $catatan = (string) $this->catatan_screening;
        $posisi = mb_strrpos($catatan, self::PENANDA_ALASAN);

        return $posisi === false ? null : mb_substr($catatan, $posisi + mb_strlen(self::PENANDA_ALASAN));
    }

    /** Gabungkan catatan TS dan alasan AM menjadi isi kolom `catatan_screening`. */
    public static function gabungCatatan(?string $catatanTs, ?string $alasan): ?string
    {
        if ($alasan === null || $alasan === '') {
            return $catatanTs;
        }

        return ($catatanTs ? $catatanTs."\n\n" : '').self::PENANDA_ALASAN.$alasan;
    }

    /** menunggu_am | diteruskan | tidak_diteruskan (butuh relasi pengajuan). */
    public function statusUsulan(): string
    {
        return match (true) {
            $this->hasil_screening === HasilScreening::TidakLolos => 'tidak_diteruskan',
            $this->pengajuan !== null => 'diteruskan',
            default => 'menunggu_am',
        };
    }

    /** Padanan query dari statusUsulan(). */
    public function scopeStatusUsulan(Builder $query, string $status): Builder
    {
        return match ($status) {
            'tidak_diteruskan' => $query->where('hasil_screening', HasilScreening::TidakLolos),
            'diteruskan' => $query->has('pengajuan'),
            default => $query->where('hasil_screening', '!=', HasilScreening::TidakLolos)->doesntHave('pengajuan'),
        };
    }
}
