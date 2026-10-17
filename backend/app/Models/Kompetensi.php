<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kompetensi extends Model
{
    use HasFactory;

    protected $table = 'kompetensi';

    protected $primaryKey = 'id_kompetensi';

    protected $fillable = [
        'nama_kompetensi',
        'kategori',
    ];

    public function talents(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class, 'talent_kompetensi', 'id_kompetensi', 'id_talent');
    }

    public function jobRequirements(): BelongsToMany
    {
        return $this->belongsToMany(JobRequirement::class, 'job_req_kompetensi', 'id_kompetensi', 'id_job_req');
    }
}
