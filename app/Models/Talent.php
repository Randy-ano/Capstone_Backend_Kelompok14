<?php

namespace App\Models;

use App\Enums\StatusKandidat;
use App\Enums\StatusKelengkapan;
use App\Models\RiwayatPendidikan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Talent extends Model
{
    use HasFactory;

    protected $table = 'talent';

    protected $primaryKey = 'id_talent';

    protected $fillable = [
        'id_user',

        // Data pribadi
        'nama_lengkap',
        'tanggal_lahir',
        'nomor_telepon',
        'alamat',
        'domisili',
        'tentang_saya',

        // Data akademik utama
        'pendidikan',
        'nama_institusi',
        'jurusan',
        'tahun_lulus',

        // Pengalaman kerja utama
        'pengalaman_kerja',
        'gaji_harapan',

        // Media sosial dan portofolio
        'linkedin',
        'instagram',
        'github',
        'portfolio',

        // Dokumen dan status
        'file_cv',
        'status_kelengkapan',
        'status_kandidat',
    ];

    protected $attributes = [
        'status_kelengkapan' => StatusKelengkapan::BelumLengkap->value,
        'status_kandidat' => StatusKandidat::Tersedia->value,
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tahun_lulus' => 'integer',
            'gaji_harapan' => 'decimal:2',
            'status_kelengkapan' => StatusKelengkapan::class,
            'status_kandidat' => StatusKandidat::class,
        ];
    }

    // Relasi ke akun pengguna
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id_user'
        );
    }

    // Relasi ke kompetensi atau skills
    public function kompetensi(): BelongsToMany
    {
        return $this->belongsToMany(
            Kompetensi::class,
            'talent_kompetensi',
            'id_talent',
            'id_kompetensi'
        );
    }

    // Relasi ke riwayat pendidikan
    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(
            RiwayatPendidikan::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi ke pengalaman kerja
    public function pengalamanKerjaTalent(): HasMany
    {
        return $this->hasMany(
            PengalamanKerjaTalent::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi ke sertifikat
    public function sertifikat(): HasMany
    {
        return $this->hasMany(
            SertifikatTalent::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi ke bahasa yang dikuasai
    public function bahasa(): BelongsToMany
    {
        return $this->belongsToMany(
            Bahasa::class,
            'talent_bahasa',
            'id_talent',
            'id_bahasa'
        )->withPivot('id_talent_bahasa', 'tingkat_kemampuan')
         ->withTimestamps();
    }

    // Relasi talent pool
    public function talentPool(): HasOne
    {
        return $this->hasOne(
            TalentPool::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi proses screening
    public function screenings(): HasMany
    {
        return $this->hasMany(
            ScreeningTalent::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi progres pemenuhan
    public function progres(): HasMany
    {
        return $this->hasMany(
            ProgresPemenuhan::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi pengajuan kandidat
    public function pengajuanKandidat(): HasMany
    {
        return $this->hasMany(
            PengajuanKandidat::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi penempatan
    public function placements(): HasMany
    {
        return $this->hasMany(
            Placement::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi pengajuan cuti
    public function cuti(): HasMany
    {
        return $this->hasMany(
            PengajuanCuti::class,
            'id_talent',
            'id_talent'
        );
    }

    // Relasi dokumen operasional dan CV
    public function dokumen(): HasMany
    {
        return $this->hasMany(
            Dokumen::class,
            'id_talent',
            'id_talent'
        );
    }
}
