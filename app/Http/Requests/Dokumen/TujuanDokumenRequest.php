<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\JenisDokumen;
use App\Services\KelengkapanDokumenService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TujuanDokumenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'jenis' => ['required', Rule::in(array_map(fn (JenisDokumen $j) => $j->value, KelengkapanDokumenService::URUTAN))],
        ];
    }

    public function attributes(): array
    {
        return ['jenis' => 'jenis dokumen'];
    }

    public function queryParameters(): array
    {
        return [
            'jenis' => ['description' => 'quotation, purchase_order, pks, atau kontrak_kerja.', 'example' => 'quotation'],
        ];
    }
}
