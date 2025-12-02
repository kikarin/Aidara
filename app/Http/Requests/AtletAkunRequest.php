<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletAkunRequest extends FormRequest
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
            'email' => 'required|max:200|email',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            if ($this->users_id) {
                $rules['email'] = 'required|max:200|email|unique:users,email,'.$this->users_id;
            } else {
                $rules['email'] = 'required|max:200|email|unique:users,email';
            }
        } else {
            $rules['email'] = 'required|max:200|email|unique:users,email';
        }

        if ($this->isMethod('post') || $this->password) {
            $rules['password'] = 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $messages = [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'email.unique'      => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 8 karakter.',
            'password.regex'    => 'Password harus mengandung huruf kecil, huruf besar, dan angka.',
            'password.not_in'   => 'Password tidak boleh menggunakan kata yang mudah ditebak.',
        ];

        return $messages;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'email'    => $this->email ?: null,
            'password' => $this->password ?: null,
        ]);
    }
}
