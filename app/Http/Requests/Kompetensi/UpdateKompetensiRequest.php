<?php

namespace App\Http\Requests\Kompetensi;

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

/**
 * Ubah sebagian: nama dan/atau kategori kompetensi.
 */
class UpdateKompetensiRequest extends StoreKompetensiRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('kompetensi'));
    }

    public function rules(): array
    {
        return [
            'nama_kompetensi' => ['sometimes', 'required', 'string', 'max:100', $this->namaUnik($this->route('kompetensi')->id_kompetensi)],
            'kategori' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['nama_kompetensi', 'kategori'])) {
                    $validator->errors()->add('nama_kompetensi', 'Kirim minimal satu kolom: nama kompetensi atau kategori.');
                }
            },
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'nama_kompetensi' => ['example' => 'Go'],
            'kategori' => ['description' => 'null untuk mengosongkan.', 'example' => 'Backend'],
        ];
    }
}
