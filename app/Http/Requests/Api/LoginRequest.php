<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email'       => 'required|email',
            'password'    => 'required|string|min:1',
            'device_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'email.required'     => 'Email wajib diisi.',
            'email.email'        => 'Format email tidak valid.',
            'password.required'  => 'Password wajib diisi.',
            'password.string'    => 'Password harus berupa string.',
            'password.min'       => 'Password minimal 1 karakter.',
            'device_name.string' => 'Nama device harus berupa string.',
            'device_name.max'    => 'Nama device maksimal 255 karakter.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'email'       => 'email',
            'password'    => 'password',
            'device_name' => 'nama device',
        ];
    }
}
