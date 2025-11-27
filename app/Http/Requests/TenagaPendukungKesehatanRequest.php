<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class TenagaPendukungKesehatanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tenaga_pendukung_id' => 'required|exists:tenaga_pendukungs,id',
            'tinggi_badan'        => 'nullable|numeric',
            'berat_badan'         => 'nullable|numeric',
            'penglihatan'         => 'nullable|string|max:255',
            'pendengaran'         => 'nullable|string|max:255',
            'riwayat_penyakit'    => 'nullable|string',
            'alergi'              => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:tenaga_pendukung_kesehatan,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tenaga_pendukung_id.required' => 'ID Tenaga Pendukung wajib diisi.',
            'tenaga_pendukung_id.exists'   => 'ID Tenaga Pendukung tidak valid.',
            'tinggi_badan.numeric'         => 'Tinggi badan harus berupa angka.',
            'berat_badan.numeric'          => 'Berat badan harus berupa angka.',
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

        Log::info('TenagaPendukungKesehatanRequest: Data after prepareForValidation', $this->all());
    }
}
