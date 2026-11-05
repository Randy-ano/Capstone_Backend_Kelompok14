<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\DataTidakDitemukanException;
use App\Models\PengajuanKandidat;
use App\Models\SertifikatTalent;
use App\Models\Talent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sertifikat talent: daftar disinkronkan per id (file tidak hilang saat profil disimpan ulang), dan file sertifikat
 * di disk private yang hanya dapat dibuka lewat API.
 */
class SertifikatService
{
    public const DISK = 'local';

    /**
     * Sinkronkan daftar sertifikat dari form profil: baris ber-id diperbarui, baris tanpa id dibuat,
     * baris lama yang tidak dikirim dihapus beserta filenya. Validasi id milik talent ada di FormRequest.
     *
     * @param  list<array<string, mixed>>  $baris
     */
    public function sinkron(Talent $talent, array $baris): void
    {
        $lama = $talent->sertifikat()->get()->keyBy('id_sertifikat');
        $dipertahankan = [];

        foreach ($baris as $data) {
            $id = $data['id_sertifikat'] ?? null;
            $isi = collect($data)->except(['id_sertifikat', 'file_sertifikat'])->all();

            if ($id !== null && $lama->has($id)) {
                $lama[$id]->update($isi);
                $dipertahankan[] = (int) $id;
            } else {
                $talent->sertifikat()->create($isi);
            }
        }

        $lama->except($dipertahankan)->each(fn (SertifikatTalent $s) => $this->hapus($s));
    }

    /** Hapus baris sertifikat beserta filenya. */
    public function hapus(SertifikatTalent $sertifikat): void
    {
        $this->hapusFile($sertifikat->file_sertifikat);
        $sertifikat->delete();
    }

    /**
     * Unggah atau ganti file sertifikat (PDF/JPG/PNG, maks 2 MB). File lama dihapus dari storage.
     * Path: sertifikat/{id_talent}/{id_sertifikat}/{nama-file}.{ext}.
     */
    public function unggah(SertifikatTalent $sertifikat, UploadedFile $file): SertifikatTalent
    {
        $nama = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'sertifikat';
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'pdf');
        $lama = $sertifikat->file_sertifikat;

        $path = $file->storeAs("sertifikat/{$sertifikat->id_talent}/{$sertifikat->id_sertifikat}", "{$nama}.{$ext}", self::DISK);
        $sertifikat->update(['file_sertifikat' => $path]);

        if ($lama !== $path) {
            $this->hapusFile($lama);
        }

        return $sertifikat;
    }

    /** Buka file di browser (inline), agar PDF/gambar bisa langsung dipratinjau. */
    public function file(SertifikatTalent $sertifikat): StreamedResponse
    {
        $path = $sertifikat->file_sertifikat;
        if (! $path || ! Storage::disk(self::DISK)->exists($path)) {
            throw new DataTidakDitemukanException('File sertifikat belum diunggah.');
        }

        return Storage::disk(self::DISK)->response($path, basename($path), [], 'inline');
    }

    /**
     * Pemilik, Talent Support, Manager, Account Manager; HR Client hanya jika talent pernah diajukan ke kliennya.
     */
    public static function bolehLihat(User $user, SertifikatTalent $sertifikat): bool
    {
        return match ($user->role) {
            Role::Talent => $user->talent?->id_talent === $sertifikat->id_talent,
            Role::HrClient => PengajuanKandidat::query()
                ->where('id_talent', $sertifikat->id_talent)
                ->whereHas('jobRequirement', fn ($q) => $q->where('id_klien', $user->idKlien()))
                ->exists(),
            default => $user->isInternal(),
        };
    }

    public static function namaFile(?string $path): ?string
    {
        return $path ? basename($path) : null;
    }

    private function hapusFile(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
