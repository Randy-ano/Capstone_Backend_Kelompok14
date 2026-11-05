<?php

namespace App\Http\Resources;

use App\Enums\Role;
use App\Models\JobRequirement;
use App\Services\ProgresService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Permintaan kebutuhan untuk daftar, detail, monitoring, dan log progres.
 * Bagian opsional muncul sesuai relasi/hitungan yang dimuat service.
 *
 * @mixin JobRequirement
 */
class JobRequirementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $atribut = $this->resource->getAttributes();

        return [
            'id_job_req' => $this->id_job_req,
            'posisi' => $this->posisi,
            'klien' => [
                'id_klien' => $this->klien->id_klien,
                'nama_perusahaan' => $this->klien->nama_perusahaan,
            ],
            'account_manager' => $this->accountManager ? [
                'id_pegawai' => $this->accountManager->id_pegawai,
                'nama_pegawai' => $this->accountManager->nama_pegawai,
            ] : null,
            'jumlah_kebutuhan' => $this->jumlah_kebutuhan,
            'terpenuhi' => (int) ($atribut['terpenuhi'] ?? 0),
            'pengalaman_min' => $this->pengalaman_min,
            // Kisaran gaji (rupiah, opsional): untuk HR Client & internal; endpoint ini memang tidak terbuka bagi talent.
            'gaji_min' => $this->when(! $request->user()?->hasRole(Role::Talent), $this->gaji_min),
            'gaji_maks' => $this->when(! $request->user()?->hasRole(Role::Talent), $this->gaji_maks),
            'kualifikasi' => $this->kualifikasi,
            'status_req' => $this->status_req,
            'sla_deadline' => $this->sla_deadline?->toIso8601String(),
            'indikator_sla' => app(ProgresService::class)->indikatorSla($this->resource),
            'kompetensi' => KompetensiResource::collection($this->kompetensi),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'jumlah_screening' => $this->whenCounted('screenings'),
            'ringkasan_progres' => $this->when(array_key_exists('screening_pending', $atribut), fn () => [
                'jumlah_screening' => (int) $atribut['screenings_count'],
                'screening_pending' => (int) $atribut['screening_pending'],
                'screening_lolos' => (int) $atribut['screening_lolos'],
                'screening_tidak_lolos' => (int) $atribut['screening_tidak_lolos'],
                // Revisi 2026-11-05: usulan TS → AM.
                'usulan_menunggu_am' => (int) ($atribut['usulan_menunggu_am'] ?? 0),
                'usulan_diteruskan' => (int) ($atribut['usulan_diteruskan'] ?? 0),
                'usulan_tidak_diteruskan' => (int) ($atribut['usulan_tidak_diteruskan'] ?? 0),
                'terpenuhi' => (int) ($atribut['terpenuhi'] ?? 0),
                'sisa_kebutuhan' => max(0, $this->jumlah_kebutuhan - (int) ($atribut['terpenuhi'] ?? 0)),
            ]),
            'progres_terakhir' => new ProgresPemenuhanResource($this->whenLoaded('progresTerakhir')),
            'log_progres' => ProgresPemenuhanResource::collection($this->whenLoaded('progres')),
        ];
    }
}
