<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id_user';

    /**
     * Tabel users tidak punya kolom remember_token (sesuai ERD); auth memakai token Sanctum.
     */
    protected $rememberTokenName = '';

    protected $fillable = [
        'email',
        'password',
        'role',
        'status_aktif',
    ];

    protected $hidden = [
        'password',
    ];

    protected $attributes = [
        'status_aktif' => true,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
            'status_aktif' => 'boolean',
        ];
    }

    public function pegawai(): HasOne
    {
        return $this->hasOne(Pegawai::class, 'id_user', 'id_user');
    }

    public function hrClient(): HasOne
    {
        return $this->hasOne(HrClient::class, 'id_user', 'id_user');
    }

    public function talent(): HasOne
    {
        return $this->hasOne(Talent::class, 'id_user', 'id_user');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_user_penerima', 'id_user');
    }

    public function dokumenDiunggah(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'id_user_uploader', 'id_user');
    }

    public function tandaTangan(): HasMany
    {
        return $this->hasMany(TandaTanganDokumen::class, 'id_user_penandatangan', 'id_user');
    }

    public function shiftDikelola(): HasMany
    {
        return $this->hasMany(ShiftingTalent::class, 'id_pengelola', 'id_user');
    }
}
