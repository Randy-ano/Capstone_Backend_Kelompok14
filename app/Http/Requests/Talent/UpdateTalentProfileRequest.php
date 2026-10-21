<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTalentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Hanya user yang memiliki profil talent yang boleh mengubahnya.
        return $this->user()?->talent !== null;
    }

    public function rules(): array
    {
        return [
            // Data pribadi
            'nama_lengkap' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'tanggal_lahir' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'nomor_telepon' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
            'alamat' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'domisili' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            // Informasi About Me
            'tentang_saya' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            // Data akademik utama
            'pendidikan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'nama_institusi' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'jurusan' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'tahun_lulus' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1900',
                'max:' . now()->year,
            ],

            // Ringkasan pengalaman kerja
            'pengalaman_kerja' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],

            // Gaji yang diharapkan
            'gaji_harapan' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],

            // Media sosial dan portofolio
            'linkedin' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            'instagram' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            'github' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
            'portfolio' => [
                'sometimes',
                'nullable',
                'url',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_lengkap.required' =>
                'Nama lengkap wajib diisi.',

            'tanggal_lahir.date' =>
                'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before_or_equal' =>
                'Tanggal lahir tidak boleh melebihi hari ini.',

            'nomor_telepon.max' =>
                'Nomor telepon maksimal 20 karakter.',

            'domisili.max' =>
                'Domisili maksimal 255 karakter.',

            'tentang_saya.max' =>
                'Bagian About Me maksimal 5000 karakter.',

            'tahun_lulus.integer' =>
                'Tahun lulus harus berupa angka.',
            'tahun_lulus.min' =>
                'Tahun lulus tidak valid.',
            'tahun_lulus.max' =>
                'Tahun lulus tidak boleh melebihi tahun ini.',

            'pengalaman_kerja.max' =>
                'Ringkasan pengalaman kerja maksimal 10000 karakter.',

            'gaji_harapan.numeric' =>
                'Gaji harapan harus berupa angka.',
            'gaji_harapan.min' =>
                'Gaji harapan tidak boleh bernilai negatif.',

            'linkedin.url' =>
                'Format URL LinkedIn tidak valid.',
            'instagram.url' =>
                'Format URL Instagram tidak valid.',
            'github.url' =>
                'Format URL GitHub tidak valid.',
            'portfolio.url' =>
                'Format URL portofolio tidak valid.',
        ];
    }
}
