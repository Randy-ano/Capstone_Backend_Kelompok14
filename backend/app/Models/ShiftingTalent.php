<?php

namespace App\Models;

use App\Enums\JenisShift;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftingTalent extends Model
{
    use HasFactory;

    protected $table = 'shifting_talent';

    protected $primaryKey = 'id_shift';

    protected $fillable = [
        'id_placement',
        'id_pengelola',
        'tanggal_shift',
        'jenis_shift',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_shift' => 'date',
            'jenis_shift' => JenisShift::class,
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'id_placement', 'id_placement');
    }

    public function pengelola(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengelola', 'id_user');
    }
}
