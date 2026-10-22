<?php

namespace App\Http\Requests\JobRequirement;

use App\Enums\Role;
use App\Enums\StatusReq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * R7: HR Client otomatis memakai kliennya (id_klien diabaikan); AM wajib memilih id_klien.
 */
class StoreJobRequirementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // HR Client: klien diambil otomatis. Tanpa user (mis. saat dokumentasi API dibuat) ditampilkan sebagai wajib (aturan AM).
            'id_klien' => ! $this->user()?->hasRole(Role::HrClient)
                ? ['required', 'integer', 'exists:klien,id_klien']
                : ['exclude'],
            'posisi' => ['required', 'string', 'max:255'],
            'jumlah_kebutuhan' => ['required', 'integer', 'min:1', 'max:100'],
            'kualifikasi' => ['nullable', 'string', 'max:5000'],
            'pengalaman_min' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'sla_deadline' => ['required', 'date', 'after:now'],
            'status_req' => ['sometimes', Rule::in([StatusReq::Draft->value, StatusReq::Diajukan->value])],
            'kompetensi' => ['required', 'array', 'min:1', 'max:30'],
            'kompetensi.*' => ['integer', 'distinct', 'exists:kompetensi,id_kompetensi'],
        ];
    }

    public function messages(): array
    {
        return JobRequirementRules::MESSAGES;
    }

    public function attributes(): array
    {
        return JobRequirementRules::ATTRIBUTES;
    }

    public function bodyParameters(): array
    {
        return [
            'id_klien' => ['description' => 'Wajib untuk Account Manager; diabaikan untuk HR Client.', 'example' => 1],
            'posisi' => ['example' => 'Backend Developer'],
            'jumlah_kebutuhan' => ['example' => 2],
            'kualifikasi' => ['example' => 'Menguasai Laravel dan REST API.'],
            'pengalaman_min' => ['description' => 'Pengalaman minimal dalam tahun (default 0).', 'example' => 2],
            'sla_deadline' => ['description' => 'Batas waktu pemenuhan, harus setelah sekarang.', 'example' => '2026-12-31 17:00:00'],
            'status_req' => ['description' => 'draft (default) atau diajukan.', 'example' => 'diajukan'],
            'kompetensi' => ['description' => 'Daftar id kompetensi yang dibutuhkan (minimal 1).', 'example' => [1, 12]],
            'kompetensi.*' => ['example' => 1],
        ];
    }
}
