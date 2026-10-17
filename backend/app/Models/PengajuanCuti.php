<?php

namespace App\Models;

use App\Enums\StatusApproval;
use App\Enums\StatusCuti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanCuti extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_cuti';

    protected $primaryKey = 'id_cuti';

    protected $fillable = [
        'id_talent',
        'id_placement',
        'id_approver_klien',
        'id_approver_manager',
        'status_approval_klien',
        'status_approval_manager',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan_cuti',
        'status_cuti',
    ];

    protected $attributes = [
        'status_approval_klien' => StatusApproval::Menunggu->value,
        'status_approval_manager' => StatusApproval::Menunggu->value,
        'status_cuti' => StatusCuti::Menunggu->value,
    ];

    protected function casts(): array
    {
        return [
            'status_approval_klien' => StatusApproval::class,
            'status_approval_manager' => StatusApproval::class,
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'status_cuti' => StatusCuti::class,
        ];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class, 'id_talent', 'id_talent');
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class, 'id_placement', 'id_placement');
    }

    public function approverKlien(): BelongsTo
    {
        return $this->belongsTo(HrClient::class, 'id_approver_klien', 'id_hr_client');
    }

    public function approverManager(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'id_approver_manager', 'id_pegawai');
    }
}
