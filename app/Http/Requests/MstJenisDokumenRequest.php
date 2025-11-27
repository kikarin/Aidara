<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MstJenisDokumenRequest extends FormRequest
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
        $rules = [
            'nama' => 'required|string|max:255',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['nama'] = 'required|string|max:255|unique:mst_jenis_dokumen,nama,'.$this->id;
        } else {
            $rules['nama'] = 'required|string|max:255|unique:mst_jenis_dokumen,nama';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jenis dokumen wajib diisi.',
            'nama.string'   => 'Nama jenis dokumen harus berupa teks.',
            'nama.max'      => 'Nama jenis dokumen tidak boleh lebih dari 255 karakter.',
            'nama.unique'   => 'Nama jenis dokumen sudah ada.',
        ];
    }
}
