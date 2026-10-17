<?php

namespace App\Models;

use App\Enums\StatusKetersediaan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentPool extends Model
{
    use HasFactory;

    protected $table = 'talent_pool';

    protected $primaryKey = 'id_talent_pool';

    protected $fillable = [
        'id_talent',
        'tanggal_masuk',
        'status_ketersediaan',
        'tag_keahlian',
    ];

    protected $attributes = [
        'status_ketersediaan' => StatusKetersediaan::Tersedia->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'status_ketersediaan' => StatusKetersediaan::class,
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }
}
