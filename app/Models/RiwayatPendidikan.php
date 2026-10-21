<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPendidikan extends Model
{
    use HasFactory;

    protected $table = 'riwayat_pendidikan';

    protected $primaryKey = 'id_riwayat_pendidikan';

    protected $fillable = [
        'id_talent',
        'jenjang',
        'nama_institusi',
        'jurusan',
        'tahun_mulai',
        'tahun_selesai',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'tahun_mulai' => 'integer',
            'tahun_selesai' => 'integer',
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