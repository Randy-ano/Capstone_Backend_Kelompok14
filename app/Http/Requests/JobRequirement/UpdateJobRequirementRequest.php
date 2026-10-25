<?php

namespace App\Http\Requests\JobRequirement;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Ubah sebagian isi job requirement (hanya saat draft/diajukan, dicek di service). Klien tidak bisa diganti.
 */
class UpdateJobRequirementRequest extends FormRequest
{
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('jobRequirement'));
    }

    protected function prepareForValidation(): void
    {
        JobRequirementRules::rapikanKompetensiBaru($this);
    }

    public function rules(): array
    {
        $kompetensi = JobRequirementRules::kompetensi($this);

        return [
            'posisi' => ['sometimes', 'required', 'string', 'max:255'],
            'jumlah_kebutuhan' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'kualifikasi' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'pengalaman_min' => ['sometimes', 'required', 'integer', 'min:0', 'max:50'],
            'sla_deadline' => ['sometimes', 'required', 'date', 'after:now'],
            // kompetensi_baru hanya diproses bersama kompetensi; keduanya menggantikan daftar lama.
            'kompetensi' => ['sometimes', ...$kompetensi['kompetensi']],
            'kompetensi.*' => $kompetensi['kompetensi.*'],
            // (exclude_without tidak dipakai karena menganggap kompetensi [] sebagai tidak dikirim.)
            'kompetensi_baru' => $this->has('kompetensi') ? $kompetensi['kompetensi_baru'] : ['exclude'],
            'kompetensi_baru.*' => $this->has('kompetensi') ? $kompetensi['kompetensi_baru.*'] : ['exclude'],
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
            'posisi' => ['example' => 'Senior Backend Developer'],
            'jumlah_kebutuhan' => ['example' => 3],
            'kualifikasi' => ['example' => 'Menguasai Laravel, MySQL, dan Docker.'],
            'pengalaman_min' => ['example' => 3],
            'sla_deadline' => ['example' => '2027-01-15 17:00:00'],
            'kompetensi' => ['description' => 'Jika dikirim, menggantikan daftar kompetensi lama (bersama kompetensi_baru). Kirim [] jika hanya memakai kompetensi_baru.', 'example' => [1, 12, 15]],
            'kompetensi.*' => ['example' => 1],
            'kompetensi_baru' => ['description' => 'R33: nama kompetensi baru; hanya diproses jika kompetensi juga dikirim.', 'example' => ['Golang']],
            'kompetensi_baru.*' => ['example' => 'Golang'],
        ];
    }
}
