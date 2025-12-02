<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'old_password' => 'required|string|min:1',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
                'not_in:password,123456,admin',
            ],
            'confirm_password' => 'required|same:new_password',
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
            'old_password.required'     => 'Password lama wajib diisi.',
            'old_password.string'       => 'Password lama harus berupa string.',
            'old_password.min'          => 'Password lama minimal 1 karakter.',
            'new_password.required'     => 'Password baru wajib diisi.',
            'new_password.string'       => 'Password baru harus berupa string.',
            'new_password.min'          => 'Password baru minimal 8 karakter.',
            'new_password.regex'        => 'Password baru harus mengandung huruf kecil, huruf besar, dan angka.',
            'new_password.not_in'       => 'Password baru tidak boleh menggunakan kata yang mudah ditebak.',
            'confirm_password.required' => 'Konfirmasi password wajib diisi.',
            'confirm_password.same'     => 'Konfirmasi password tidak cocok dengan password baru.',
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
            'old_password'     => 'password lama',
            'new_password'     => 'password baru',
            'confirm_password' => 'konfirmasi password',
        ];
    }
}
