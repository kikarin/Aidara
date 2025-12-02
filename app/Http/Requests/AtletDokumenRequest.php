<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'atlet_id'         => 'required|exists:atlets,id',
            'jenis_dokumen_id' => 'nullable|integer',
            'nomor'            => 'nullable|string|max:255',
            'file'             => 'nullable|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:atlet_dokumen,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'atlet_id.required' => 'ID Atlet wajib diisi.',
            'atlet_id.exists'   => 'ID Atlet tidak valid.',
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
