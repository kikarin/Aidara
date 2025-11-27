<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersRequest extends FormRequest
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
            'name'  => 'required|max:100',
            'email' => 'required|max:200|email',
            'no_hp' => 'required|max:20',
            // PERBAIKAN: Ganti 'role' menjadi 'role_id' dan support array
            'role_id'   => 'required|array|min:1',
            'role_id.*' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            // PERBAIKAN: Email unique validation untuk update
            $rules['email'] = 'required|max:200|email|unique:users,email,'.$this->id;
        } else {
            // PERBAIKAN: Email unique validation untuk create
            $rules['email'] = 'required|max:200|email|unique:users,email';
        }

        if ($this->id == null || $this->password) {
            $rules['password'] = 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|not_in:password,123456,admin';
        }

        if ($this->hasFile('file')) {
            $rules['file'] = 'mimes:jpg,png,jpeg,webp,ico|max:2048';
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            // PERBAIKAN: Messages untuk multi-role
            'role_id.required'   => 'Role harus dipilih minimal 1.',
            'role_id.array'      => 'Role harus berupa array.',
            'role_id.min'        => 'Role harus dipilih minimal 1.',
            'role_id.*.required' => 'Role tidak boleh kosong.',
            'role_id.*.exists'   => 'Role yang dipilih tidak valid.',
        ];

        // Check if 'id' is null or 'password' is present
        if ($this->id == null || $this->password) {
            $messages['password.required'] = 'Password wajib diisi.';
            $messages['password.min']      = 'Password minimal 8 karakter.';
            $messages['password.regex']    = 'Password harus mengandung huruf kecil, huruf besar, dan angka.';
            $messages['password.not_in']   = 'Password tidak boleh menggunakan kata yang mudah ditebak.';
        }

        return $messages;
    }
}
