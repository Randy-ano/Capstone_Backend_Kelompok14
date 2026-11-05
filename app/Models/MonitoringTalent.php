<?php

namespace App\Models;

use App\Enums\TipeMonitoring;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu entri linimasa monitoring kinerja sebuah penempatan: catatan (HR Client / Manager) atau Surat Peringatan
 * (Manager, wajib lampiran dokumen SP). `id_manager` peninggalan skema lama, tidak dipakai lagi.
 */
class MonitoringTalent extends Model
{
    use HasFactory;

    protected $table = 'monitoring_talent';

    protected $primaryKey = 'id_monitoring';

    protected $fillable = [
        'id_placement',
        'id_manager',
        'id_user_pencatat',
        'tipe',
        'id_dokumen',
        'tanggal_monitoring',
        'hasil_monitoring',
        'catatan_tindak_lanjut',
    ];

    protected $attributes = [
        'tipe' => TipeMonitoring::Catatan->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_monitoring' => 'datetime',
            'tipe' => TipeMonitoring::class,
        ];
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_pencatat', 'id_user');
    }

    /** Urutan SP ini dalam penempatannya (SP 1, SP 2, …); null untuk catatan. */
    public function nomorSp(): ?int
    {
        if ($this->tipe !== TipeMonitoring::Sp) {
            return null;
        }

        return self::where('id_placement', $this->id_placement)->where('tipe', TipeMonitoring::Sp)
            ->where('id_monitoring', '<=', $this->id_monitoring)->count();
    }

    /** Lampiran Surat Peringatan. */
    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class, 'id_dokumen', 'id_dokumen');
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
