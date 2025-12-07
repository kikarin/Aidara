<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemeriksaanKhususAspekItemTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pemeriksaan_khusus_id' => 'required|exists:pemeriksaan_khusus,id',
            'aspek'                 => 'required|array|min:1',
            'aspek.*.nama'          => 'required|string|max:200',
            'aspek.*.urutan'        => 'nullable|integer',
            'aspek.*.item_tes'      => 'required|array|min:1',
            'aspek.*.item_tes.*.nama'             => 'required|string|max:200',
            'aspek.*.item_tes.*.satuan'           => 'nullable|string|max:50',
            'aspek.*.item_tes.*.target_laki_laki' => 'nullable|string',
            'aspek.*.item_tes.*.target_perempuan' => 'nullable|string',
            'aspek.*.item_tes.*.performa_arah'    => 'required|in:max,min',
            'aspek.*.item_tes.*.urutan'           => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'pemeriksaan_khusus_id.required' => 'Pemeriksaan khusus wajib dipilih.',
            'aspek.required'                 => 'Aspek wajib diisi minimal 1.',
            'aspek.array'                    => 'Aspek harus berupa array.',
            'aspek.min'                      => 'Aspek wajib diisi minimal 1.',
            'aspek.*.nama.required'          => 'Nama aspek wajib diisi.',
            'aspek.*.item_tes.required'      => 'Item tes wajib diisi minimal 1 untuk setiap aspek.',
            'aspek.*.item_tes.array'         => 'Item tes harus berupa array.',
            'aspek.*.item_tes.min'           => 'Item tes wajib diisi minimal 1 untuk setiap aspek.',
            'aspek.*.item_tes.*.nama.required'          => 'Nama item tes wajib diisi.',
            'aspek.*.item_tes.*.performa_arah.required' => 'Arah performa wajib dipilih.',
        ];
    }
}

