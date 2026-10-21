<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengalamanKerjaTalent extends Model
{
    use HasFactory;

    protected $table = 'pengalaman_kerja_talent';

    protected $primaryKey = 'id_pengalaman_kerja';

    protected $fillable = [
        'id_talent',
        'nama_perusahaan',
        'posisi',
        'jenis_pekerjaan',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'masih_bekerja',
        'deskripsi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'masih_bekerja' => 'boolean',
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