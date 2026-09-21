<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePenyewaProfileRequest extends FormRequest
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
            'nik' => ['nullable', 'string', 'max:32'],
            'no_hp' => ['nullable', 'string', 'max:32'],
            'alamat' => ['nullable', 'string'],
            'instansi' => ['nullable', 'string', 'max:150'],
            'kategori_default' => ['nullable', 'in:pemerintah,non_pemerintah'],
        ];
    }
}
