<?php

namespace App\Http\Requests\Kompetensi;

use App\Models\Kompetensi;
use Closure;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Tambah kompetensi; dipakai juga sebagai dasar UpdateKompetensiRequest.
 */
class StoreKompetensiRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('create', Kompetensi::class);
    }

    protected function prepareForValidation(): void
    {
        // Spasi di awal/akhir dibuang agar "Laravel " dianggap sama dengan "Laravel".
        $this->merge(collect($this->only(['nama_kompetensi', 'kategori']))
            ->map(fn ($nilai) => is_string($nilai) ? (trim($nilai) === '' ? null : trim($nilai)) : $nilai)
            ->all());
    }

    public function rules(): array
    {
        return [
            'nama_kompetensi' => ['required', 'string', 'max:100', $this->namaUnik()],
            'kategori' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Nama unik tanpa membedakan huruf besar/kecil ("laravel" = "Laravel"), di MySQL maupun SQLite.
     */
    protected function namaUnik(?int $kecuali = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($kecuali) {
            $ada = Kompetensi::query()
                ->whereRaw('LOWER(nama_kompetensi) = ?', [mb_strtolower((string) $value)])
                ->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))
                ->exists();

            if ($ada) {
                $fail("Kompetensi {$value} sudah ada.");
            }
        };
    }

    public function attributes(): array
    {
        return [
            'nama_kompetensi' => 'nama kompetensi',
            'kategori' => 'kategori',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'nama_kompetensi' => ['description' => 'Unik, tidak membedakan huruf besar/kecil.', 'example' => 'Golang'],
            'kategori' => ['description' => 'Bebas; pilih dari meta.kategori agar konsisten.', 'example' => 'Backend'],
        ];
    }
}
