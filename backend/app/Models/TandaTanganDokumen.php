<?php

namespace App\Models;

use App\Enums\StatusTtd;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TandaTanganDokumen extends Model
{
    use HasFactory;

    protected $table = 'tanda_tangan_dokumen';

    protected $primaryKey = 'id_ttd';

    protected $fillable = [
        'id_dokumen',
        'id_user_penandatangan',
        'file_tanda_tangan',
        'tanggal_ttd',
        'status_ttd',
    ];

    protected $attributes = [
        'status_ttd' => StatusTtd::Menunggu->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_ttd' => 'datetime',
            'status_ttd' => StatusTtd::class,
        ];
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class, 'id_dokumen', 'id_dokumen');
    }

    public function penandatangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penandatangan', 'id_user');
    }
}
