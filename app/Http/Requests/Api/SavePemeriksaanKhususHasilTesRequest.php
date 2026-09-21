<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SavePemeriksaanKhususHasilTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pemeriksaan_khusus_id' => 'required|exists:pemeriksaan_khusus,id',
            'data' => 'required|array|min:1',
            'data.*.peserta_id' => 'required|exists:pemeriksaan_khusus_peserta,id',
            'data.*.catatan' => 'nullable|string',
            'data.*.item_tes' => 'required|array|min:1',
            'data.*.item_tes.*.item_tes_id' => 'required|exists:pemeriksaan_khusus_item_tes,id',
            'data.*.item_tes.*.nilai' => 'nullable|string',
            'data.*.item_tes.*.catatan' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'pemeriksaan_khusus_id.required' => 'Pemeriksaan khusus wajib dipilih.',
            'pemeriksaan_khusus_id.exists' => 'Pemeriksaan khusus tidak valid.',
            'data.required' => 'Data hasil tes wajib diisi.',
            'data.array' => 'Data hasil tes harus berupa array.',
            'data.min' => 'Minimal harus ada 1 peserta.',
            'data.*.peserta_id.required' => 'Peserta wajib dipilih.',
            'data.*.peserta_id.exists' => 'Peserta tidak valid.',
            'data.*.item_tes.required' => 'Item tes wajib diisi.',
            'data.*.item_tes.array' => 'Item tes harus berupa array.',
            'data.*.item_tes.min' => 'Item tes wajib diisi minimal 1.',
            'data.*.item_tes.*.item_tes_id.required' => 'Item tes wajib dipilih.',
            'data.*.item_tes.*.item_tes_id.exists' => 'Item tes tidak valid.',
        ];
    }
}

