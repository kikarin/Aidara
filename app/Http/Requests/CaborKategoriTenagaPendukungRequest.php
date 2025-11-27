<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaborKategoriTenagaPendukungRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            // Untuk update, validasi jenis_tenaga_pendukung sebagai string
            $rules = [
                'jenis_tenaga_pendukung' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'                  => 'required|exists:cabor,id',
                'cabor_kategori_id'         => 'required|exists:cabor_kategori,id',
                'tenaga_pendukung_ids'      => 'required|array|min:1',
                'tenaga_pendukung_ids.*'    => 'required|exists:tenaga_pendukungs,id',
                'jenis_tenaga_pendukung'    => 'nullable|string|max:255',
                'is_active'                 => 'required|boolean',
            ];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cabor_id.required'                  => 'Cabor harus dipilih.',
            'cabor_id.exists'                    => 'Cabor yang dipilih tidak valid.',
            'cabor_kategori_id.required'         => 'Kategori harus dipilih.',
            'cabor_kategori_id.exists'           => 'Kategori yang dipilih tidak valid.',
            'tenaga_pendukung_ids.required'      => 'Tenaga pendukung harus dipilih minimal 1.',
            'tenaga_pendukung_ids.array'         => 'Tenaga pendukung harus berupa array.',
            'tenaga_pendukung_ids.min'           => 'Tenaga pendukung harus dipilih minimal 1.',
            'tenaga_pendukung_ids.*.required'    => 'Tenaga pendukung tidak boleh kosong.',
            'tenaga_pendukung_ids.*.exists'      => 'Tenaga pendukung yang dipilih tidak valid.',
            'jenis_tenaga_pendukung.string'      => 'Jenis tenaga pendukung harus berupa teks.',
            'jenis_tenaga_pendukung.max'         => 'Jenis tenaga pendukung tidak boleh lebih dari 255 karakter.',
        ];
    }
}
