<?php

namespace App\Http\Requests\Dokumen;

use App\Enums\JenisDokumen;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * R26: quotation & PO berisi rincian item; PKS berisi ketentuan.
 */
class GenerateDokumenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'jenis_dokumen' => ['required', Rule::in([JenisDokumen::Quotation->value, JenisDokumen::PurchaseOrder->value, JenisDokumen::Pks->value])],
            'id_klien' => ['required', 'integer', 'exists:klien,id_klien'],
            'nomor_dokumen' => ['required', 'string', 'max:100'],
            'tanggal_dokumen' => ['required', 'date'],
            'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_dokumen'],
            'perihal' => ['required', 'string', 'max:255'],
            'items' => ['exclude_if:jenis_dokumen,pks', 'required', 'array', 'min:1', 'max:30'],
            'items.*.deskripsi' => ['required', 'string', 'max:255'],
            'items.*.jumlah' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.harga_satuan' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'ketentuan' => ['required_if:jenis_dokumen,pks', 'nullable', 'string', 'max:10000'],
            'catatan' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tambahkan minimal 1 item.',
            'items.min' => 'Tambahkan minimal 1 item.',
            'ketentuan.required_if' => 'Ketentuan wajib diisi untuk Perjanjian Kerja Sama.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berlaku tidak boleh sebelum tanggal dokumen.',
        ];
    }

    public function attributes(): array
    {
        return [
            'jenis_dokumen' => 'jenis dokumen',
            'id_klien' => 'klien',
            'nomor_dokumen' => 'nomor dokumen',
            'tanggal_dokumen' => 'tanggal dokumen',
            'berlaku_sampai' => 'tanggal berlaku',
            'items.*.deskripsi' => 'deskripsi item',
            'items.*.jumlah' => 'jumlah item',
            'items.*.harga_satuan' => 'harga satuan',
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'jenis_dokumen' => ['description' => 'quotation, purchase_order, atau pks.', 'example' => 'quotation'],
            'id_klien' => ['example' => 1],
            'nomor_dokumen' => ['example' => 'QUO/2026/10/001'],
            'tanggal_dokumen' => ['example' => '2026-10-22'],
            'berlaku_sampai' => ['example' => '2026-11-22'],
            'perihal' => ['example' => 'Penawaran 2 Backend Developer'],
            'items' => ['description' => 'Wajib untuk quotation & PO.', 'example' => [['deskripsi' => 'Backend Developer (per bulan)', 'jumlah' => 2, 'harga_satuan' => 15000000]]],
            'ketentuan' => ['description' => 'Wajib untuk PKS.', 'example' => null],
            'catatan' => ['example' => 'Harga belum termasuk PPN.'],
        ];
    }
}
