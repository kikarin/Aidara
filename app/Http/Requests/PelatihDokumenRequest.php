<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PelatihDokumenRequest extends FormRequest
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
            'jenis_dokumen_id' => 'nullable|integer',
            'nomor'            => 'nullable|string|max:255',
            'file'             => 'nullable|mimes:jpg,png,jpeg,pdf,webp|max:4096',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:pelatih_dokumen,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pelatih_id.required'     => 'ID Pelatih wajib diisi.',
            'pelatih_id.exists'       => 'ID Pelatih tidak valid.',
            'jenis_dokumen_id.exists' => 'Jenis dokumen tidak valid.',
            'file.mimes'              => 'Format file tidak didukung. Gunakan JPG, PNG, JPEG, PDF, atau WEBP.',
            'file.max'                => 'Ukuran file maksimal 4MB.',
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
