<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletKesehatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'atlet_id'         => 'required|exists:atlets,id',
            'golongan_darah'   => 'nullable|string|max:10',
            'tinggi_badan'     => 'nullable|numeric',
            'berat_badan'      => 'nullable|numeric',
            'penglihatan'      => 'nullable|string|max:255',
            'pendengaran'      => 'nullable|string|max:255',
            'riwayat_penyakit' => 'nullable|string',
            'alergi'           => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:atlet_kesehatan,id';
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
