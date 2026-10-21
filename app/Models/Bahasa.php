<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bahasa extends Model
{
    use HasFactory;

    protected $table = 'bahasa';

    protected $primaryKey = 'id_bahasa';

    protected $fillable = [
        'nama_bahasa',
    ];

    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(
            Talent::class,
            'talent_bahasa',
            'id_bahasa',
            'id_talent'
        )
            ->withPivot(
                'id_talent_bahasa',
                'tingkat_kemampuan'
            )
            ->withTimestamps();
    }
}