<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'atlet_id'        => 'required|exists:atlets,id',
            'nama_sertifikat' => 'required|string|max:255',
            'penyelenggara'   => 'nullable|string|max:255',
            'tanggal_terbit'  => 'nullable|date',
            'file'            => 'nullable|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:atlet_sertifikat,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'atlet_id.required'        => 'ID Atlet wajib diisi.',
            'atlet_id.exists'          => 'ID Atlet tidak valid.',
            'nama_sertifikat.required' => 'Nama sertifikat wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('atlet_id') && $this->route('atlet_id')) {
            $this->merge(['atlet_id' => $this->route('atlet_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
