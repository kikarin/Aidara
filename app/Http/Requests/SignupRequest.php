<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignupRequest extends FormRequest
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
            'persetujuan' => 'required',
        ];

        return $rules;
        // return [
        //     'role_id' => 'required',
        //     'name' => 'required|max:100',
        //     'email' => 'required|unique:users|email|max:150',
        //     'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin',
        //     'password_confirmation' => 'required',
        //     'terms' => 'required',
        // ];
    }

    public function messages()
    {
        return [
            'password.required'  => 'Password harus diisi.',
            'password.min'       => 'Password minimal harus terdiri dari 8 karakter.',
            'password.regex'     => 'Password harus mengandung setidaknya satu huruf kecil, satu huruf besar, dan satu angka.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ];
    }
}
