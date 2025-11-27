<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemeriksaanPesertaParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pemeriksaan_id'           => 'required|exists:pemeriksaan,id',
            'pemeriksaan_peserta_id'   => 'required|exists:pemeriksaan_peserta,id',
            'pemeriksaan_parameter_id' => 'required|exists:pemeriksaan_parameter,id',
            'nilai'                    => 'required|string',
            'trend'                    => 'required|in:stabil,penurunan,kenaikan',
        ];
    }

    public function messages(): array
    {
        return [
            'pemeriksaan_id.required'           => 'Pemeriksaan wajib diisi.',
            'pemeriksaan_id.exists'             => 'Pemeriksaan tidak valid.',
            'pemeriksaan_peserta_id.required'   => 'Peserta wajib diisi.',
            'pemeriksaan_peserta_id.exists'     => 'Peserta tidak valid.',
            'pemeriksaan_parameter_id.required' => 'Parameter wajib diisi.',
            'pemeriksaan_parameter_id.exists'   => 'Parameter tidak valid.',
            'nilai.required'                    => 'Nilai wajib diisi.',
            'nilai.string'                      => 'Nilai harus berupa teks.',
            'trend.required'                    => 'Trend wajib dipilih.',
            'trend.in'                          => 'Trend tidak valid.',
        ];
    }
}
