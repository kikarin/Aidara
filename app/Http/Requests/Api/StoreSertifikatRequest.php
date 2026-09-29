<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_sertifikat' => 'required|string|max:255',
            'penyelenggara' => 'nullable|string|max:255',
            'tanggal_terbit' => 'nullable|date',
            'file' => 'required|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_sertifikat.required' => 'Nama sertifikat wajib diisi.',
            'file.required' => 'File sertifikat wajib diisi.',
            'file.mimes' => 'File harus berupa gambar (jpg, png, jpeg, webp) atau PDF.',
            'file.max' => 'Ukuran file maksimal 4MB.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));
    }
}

