<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenagaPendukungDokumenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tenaga_pendukung_id' => 'required|exists:tenaga_pendukungs,id',
            'jenis_dokumen_id'    => 'nullable|integer',
            'nomor'               => 'nullable|string|max:255',
            'file'                => 'nullable|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:tenaga_pendukung_dokumen,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tenaga_pendukung_id.required' => 'ID Tenaga Pendukung wajib diisi.',
            'tenaga_pendukung_id.exists'   => 'ID Tenaga Pendukung tidak valid.',
            'jenis_dokumen_id.exists'      => 'Jenis dokumen tidak valid.',
            'file.mimes'                   => 'Format file tidak didukung. Gunakan JPG, PNG, JPEG, PDF, atau WEBP.',
            'file.max'                     => 'Ukuran file maksimal 4MB.',
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
