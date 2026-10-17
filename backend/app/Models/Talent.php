<?php

namespace App\Models;

use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Talent extends Model
{
    use HasFactory;

    protected $table = 'talent';

    protected $primaryKey = 'id_talent';

    protected $fillable = [
        'id_user',
        'nama_lengkap',
        'pendidikan',
        'pengalaman_kerja',
        'file_cv',
        'status_kelengkapan',
        'status_kandidat',
    ];

    protected $attributes = [
        'status_kelengkapan' => StatusKelengkapan::BelumLengkap->value,
        'status_kandidat' => StatusKandidat::Tersedia->value,
    ];

    protected function casts(): array
    {
        return [
            'status_kelengkapan' => StatusKelengkapan::class,
            'status_kandidat' => StatusKandidat::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function kompetensi(): BelongsToMany
    {
        return $this->belongsToMany(Kompetensi::class, 'talent_kompetensi', 'id_talent', 'id_kompetensi');
    }

    public function talentPool(): HasOne
    {
        return $this->hasOne(TalentPool::class, 'id_talent', 'id_talent');
    }

    public function screenings(): HasMany
    {
        return $this->hasMany(ScreeningTalent::class, 'id_talent', 'id_talent');
    }

    public function progres(): HasMany
    {
        return $this->hasMany(ProgresPemenuhan::class, 'id_talent', 'id_talent');
    }

    public function pengajuanKandidat(): HasMany
    {
        return $this->hasMany(PengajuanKandidat::class, 'id_talent', 'id_talent');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'id_talent', 'id_talent');
    }

    public function cuti(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class, 'id_talent', 'id_talent');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'id_talent', 'id_talent');
    }
}
