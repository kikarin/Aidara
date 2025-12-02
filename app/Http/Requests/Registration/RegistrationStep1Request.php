<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationStep1Request extends FormRequest
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
        return [
            'peserta_type' => 'required|in:atlet,pelatih,tenaga_pendukung',
        ];
    }

    public function messages(): array
    {
        return [
            'peserta_type.required' => 'Jenis peserta wajib dipilih.',
            'peserta_type.in'       => 'Jenis peserta tidak valid.',
        ];
    }
}
