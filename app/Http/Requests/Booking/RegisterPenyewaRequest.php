<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPenyewaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'no_hp' => ['required', 'string', 'max:32'],
            'nik' => ['nullable', 'string', 'max:32'],
            'alamat' => ['nullable', 'string'],
            'instansi' => ['nullable', 'string', 'max:150'],
            'kategori_default' => ['nullable', 'in:pemerintah,non_pemerintah'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
