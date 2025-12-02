<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class PelatihKesehatanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'pelatih_id'       => 'required|exists:pelatihs,id',
            'tinggi_badan'     => 'nullable|numeric',
            'berat_badan'      => 'nullable|numeric',
            'penglihatan'      => 'nullable|string|max:255',
            'pendengaran'      => 'nullable|string|max:255',
            'riwayat_penyakit' => 'nullable|string',
            'alergi'           => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:pelatih_kesehatan,id';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pelatih_id.required'  => 'ID Pelatih wajib diisi.',
            'pelatih_id.exists'    => 'ID Pelatih tidak valid.',
            'tinggi_badan.numeric' => 'Tinggi badan harus berupa angka.',
            'berat_badan.numeric'  => 'Berat badan harus berupa angka.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
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

        Log::info('PelatihKesehatanRequest: Data after prepareForValidation', $this->all());
    }
}
