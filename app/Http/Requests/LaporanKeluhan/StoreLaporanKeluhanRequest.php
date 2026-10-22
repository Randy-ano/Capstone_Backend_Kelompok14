<?php

namespace App\Http\Requests\LaporanKeluhan;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaporanKeluhanRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id_placement' => ['required', 'integer', 'exists:placement,id_placement'],
            'judul_laporan' => ['required', 'string', 'max:255'],
            'deskripsi_keluhan' => ['required', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['id_placement' => 'placement', 'judul_laporan' => 'judul laporan', 'deskripsi_keluhan' => 'deskripsi keluhan'];
    }

    public function bodyParameters(): array
    {
        return [
            'id_placement' => ['description' => 'Placement di klien HR Client yang login.', 'example' => 1],
            'judul_laporan' => ['example' => 'Sering terlambat hadir'],
            'deskripsi_keluhan' => ['example' => 'Dalam dua minggu terakhir talent terlambat 4 kali.'],
        ];
    }
}
