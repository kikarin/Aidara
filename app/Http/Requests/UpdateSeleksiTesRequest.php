<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSeleksiTesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'skor_kecabangan_mentah' => 'nullable|numeric|min:1|max:5',
            'skor_fisik'             => 'nullable|numeric|min:0|max:100',
            'skor_akademik'          => 'nullable|numeric|min:0|max:100',
            'skor_psikologi'         => 'nullable|numeric|min:0|max:100',
            'psikologi_rekomendasi'  => 'nullable|boolean',
            'kesehatan_layak'        => 'nullable|boolean',
            'antropometri_layak'     => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'skor_kecabangan_mentah',
            'skor_fisik',
            'skor_akademik',
            'skor_psikologi',
            'psikologi_rekomendasi',
            'kesehatan_layak',
            'antropometri_layak',
        ];

        foreach ($nullable as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
