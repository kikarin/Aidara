<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MstJenisPelatihRequest extends FormRequest
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
            $rules['nama'] = 'required|string|max:255|unique:mst_jenis_pelatih,nama,'.$this->id;
        } else {
            $rules['nama'] = 'required|string|max:255|unique:mst_jenis_pelatih,nama';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama jenis pelatih wajib diisi.',
            'nama.string'   => 'Nama jenis pelatih harus berupa teks.',
            'nama.max'      => 'Nama jenis pelatih tidak boleh lebih dari 255 karakter.',
            'nama.unique'   => 'Nama jenis pelatih sudah ada.',
        ];
    }
}
