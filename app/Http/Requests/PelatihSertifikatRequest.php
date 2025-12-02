<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PelatihSertifikatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'pelatih_id'      => 'required|exists:pelatihs,id',
            'nama_sertifikat' => 'required|string|max:255',
            'penyelenggara'   => 'nullable|string|max:255',
            'tanggal_terbit'  => 'nullable|date',
            'file'            => 'nullable|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:pelatih_sertifikat,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pelatih_id.required'      => 'ID Pelatih wajib diisi.',
            'pelatih_id.exists'        => 'ID Pelatih tidak valid.',
            'nama_sertifikat.required' => 'Nama sertifikat wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('pelatih_id') && $this->route('pelatih_id')) {
            $this->merge(['pelatih_id' => $this->route('pelatih_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
