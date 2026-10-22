<?php

namespace App\Http\Resources;

use App\Enums\StatusDokumen;
use App\Enums\StatusTtd;
use App\Models\Dokumen;
use App\Models\TandaTanganDokumen;
use App\Models\User;
use App\Services\DokumenService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dokumen + daftar penandatangan. `aksi` menunjukkan apa yang boleh dilakukan user yang login.
 *
 * @mixin Dokumen
 */
class DokumenResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $milikSaya = $this->tandaTangan->firstWhere('id_user_penandatangan', $user?->id_user);

        return [
            'id_dokumen' => $this->id_dokumen,
            'nama_file' => DokumenService::namaFile($this->resource),
            'jenis_dokumen' => $this->jenis_dokumen,
            'status_dokumen' => $this->status_dokumen,
            'tanggal_unggah' => $this->tanggal_unggah?->toIso8601String(),
            'uploader' => $this->uploader ? self::user($this->uploader) : null,
            'klien' => $this->klien ? ['id_klien' => $this->klien->id_klien, 'nama_perusahaan' => $this->klien->nama_perusahaan] : null,
            'talent' => $this->talent ? ['id_talent' => $this->talent->id_talent, 'nama_lengkap' => $this->talent->nama_lengkap] : null,
            'id_placement' => $this->id_placement,
            'penandatangan' => $this->tandaTangan->map(fn (TandaTanganDokumen $t) => [
                'id_ttd' => $t->id_ttd,
                ...self::user($t->penandatangan),
                'status_ttd' => $t->status_ttd,
                'tanggal_ttd' => $t->tanggal_ttd?->toIso8601String(),
            ])->values(),
            'aksi' => [
                'boleh_atur_penandatangan' => $this->id_user_uploader === $user?->id_user && $this->status_dokumen === StatusDokumen::Draft,
                'perlu_tanda_tangan_saya' => $this->status_dokumen === StatusDokumen::MenungguTtd && $milikSaya?->status_ttd === StatusTtd::Menunggu,
            ],
        ];
    }

    /** @return array{id_user: int, nama: string, role: \App\Enums\Role} */
    public static function user(User $user): array
    {
        return ['id_user' => $user->id_user, 'nama' => $user->namaTampilan(), 'role' => $user->role];
    }
}
