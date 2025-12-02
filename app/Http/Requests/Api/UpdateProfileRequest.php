<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = auth()->id();

        return [
            'name'  => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:200',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'no_hp'         => 'nullable|string|max:20',
            'tanggal_lahir' => 'nullable|date|before:today',
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
            'name.required'        => 'Nama wajib diisi.',
            'name.string'          => 'Nama harus berupa string.',
            'name.max'             => 'Nama maksimal 100 karakter.',
            'email.required'       => 'Email wajib diisi.',
            'email.email'          => 'Format email tidak valid.',
            'email.max'            => 'Email maksimal 200 karakter.',
            'email.unique'         => 'Email sudah digunakan oleh user lain.',
            'no_hp.string'         => 'No. HP harus berupa string.',
            'no_hp.max'            => 'No. HP maksimal 20 karakter.',
            'tanggal_lahir.date'   => 'Format tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh lebih dari hari ini.',
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
            'name'          => 'nama',
            'email'         => 'email',
            'no_hp'         => 'no. HP',
            'tanggal_lahir' => 'tanggal lahir',
        ];
    }
}
