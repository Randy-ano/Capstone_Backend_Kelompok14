<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\JenisDokumen;
use App\Enums\StatusDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexDokumenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'jenis' => ['nullable', Rule::enum(JenisDokumen::class)],
            'status' => ['nullable', Rule::enum(StatusDokumen::class)],
            'perlu_ttd' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return ['jenis' => 'jenis dokumen', 'status' => 'status dokumen'];
    }

    public function queryParameters(): array
    {
        return [
            'jenis' => ['description' => 'quotation, purchase_order, pks, kontrak_kerja, surat_peringatan, cv, lainnya.', 'example' => 'quotation'],
            'status' => ['description' => 'draft, menunggu_ttd, ditandatangani, ditolak.', 'example' => 'menunggu_ttd'],
            'perlu_ttd' => ['description' => 'Jika 1, hanya dokumen yang menunggu tanda tangan user yang login.', 'example' => 1],
            'search' => ['description' => 'Cari nama file, klien, atau talent.', 'example' => 'quotation'],
            'per_page' => ['example' => 15],
            'page' => ['example' => 1],
        ];
    }
}
