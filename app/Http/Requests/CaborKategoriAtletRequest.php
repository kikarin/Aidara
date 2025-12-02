<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaborKategoriAtletRequest extends FormRequest
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
            // Untuk update, validasi posisi_atlet sebagai string
            $rules = [
                'posisi_atlet' => 'nullable|string|max:255',
            ];
        } else {
            // Untuk create/store, validasi semua field
            $rules = [
                'cabor_id'          => 'required|exists:cabor,id',
                'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
                'atlet_ids'         => 'required|array|min:1',
                'atlet_ids.*'       => 'required|exists:atlets,id',
                'is_active'         => 'required|boolean',
                'posisi_atlet'      => 'nullable|string|max:255',
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
            'atlet_ids.required'         => 'Atlet harus dipilih minimal 1.',
            'atlet_ids.array'            => 'Atlet harus berupa array.',
            'atlet_ids.min'              => 'Atlet harus dipilih minimal 1.',
            'atlet_ids.*.required'       => 'Atlet tidak boleh kosong.',
            'atlet_ids.*.exists'         => 'Atlet yang dipilih tidak valid.',
            'posisi_atlet.string'        => 'Posisi atlet harus berupa teks.',
            'posisi_atlet.max'           => 'Posisi atlet tidak boleh lebih dari 255 karakter.',
        ];
    }
}
