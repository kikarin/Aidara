<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersMenuRequest extends FormRequest
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
            'nama' => 'required|string|max:200',
            'kode' => 'required|string|max:200|unique:users_menus,kode,'.($this->id ?? ''),
            'icon' => 'nullable|string',
            'rel'  => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value === 0) {
                        return true;
                    }
                    if (! \App\Models\UsersMenu::where('id', $value)->exists()) {
                        $fail('Menu parent tidak ditemukan');
                    }
                },
            ],
            'urutan'        => 'required|integer|min:1',
            'url'           => 'required|string',
            'permission_id' => 'required|integer|exists:permissions,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|integer|exists:users_menus,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required'          => 'Nama menu harus diisi',
            'nama.max'               => 'Nama menu maksimal 200 karakter',
            'kode.required'          => 'Kode menu harus diisi',
            'kode.unique'            => 'Kode menu sudah digunakan',
            'kode.max'               => 'Kode menu maksimal 200 karakter',
            'rel.integer'            => 'Menu parent harus berupa angka',
            'urutan.required'        => 'Urutan harus diisi',
            'urutan.min'             => 'Urutan minimal 1',
            'url.required'           => 'URL harus diisi',
            'permission_id.required' => 'Permission harus dipilih',
            'permission_id.exists'   => 'Permission tidak ditemukan',
        ];
    }
}
