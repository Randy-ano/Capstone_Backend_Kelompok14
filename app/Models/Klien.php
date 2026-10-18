<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Klien extends Model
{
    use HasFactory;

    protected $table = 'klien';

    protected $primaryKey = 'id_klien';

    protected $fillable = [
        'nama_perusahaan',
        'alamat',
        'no_telepon',
        'penanggung_jawab',
    ];

    public function hrClients(): HasMany
    {
        return $this->hasMany(HrClient::class, 'id_klien', 'id_klien');
    }

    public function jobRequirements(): HasMany
    {
        return $this->hasMany(JobRequirement::class, 'id_klien', 'id_klien');
    }

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'id_klien', 'id_klien');
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(Dokumen::class, 'id_klien', 'id_klien');
    }
}
