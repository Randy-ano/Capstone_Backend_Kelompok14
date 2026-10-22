<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\JenisDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * R25: pdf/doc/docx/png/jpg, maks 5 MB. CV tidak diunggah di sini (dibuat lewat generate CV talent).
 */
class UploadDokumenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:5120'],
            'jenis_dokumen' => ['required', Rule::in(array_values(array_diff(JenisDokumen::values(), [JenisDokumen::Cv->value])))],
            'id_klien' => ['nullable', 'integer', 'exists:klien,id_klien'],
            'id_talent' => ['nullable', 'integer', 'exists:talent,id_talent'],
            'id_placement' => ['nullable', 'integer', 'exists:placement,id_placement'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File harus berformat PDF, DOC, DOCX, PNG, atau JPG.',
            'file.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['jenis_dokumen' => 'jenis dokumen', 'id_klien' => 'klien', 'id_talent' => 'talent', 'id_placement' => 'placement'];
    }

    public function bodyParameters(): array
    {
        return [
            'file' => ['description' => 'pdf/doc/docx/png/jpg, maks 5 MB.'],
            'jenis_dokumen' => ['description' => 'Semua jenis kecuali cv.', 'example' => 'kontrak_kerja'],
            'id_klien' => ['description' => 'Untuk internal; HR Client otomatis kliennya.', 'example' => 1],
            'id_talent' => ['description' => 'Untuk internal; talent otomatis dirinya.', 'example' => null],
            'id_placement' => ['description' => 'Jika dikirim, talent & klien diambil dari placement.', 'example' => 1],
        ];
    }
}
