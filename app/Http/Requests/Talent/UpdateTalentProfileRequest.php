<?php

namespace App\Http\Requests\Talent;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Semua kolom opsional. Role talent dicek middleware route.
 * riwayat_pendidikan / riwayat_pengalaman_kerja, jika dikirim, menggantikan seluruh daftar lama.
 */
class UpdateTalentProfileRequest extends FormRequest
{
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

            // Riwayat pendidikan (R4: minimal 1 agar profil lengkap)
            'riwayat_pendidikan' => ['sometimes', 'array', 'max:20'],
            'riwayat_pendidikan.*.jenjang' => ['required', 'string', 'max:50'],
            'riwayat_pendidikan.*.nama_institusi' => ['required', 'string', 'max:255'],
            'riwayat_pendidikan.*.jurusan' => ['nullable', 'string', 'max:255'],
            'riwayat_pendidikan.*.tahun_mulai' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'riwayat_pendidikan.*.tahun_selesai' => ['nullable', 'integer', 'min:1900', 'max:'.(now()->year + 10)],
            'riwayat_pendidikan.*.deskripsi' => ['nullable', 'string', 'max:5000'],

            // Riwayat pengalaman kerja (dipakai R9 untuk lama pengalaman)
            'riwayat_pengalaman_kerja' => ['sometimes', 'array', 'max:30'],
            'riwayat_pengalaman_kerja.*.nama_perusahaan' => ['required', 'string', 'max:255'],
            'riwayat_pengalaman_kerja.*.posisi' => ['required', 'string', 'max:255'],
            'riwayat_pengalaman_kerja.*.jenis_pekerjaan' => ['nullable', 'string', 'max:50'],
            'riwayat_pengalaman_kerja.*.lokasi' => ['nullable', 'string', 'max:255'],
            'riwayat_pengalaman_kerja.*.tanggal_mulai' => ['required', 'date', 'before_or_equal:today'],
            'riwayat_pengalaman_kerja.*.masih_bekerja' => ['sometimes', 'boolean'],
            'riwayat_pengalaman_kerja.*.tanggal_selesai' => ['nullable', 'required_unless:riwayat_pengalaman_kerja.*.masih_bekerja,true', 'date', 'before_or_equal:today'],
            'riwayat_pengalaman_kerja.*.deskripsi' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * Cek urutan tahun/tanggal per baris (tidak bisa lewat rule biasa karena kolom pembanding boleh kosong).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('riwayat_pendidikan', []) as $i => $baris) {
                    $mulai = $baris['tahun_mulai'] ?? null;
                    $selesai = $baris['tahun_selesai'] ?? null;

                    if (is_numeric($mulai) && is_numeric($selesai) && $selesai < $mulai) {
                        $validator->errors()->add("riwayat_pendidikan.$i.tahun_selesai", 'Tahun selesai tidak boleh sebelum tahun mulai.');
                    }
                }

                foreach ($this->input('riwayat_pengalaman_kerja', []) as $i => $baris) {
                    $mulai = $baris['tanggal_mulai'] ?? null;
                    $selesai = $baris['tanggal_selesai'] ?? null;

                    if (! is_string($mulai) || ! is_string($selesai) || $validator->errors()->has("riwayat_pengalaman_kerja.$i.*")) {
                        continue;
                    }

                    if (strtotime($mulai) !== false && strtotime($selesai) !== false && Carbon::parse($selesai)->lt(Carbon::parse($mulai))) {
                        $validator->errors()->add("riwayat_pengalaman_kerja.$i.tanggal_selesai", 'Tanggal selesai tidak boleh sebelum tanggal mulai.');
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'riwayat_pendidikan.*.jenjang' => 'jenjang pendidikan',
            'riwayat_pendidikan.*.nama_institusi' => 'nama institusi',
            'riwayat_pendidikan.*.jurusan' => 'jurusan',
            'riwayat_pendidikan.*.tahun_mulai' => 'tahun mulai',
            'riwayat_pendidikan.*.tahun_selesai' => 'tahun selesai',
            'riwayat_pendidikan.*.deskripsi' => 'deskripsi pendidikan',
            'riwayat_pengalaman_kerja.*.nama_perusahaan' => 'nama perusahaan',
            'riwayat_pengalaman_kerja.*.posisi' => 'posisi',
            'riwayat_pengalaman_kerja.*.jenis_pekerjaan' => 'jenis pekerjaan',
            'riwayat_pengalaman_kerja.*.lokasi' => 'lokasi',
            'riwayat_pengalaman_kerja.*.tanggal_mulai' => 'tanggal mulai',
            'riwayat_pengalaman_kerja.*.masih_bekerja' => 'masih bekerja',
            'riwayat_pengalaman_kerja.*.tanggal_selesai' => 'tanggal selesai',
            'riwayat_pengalaman_kerja.*.deskripsi' => 'deskripsi pekerjaan',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'nama_lengkap' => ['example' => 'Budi Santoso'],
            'tanggal_lahir' => ['example' => '1999-05-12'],
            'nomor_telepon' => ['example' => '0812-3456-7890'],
            'domisili' => ['example' => 'Jakarta Selatan'],
            'tentang_saya' => ['example' => 'Backend developer yang fokus pada Laravel dan REST API.'],
            'gaji_harapan' => ['example' => 9000000],
            'linkedin' => ['example' => 'https://www.linkedin.com/in/budi-santoso'],
            'riwayat_pendidikan' => ['description' => 'Jika dikirim, menggantikan seluruh riwayat pendidikan lama. Minimal 1 agar profil lengkap (R4).'],
            'riwayat_pendidikan.*.jenjang' => ['example' => 'S1'],
            'riwayat_pendidikan.*.nama_institusi' => ['example' => 'Universitas Indonesia'],
            'riwayat_pendidikan.*.jurusan' => ['example' => 'Teknik Informatika'],
            'riwayat_pendidikan.*.tahun_mulai' => ['example' => 2017],
            'riwayat_pendidikan.*.tahun_selesai' => ['example' => 2021],
            'riwayat_pengalaman_kerja' => ['description' => 'Jika dikirim, menggantikan seluruh riwayat pengalaman kerja lama.'],
            'riwayat_pengalaman_kerja.*.nama_perusahaan' => ['example' => 'PT Niaga Digital'],
            'riwayat_pengalaman_kerja.*.posisi' => ['example' => 'Backend Developer'],
            'riwayat_pengalaman_kerja.*.tanggal_mulai' => ['example' => '2021-08-01'],
            'riwayat_pengalaman_kerja.*.masih_bekerja' => ['example' => true],
            'riwayat_pengalaman_kerja.*.tanggal_selesai' => ['description' => 'Wajib jika masih_bekerja bukan true.', 'example' => null],
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
