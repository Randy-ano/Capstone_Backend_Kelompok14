<?php

namespace App\Models;

use App\Enums\KanalNotifikasi;
use App\Enums\StatusPengiriman;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasi';

    protected $primaryKey = 'id_notifikasi';

    protected $fillable = [
        'id_user_penerima',
        'pesan',
        'tipe_notifikasi',
        'kanal',
        'jadwal_kirim',
        'status_pengiriman',
        'status_baca',
    ];

    protected $attributes = [
        'status_pengiriman' => StatusPengiriman::Menunggu->value,
        'status_baca' => false,
    ];

    protected function casts(): array
    {
        return [
            'kanal' => KanalNotifikasi::class,
            'jadwal_kirim' => 'datetime',
            'status_pengiriman' => StatusPengiriman::class,
            'status_baca' => 'boolean',
        ];
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user_penerima', 'id_user');
    }
}
