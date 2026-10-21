<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Exceptions\DataTidakDitemukanException;
use App\Enums\StatusKelengkapan;
use App\Models\Dokumen;
use App\Models\Talent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TalentCvService
{
    /** Disk private: CV hanya bisa diambil lewat endpoint terproteksi. */
    public const DISK = 'local';

    /**
     * R6: render PDF dari data profil, simpan di disk private,
     * isi talent.file_cv, dan catat sebagai dokumen jenis cv.
     * Generate ulang menimpa file dan record dokumen CV yang sama.
     */
    public function generate(Talent $talent): Talent
    {
        if ($talent->status_kelengkapan !== StatusKelengkapan::Lengkap) {
            throw ValidationException::withMessages([
                'profil' => 'Lengkapi profil terlebih dahulu (nama lengkap, riwayat pendidikan, dan kompetensi) sebelum membuat CV.',
            ]);
        }

        $talent->load(['user', ...TalentService::RELASI_PROFIL]);

        $pdf = Pdf::loadView('pdf.talent-cv', [
            'talent' => $talent,
        ]);

        $path = "cv/talent-{$talent->id_talent}.pdf";

        Storage::disk(self::DISK)->put($path, $pdf->output());

        DB::transaction(function () use ($talent, $path) {
            $talent->update([
                'file_cv' => $path,
            ]);

            Dokumen::updateOrCreate(
                ['id_talent' => $talent->id_talent, 'jenis_dokumen' => JenisDokumen::Cv],
                [
                    'id_user_uploader' => $talent->id_user,
                    'file_path' => $path,
                    'tanggal_unggah' => now(),
                ]
            );
        });

        return $talent->fresh()->load(['dokumen' => fn ($query) => $query->where('jenis_dokumen', JenisDokumen::Cv)]);
    }

    /**
     * Mengirim file CV talent dari disk private. 404 jika CV belum pernah dibuat
     * atau filenya hilang dari storage.
     */
    public function download(Talent $talent): StreamedResponse
    {
        if ($talent->file_cv === null || ! Storage::disk(self::DISK)->exists($talent->file_cv)) {
            throw new DataTidakDitemukanException('CV belum tersedia. Generate CV terlebih dahulu.');
        }

        $namaFile = 'CV-'.Str::slug($talent->nama_lengkap).'.pdf';

        return Storage::disk(self::DISK)->download($talent->file_cv, $namaFile, ['Content-Type' => 'application/pdf']);
    }
}
