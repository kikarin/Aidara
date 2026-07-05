<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_dokumen_id' => 'nullable|integer|exists:mst_jenis_dokumen,id',
            'nomor' => 'nullable|string|max:255',
            'file' => 'required|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_dokumen_id.exists' => 'Jenis dokumen tidak valid.',
            'file.required' => 'File dokumen wajib diisi.',
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

