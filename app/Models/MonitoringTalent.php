<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringTalent extends Model
{
    use HasFactory;

    protected $table = 'monitoring_talent';

    protected $primaryKey = 'id_monitoring';

    protected $fillable = [
        'id_placement',
        'id_manager',
        'tanggal_monitoring',
        'hasil_monitoring',
        'catatan_tindak_lanjut',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_monitoring' => 'datetime',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'id_placement', 'id_placement');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_manager', 'id_pegawai');
    }
}
