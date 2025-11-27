<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemeriksaanParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pemeriksaan_id'   => 'sometimes|exists:pemeriksaan,id',
            'mst_parameter_id' => 'required|exists:mst_parameter,id',
        ];
    }

    public function messages(): array
    {
        return [
            'pemeriksaan_id.required'   => 'Pemeriksaan wajib dipilih.',
            'pemeriksaan_id.exists'     => 'Pemeriksaan tidak valid.',
            'mst_parameter_id.required' => 'Parameter wajib dipilih.',
            'mst_parameter_id.exists'   => 'Parameter tidak valid.',
        ];
    }
}
