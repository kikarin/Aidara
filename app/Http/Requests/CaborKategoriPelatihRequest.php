<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaborKategoriPelatihRequest extends FormRequest
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
            // Untuk update, validasi jenis_pelatih sebagai string
            $rules = [
                'jenis_pelatih' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'          => 'required|exists:cabor,id',
                'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
                'pelatih_ids'       => 'required|array|min:1',
                'pelatih_ids.*'     => 'required|exists:pelatihs,id',
                'jenis_pelatih'     => 'nullable|string|max:255',
                'is_active'         => 'required|boolean',
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
            'cabor_id.required'          => 'Cabor harus dipilih.',
            'cabor_id.exists'            => 'Cabor yang dipilih tidak valid.',
            'cabor_kategori_id.required' => 'Kategori harus dipilih.',
            'cabor_kategori_id.exists'   => 'Kategori yang dipilih tidak valid.',
            'pelatih_ids.required'       => 'Pelatih harus dipilih minimal 1.',
            'pelatih_ids.array'          => 'Pelatih harus berupa array.',
            'pelatih_ids.min'            => 'Pelatih harus dipilih minimal 1.',
            'pelatih_ids.*.required'     => 'Pelatih tidak boleh kosong.',
            'pelatih_ids.*.exists'       => 'Pelatih yang dipilih tidak valid.',
            'jenis_pelatih.string'       => 'Jenis pelatih harus berupa teks.',
            'jenis_pelatih.max'          => 'Jenis pelatih tidak boleh lebih dari 255 karakter.',
        ];
    }
}
