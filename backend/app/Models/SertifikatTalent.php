<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SertifikatTalent extends Model
{
    use HasFactory;

    protected $table = 'sertifikat_talent';

    protected $primaryKey = 'id_sertifikat';

    protected $fillable = [
        'id_talent',
        'nama_sertifikat',
        'penerbit',
        'tanggal_terbit',
        'tanggal_kedaluwarsa',
        'file_sertifikat',
        'url_sertifikat',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_terbit' => 'date',
            'tanggal_kedaluwarsa' => 'date',
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(
            Talent::class,
            'id_talent',
            'id_talent'
        );
    }
}