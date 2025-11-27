<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenagaPendukungPrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tenaga_pendukung_id' => 'required|exists:tenaga_pendukungs,id',
            'nama_event'          => 'required|string|max:255',
            'tingkat_id'          => 'nullable|integer',
            'tanggal'             => 'nullable|date',
            'peringkat'           => 'nullable|string|max:255',
            'keterangan'          => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:tenaga_pendukung_prestasi,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tenaga_pendukung_id.required' => 'ID Tenaga Pendukung wajib diisi.',
            'tenaga_pendukung_id.exists'   => 'ID Tenaga Pendukung tidak valid.',
            'nama_event.required'          => 'Nama event wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('tenaga_pendukung_id') && $this->route('tenaga_pendukung_id')) {
            $this->merge(['tenaga_pendukung_id' => $this->route('tenaga_pendukung_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
