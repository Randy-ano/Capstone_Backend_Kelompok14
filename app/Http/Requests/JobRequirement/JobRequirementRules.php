<?php

namespace App\Http\Requests\JobRequirement;

use App\Services\KompetensiService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pesan, nama atribut, dan aturan kompetensi yang sama untuk buat dan ubah job requirement.
 */
final class JobRequirementRules
{
    public const MESSAGES = [
        'sla_deadline.after' => 'SLA deadline harus setelah waktu sekarang.',
        'kompetensi.required' => 'Pilih minimal 1 kompetensi.',
        'kompetensi.min' => 'Pilih minimal 1 kompetensi.',
    ];

    public const ATTRIBUTES = [
        'id_klien' => 'klien',
        'posisi' => 'posisi',
        'jumlah_kebutuhan' => 'jumlah kebutuhan',
        'kualifikasi' => 'kualifikasi',
        'pengalaman_min' => 'pengalaman minimal',
        'sla_deadline' => 'SLA deadline',
        'status_req' => 'status job requirement',
        'kompetensi' => 'kompetensi',
        'kompetensi.*' => 'kompetensi',
        'kompetensi_baru' => 'kompetensi baru',
        'kompetensi_baru.*' => 'kompetensi baru',
    ];

    /**
     * R7 + R33: minimal 1 kompetensi dari gabungan `kompetensi` (id master) dan `kompetensi_baru` (nama).
     */
    public static function kompetensi(Request $request): array
    {
        return [
            'kompetensi' => [Rule::requiredIf(fn () => blank($request->input('kompetensi_baru'))), 'array', 'max:30'],
            'kompetensi.*' => ['integer', 'distinct', 'exists:kompetensi,id_kompetensi'],
            'kompetensi_baru' => ['array', 'max:10'],
            'kompetensi_baru.*' => ['string', 'min:2', 'max:100', function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value) && KompetensiService::kunciNama($value) === '') {
                    $fail('Nama kompetensi baru harus berisi huruf atau angka.');
                }
            }],
        ];
    }

    /** Nama kompetensi baru di-trim dan spasi gandanya dirapikan sebelum divalidasi. */
    public static function rapikanKompetensiBaru(Request $request): void
    {
        if (is_array($request->input('kompetensi_baru'))) {
            $request->merge(['kompetensi_baru' => array_map(
                fn ($nama) => is_string($nama) ? KompetensiService::rapikanNama($nama) : $nama,
                $request->input('kompetensi_baru'),
            )]);
        }
    }
}
