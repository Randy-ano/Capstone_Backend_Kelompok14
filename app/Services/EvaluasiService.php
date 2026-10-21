<?php

namespace App\Services;

use App\Enums\StatusEvaluasi;
use App\Enums\StatusInterview;
use App\Enums\StatusKandidat;
use App\Enums\StatusPengajuan;
use App\Models\EvaluasiKandidat;
use App\Models\PengajuanKandidat;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-12, R17: evaluasi kandidat oleh HR Client dari klien pemilik job, maksimal 1 per pengajuan,
 * setelah minimal 1 interview selesai. Ditolak → talent kembali tersedia.
 */
class EvaluasiService
{
    public const RELASI = ['hrClient', 'pengajuan.talent', 'pengajuan.jobRequirement.klien', 'placement'];

    /**
     * @param  array{id_pengajuan: int, nilai_score: int, catatan_evaluasi?: string|null, status_evaluasi: string}  $data
     */
    public function buat(User $user, array $data): EvaluasiKandidat
    {
        $pengajuan = PengajuanKandidat::with(['jobRequirement', 'evaluasi', 'talent'])->findOrFail($data['id_pengajuan']);

        if ($pengajuan->jobRequirement->id_klien !== $user->idKlien()) {
            throw new AccessDeniedHttpException('Anda tidak memiliki akses ke pengajuan ini.');
        }
        if ($pengajuan->evaluasi) {
            throw ValidationException::withMessages(['id_pengajuan' => 'Pengajuan ini sudah dievaluasi.']);
        }
        if ($pengajuan->status_pengajuan !== StatusPengajuan::LanjutInterview) {
            throw ValidationException::withMessages(['id_pengajuan' => 'Evaluasi hanya untuk pengajuan berstatus lanjut interview.']);
        }
        if (! $pengajuan->interviews()->where('status_interview', StatusInterview::Selesai)->exists()) {
            throw ValidationException::withMessages(['id_pengajuan' => 'Evaluasi dapat diisi setelah minimal 1 interview selesai.']);
        }
        PengajuanService::pastikanJobBerjalan($pengajuan->jobRequirement, 'id_pengajuan');

        return DB::transaction(function () use ($user, $data, $pengajuan) {
            $evaluasi = $pengajuan->evaluasi()->create([
                ...Arr::only($data, ['nilai_score', 'catatan_evaluasi', 'status_evaluasi']),
                'id_hr_client' => $user->hrClient->id_hr_client,
            ]);

            if ($evaluasi->status_evaluasi === StatusEvaluasi::Ditolak) {
                $pengajuan->talent->update(['status_kandidat' => StatusKandidat::Tersedia]);
            }

            return $evaluasi->load(self::RELASI);
        });
    }

    public function detail(EvaluasiKandidat $evaluasi): EvaluasiKandidat
    {
        return $evaluasi->load(self::RELASI);
    }
}
