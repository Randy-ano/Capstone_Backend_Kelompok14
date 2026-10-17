<?php

namespace App\Models;

use App\Enums\StatusLaporan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaporanKeluhan extends Model
{
    use HasFactory;

    protected $table = 'laporan_keluhan';

    protected $primaryKey = 'id_laporan';

    protected $fillable = [
        'id_placement',
        'id_hr_client',
        'id_penindak_lanjut',
        'judul_laporan',
        'deskripsi_keluhan',
        'tanggal_laporan',
        'status_laporan',
        'hasil_tindak_lanjut',
        'tanggal_tindak_lanjut',
    ];

    protected $attributes = [
        'status_laporan' => StatusLaporan::Baru->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_laporan' => 'datetime',
            'status_laporan' => StatusLaporan::class,
            'tanggal_tindak_lanjut' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'id_placement', 'id_placement');
    }

    public function hrClient(): BelongsTo
    {
        return $this->belongsTo(HrClient::class, 'id_hr_client', 'id_hr_client');
    }

    public function penindakLanjut(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_penindak_lanjut', 'id_pegawai');
    }
}
