<?php

namespace App\Services;

use App\Enums\JenisDokumen;
use App\Enums\Role;
use App\Enums\StatusDokumen;
use App\Enums\StatusTtd;
use App\Exceptions\DataTidakDitemukanException;
use App\Models\Dokumen;
use App\Models\Klien;
use App\Models\Placement;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * FR-13: dokumen (R25–R28). File di disk private, diunduh lewat endpoint terproteksi.
 * Tabel dokumen tidak punya kolom nama, jadi nama file asli disimpan di dalam file_path.
 */
class DokumenService
{
    public const DISK = 'local';

    public const RELASI = ['uploader.talent', 'uploader.hrClient', 'uploader.pegawai', 'klien', 'talent', 'tandaTangan.penandatangan.talent', 'tandaTangan.penandatangan.hrClient', 'tandaTangan.penandatangan.pegawai'];

    /** Jenis yang dibuat dari template (R26). */
    public const JENIS_TEMPLATE = [JenisDokumen::Quotation, JenisDokumen::PurchaseOrder, JenisDokumen::Pks];

    public function __construct(private readonly PemberitahuanService $pemberitahuan) {}

    /**
     * Internal: semua. HR Client: dokumen kliennya. Talent: dokumen miliknya.
     * Selain itu, setiap user melihat dokumen yang ia unggah atau harus ia tandatangani.
     */
    public static function scope(Builder $query, User $user): Builder
    {
        if ($user->isInternal()) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where('id_user_uploader', $user->id_user)
            ->orWhereHas('tandaTangan', fn (Builder $q) => $q->where('id_user_penandatangan', $user->id_user))
            ->when($user->hasRole(Role::HrClient), fn (Builder $q) => $q->orWhere('id_klien', $user->idKlien()))
            ->when($user->hasRole(Role::Talent), fn (Builder $q) => $q->orWhere('id_talent', $user->talent?->id_talent)));
    }

    public static function bolehLihat(User $user, Dokumen $dokumen): bool
    {
        return self::scope(Dokumen::query()->whereKey($dokumen->id_dokumen), $user)->exists();
    }

    /**
     * @param  array{jenis?: string|null, status?: string|null, search?: string|null, perlu_ttd?: bool|null, per_page?: int|null}  $filter
     */
    public function daftar(User $user, array $filter): LengthAwarePaginator
    {
        return self::scope(Dokumen::query(), $user)
            ->with(self::RELASI)
            ->when($filter['jenis'] ?? null, fn (Builder $q, string $jenis) => $q->where('jenis_dokumen', $jenis))
            ->when($filter['status'] ?? null, fn (Builder $q, string $status) => $q->where('status_dokumen', $status))
            ->when($filter['perlu_ttd'] ?? false, fn (Builder $q) => $q
                ->where('status_dokumen', StatusDokumen::MenungguTtd)
                ->whereHas('tandaTangan', fn (Builder $q) => $q->where('id_user_penandatangan', $user->id_user)->where('status_ttd', StatusTtd::Menunggu)))
            ->when($filter['search'] ?? null, fn (Builder $q, string $search) => $q->where(fn (Builder $q) => $q
                ->where('file_path', 'like', "%{$search}%")
                ->orWhereHas('klien', fn (Builder $q) => $q->where('nama_perusahaan', 'like', "%{$search}%"))
                ->orWhereHas('talent', fn (Builder $q) => $q->where('nama_lengkap', 'like', "%{$search}%"))))
            ->orderByDesc('tanggal_unggah')
            ->orderByDesc('id_dokumen')
            ->paginate($filter['per_page'] ?? 15)
            ->withQueryString();
    }

    /**
     * R25: unggah file. Placement (jika dikirim) menentukan talent & klien. HR Client selalu untuk kliennya,
     * talent selalu untuk dirinya; internal bebas memilih klien/talent.
     *
     * @param  array{file: UploadedFile, jenis_dokumen: string, id_klien?: int|null, id_talent?: int|null, id_placement?: int|null}  $data
     */
    public function unggah(User $user, array $data): Dokumen
    {
        [$idKlien, $idTalent, $idPlacement] = [$data['id_klien'] ?? null, $data['id_talent'] ?? null, $data['id_placement'] ?? null];

        if ($idPlacement) {
            $placement = Placement::findOrFail($idPlacement);
            $milik = match (true) {
                $user->hasRole(Role::HrClient) => $placement->id_klien === $user->idKlien(),
                $user->hasRole(Role::Talent) => $placement->id_talent === $user->talent?->id_talent,
                default => true,
            };
            if (! $milik) {
                throw new AccessDeniedHttpException('Anda tidak memiliki akses ke placement ini.');
            }
            [$idKlien, $idTalent] = [$placement->id_klien, $placement->id_talent];
        } elseif ($user->hasRole(Role::HrClient)) {
            [$idKlien, $idTalent] = [$user->idKlien(), null];
        } elseif ($user->hasRole(Role::Talent)) {
            [$idKlien, $idTalent] = [null, $user->talent->id_talent];
        }

        /** @var UploadedFile $file */
        $file = $data['file'];
        $nama = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'dokumen';
        $path = $file->storeAs('dokumen/'.Str::uuid(), $nama.'.'.strtolower($file->getClientOriginalExtension()), self::DISK);

        $dokumen = Dokumen::create([
            'id_user_uploader' => $user->id_user,
            'id_klien' => $idKlien,
            'id_talent' => $idTalent,
            'id_placement' => $idPlacement,
            'jenis_dokumen' => $data['jenis_dokumen'],
            'file_path' => $path,
            'tanggal_unggah' => now(),
        ]);

        // Dokumen unggahan talent: penandatangan otomatis talent itu sendiri, langsung menunggu tanda tangan.
        if ($user->hasRole(Role::Talent)) {
            $dokumen->tandaTangan()->create(['id_user_penandatangan' => $user->id_user]);
            $dokumen->update(['status_dokumen' => StatusDokumen::MenungguTtd]);
        }

        return $dokumen->load(self::RELASI);
    }

    /**
     * R26: quotation/PO/PKS dari template Blade → PDF, dengan data input + klien.
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(User $user, array $data): Dokumen
    {
        $jenis = JenisDokumen::from($data['jenis_dokumen']);
        $klien = Klien::findOrFail($data['id_klien']);
        $items = collect($data['items'] ?? [])->map(fn (array $i) => [...$i, 'subtotal' => $i['jumlah'] * $i['harga_satuan']]);

        $pdf = Pdf::loadView('pdf.dokumen-template', [
            'jenis' => $jenis,
            'judul' => self::judul($jenis),
            'klien' => $klien,
            'data' => $data,
            'items' => $items,
            'total' => $items->sum('subtotal'),
            'pembuat' => $user->namaTampilan(),
        ]);

        $path = 'dokumen/'.Str::uuid().'/'.Str::slug($jenis->value.' '.str_replace(['/', '\\'], '-', $data['nomor_dokumen'])).'.pdf';
        Storage::disk(self::DISK)->put($path, $pdf->output());

        $dokumen = Dokumen::create([
            'id_user_uploader' => $user->id_user,
            'id_klien' => $klien->id_klien,
            'jenis_dokumen' => $jenis,
            'file_path' => $path,
            'tanggal_unggah' => now(),
        ]);

        return $dokumen->load(self::RELASI);
    }

    /** Dokumen komersial: ditandatangani AM, Manager, dan HR Client klien dokumen. */
    public const JENIS_KOMERSIAL = [JenisDokumen::Quotation, JenisDokumen::PurchaseOrder, JenisDokumen::Pks];

    /** Dokumen kepegawaian: ditandatangani talent terkait, AM, dan Manager. */
    public const JENIS_KEPEGAWAIAN = [JenisDokumen::KontrakKerja, JenisDokumen::SuratPeringatan];

    /**
     * Calon penandatangan sesuai jenis dokumen (semua harus akun aktif):
     * - komersial (quotation, PO, PKS): AM, Manager, HR Client dari klien dokumen;
     * - kepegawaian (kontrak kerja, surat peringatan): talent terkait, AM, Manager;
     * - lainnya: AM, Manager, HR Client klien dokumen, dan talent terkait.
     * Talent Support tidak menandatangani dokumen. Dokumen unggahan talent: hanya talent itu sendiri.
     *
     * @return Collection<int, User>
     */
    public function calonPenandatangan(Dokumen $dokumen): Collection
    {
        $query = User::query()->with(['talent', 'hrClient.klien', 'pegawai'])->where('status_aktif', true);

        if ($dokumen->uploader?->hasRole(Role::Talent)) {
            return $query->whereKey($dokumen->id_user_uploader)->get();
        }

        $jenis = $dokumen->jenis_dokumen;
        $denganHr = ! in_array($jenis, self::JENIS_KEPEGAWAIAN, true);
        $denganTalent = ! in_array($jenis, self::JENIS_KOMERSIAL, true);

        return $query
            ->where(fn (Builder $q) => $q
                ->whereIn('role', [Role::AccountManager->value, Role::Manager->value])
                ->when($denganHr && $dokumen->id_klien, fn (Builder $q) => $q->orWhereHas('hrClient', fn (Builder $q) => $q->where('id_klien', $dokumen->id_klien)))
                ->when($denganTalent && $dokumen->id_talent, fn (Builder $q) => $q->orWhereHas('talent', fn (Builder $q) => $q->whereKey($dokumen->id_talent))))
            ->orderBy('role')
            ->orderBy('email')
            ->get();
    }

    /**
     * R27: pengunggah menentukan penandatangan → status menunggu_ttd.
     *
     * @param  list<int>  $idUser
     */
    public function aturPenandatangan(Dokumen $dokumen, array $idUser): Dokumen
    {
        if ($dokumen->status_dokumen !== StatusDokumen::Draft) {
            throw ValidationException::withMessages(['id_user' => 'Penandatangan hanya dapat ditentukan untuk dokumen berstatus draft.']);
        }

        $boleh = $this->calonPenandatangan($dokumen)->modelKeys();
        $tidakBoleh = array_diff($idUser, $boleh);
        if ($tidakBoleh !== []) {
            throw ValidationException::withMessages(['id_user' => 'Penandatangan tidak sesuai jenis dokumen. Pilih dari daftar calon penandatangan.']);
        }

        return DB::transaction(function () use ($dokumen, $idUser) {
            foreach ($idUser as $id) {
                $dokumen->tandaTangan()->create(['id_user_penandatangan' => $id]);
            }
            $dokumen->update(['status_dokumen' => StatusDokumen::MenungguTtd]);
            $this->pemberitahuan->mintaTandaTangan($dokumen, User::whereIn('id_user', $idUser)->get());

            return $dokumen->load(self::RELASI);
        });
    }

    /**
     * R28: penandatangan menandatangani (unggah gambar) atau menolak. Semua menandatangani → ditandatangani;
     * ada yang menolak → ditolak.
     */
    public function tandaTangan(User $user, Dokumen $dokumen, string $keputusan, ?UploadedFile $gambar): Dokumen
    {
        if ($dokumen->status_dokumen !== StatusDokumen::MenungguTtd) {
            throw ValidationException::withMessages(['keputusan' => 'Dokumen ini tidak sedang menunggu tanda tangan.']);
        }

        $ttd = $dokumen->tandaTangan()->where('id_user_penandatangan', $user->id_user)->firstOrFail();
        if ($ttd->status_ttd !== StatusTtd::Menunggu) {
            throw ValidationException::withMessages(['keputusan' => 'Anda sudah memberikan tanda tangan atau penolakan untuk dokumen ini.']);
        }

        return DB::transaction(function () use ($dokumen, $ttd, $keputusan, $gambar) {
            $ditandatangani = $keputusan === StatusTtd::Ditandatangani->value;
            $ttd->update([
                'status_ttd' => $keputusan,
                'tanggal_ttd' => now(),
                'file_tanda_tangan' => $ditandatangani
                    ? $gambar->storeAs("tanda-tangan/{$dokumen->id_dokumen}", "user-{$ttd->id_user_penandatangan}.".strtolower($gambar->getClientOriginalExtension() ?: 'png'), self::DISK)
                    : null,
            ]);

            $status = $dokumen->tandaTangan()->pluck('status_ttd');
            if ($status->contains(StatusTtd::Ditolak)) {
                $dokumen->update(['status_dokumen' => StatusDokumen::Ditolak]);
            } elseif ($status->every(fn (StatusTtd $s) => $s === StatusTtd::Ditandatangani)) {
                $dokumen->update(['status_dokumen' => StatusDokumen::Ditandatangani]);
            }

            return $dokumen->load(self::RELASI);
        });
    }

    public function download(Dokumen $dokumen): StreamedResponse
    {
        if (! Storage::disk(self::DISK)->exists($dokumen->file_path)) {
            throw new DataTidakDitemukanException('File dokumen tidak ditemukan.');
        }

        return Storage::disk(self::DISK)->download($dokumen->file_path, self::namaFile($dokumen));
    }

    public static function namaFile(Dokumen $dokumen): string
    {
        return basename($dokumen->file_path);
    }

    public static function judul(JenisDokumen $jenis): string
    {
        return match ($jenis) {
            JenisDokumen::Quotation => 'Quotation',
            JenisDokumen::PurchaseOrder => 'Purchase Order',
            JenisDokumen::Pks => 'Perjanjian Kerja Sama',
            JenisDokumen::KontrakKerja => 'Kontrak Kerja',
            JenisDokumen::SuratPeringatan => 'Surat Peringatan',
            JenisDokumen::Cv => 'CV',
            JenisDokumen::Lainnya => 'Lainnya',
        };
    }
}
