<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdatePesertaParameterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array|min:1',
            'data.*.peserta_id' => 'required|integer|exists:pemeriksaan_peserta,id',
            'data.*.status' => 'nullable|integer|exists:ref_status_pemeriksaan,id',
            'data.*.catatan' => 'nullable|string',
            'data.*.parameters' => 'required|array|min:1',
            'data.*.parameters.*.parameter_id' => 'required|integer|exists:pemeriksaan_parameter,id',
            'data.*.parameters.*.nilai' => 'nullable|string',
            'data.*.parameters.*.trend' => 'nullable|in:stabil,kenaikan,penurunan',
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'Data wajib diisi.',
            'data.array' => 'Data harus berupa array.',
            'data.min' => 'Minimal 1 data peserta harus diisi.',
            'data.*.peserta_id.required' => 'Peserta ID wajib diisi.',
            'data.*.peserta_id.exists' => 'Peserta tidak valid.',
            'data.*.status.exists' => 'Status pemeriksaan tidak valid.',
            'data.*.parameters.required' => 'Parameter wajib diisi.',
            'data.*.parameters.array' => 'Parameter harus berupa array.',
            'data.*.parameters.min' => 'Minimal 1 parameter harus diisi.',
            'data.*.parameters.*.parameter_id.required' => 'Parameter ID wajib diisi.',
            'data.*.parameters.*.parameter_id.exists' => 'Parameter tidak valid.',
            'data.*.parameters.*.trend.in' => 'Trend harus salah satu dari: stabil, kenaikan, penurunan.',
        ];
    }
}

