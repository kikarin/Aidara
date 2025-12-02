<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitPendukungRequest extends FormRequest
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
            'nama'                    => 'required|string|max:255',
            'jenis_unit_pendukung_id' => 'required|exists:mst_jenis_unit_pendukung,id',
            'deskripsi'               => 'nullable|string|max:1000',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['nama'] = 'required|string|max:255|unique:unit_pendukung,nama,'.$this->id;
        } else {
            $rules['nama'] = 'required|string|max:255|unique:unit_pendukung,nama';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required'                    => 'Nama unit pendukung wajib diisi.',
            'nama.string'                      => 'Nama unit pendukung harus berupa teks.',
            'nama.max'                         => 'Nama unit pendukung tidak boleh lebih dari 255 karakter.',
            'nama.unique'                      => 'Nama unit pendukung sudah ada.',
            'jenis_unit_pendukung_id.required' => 'Jenis unit pendukung wajib dipilih.',
            'jenis_unit_pendukung_id.exists'   => 'Jenis unit pendukung yang dipilih tidak valid.',
            'deskripsi.string'                 => 'Deskripsi harus berupa teks.',
            'deskripsi.max'                    => 'Deskripsi tidak boleh lebih dari 1000 karakter.',
        ];
    }
}
