<?php

namespace App\Models;

use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dokumen extends Model
{
    use HasFactory;

    protected $table = 'dokumen';

    protected $primaryKey = 'id_dokumen';

    protected $fillable = [
        'id_user_uploader',
        'id_klien',
        'id_talent',
        'id_placement',
        'jenis_dokumen',
        'file_path',
        'status_dokumen',
        'tanggal_unggah',
    ];

    protected $attributes = [
        'status_dokumen' => StatusDokumen::Draft->value,
    ];

    protected function casts(): array
    {
        return [
            'jenis_dokumen' => JenisDokumen::class,
            'status_dokumen' => StatusDokumen::class,
            'tanggal_unggah' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_uploader', 'id_user');
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'id_klien', 'id_klien');
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'id_placement', 'id_placement');
    }

    public function tandaTangan(): HasMany
    {
        return $this->hasMany(TandaTanganDokumen::class, 'id_dokumen', 'id_dokumen');
    }
}
