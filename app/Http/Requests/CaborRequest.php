<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaborRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nama'      => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
        ];
        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['nama'] = 'required|string|max:255|unique:cabor,nama,'.$this->id;
        } else {
            $rules['nama'] = 'required|string|max:255|unique:cabor,nama';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama cabor wajib diisi.',
            'nama.string'   => 'Nama cabor harus berupa teks.',
            'nama.max'      => 'Nama cabor tidak boleh lebih dari 255 karakter.',
            'nama.unique'   => 'Nama cabor sudah ada.',
        ];
    }
}
