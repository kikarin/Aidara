<?php

namespace App\Http\Requests;

use App\Rules\PesertaAvailableForUser;
use App\Services\UserPesertaLinkService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $roleIds = $this->input('role_id', []);
        if (!is_array($roleIds) || empty($roleIds)) {
            return;
        }

        $primaryRoleId = (int) $roleIds[0];
        $pesertaType   = UserPesertaLinkService::ROLE_TO_PESERTA_TYPE[$primaryRoleId] ?? null;

        if ($pesertaType) {
            $this->merge(['peserta_type' => $pesertaType]);
        }
    }

    private function requiresPesertaLink(): bool
    {
        $roleIds = $this->input('role_id', []);
        if (!is_array($roleIds) || empty($roleIds)) {
            return false;
        }

        return app(UserPesertaLinkService::class)->requiresPesertaLink($roleIds);
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
            'role_id'   => 'required|array|min:1',
            'role_id.*' => 'required|exists:roles,id',
            'is_active' => 'required|boolean',
        ];

        if ($this->requiresPesertaLink()) {
            $rules['peserta_id'] = [
                'required',
                'integer',
                new PesertaAvailableForUser(
                    $this->input('peserta_type'),
                    $this->isMethod('patch') || $this->isMethod('put') ? (int) $this->id : null
                ),
            ];
            $rules['peserta_type'] = [
                'required',
                Rule::in(array_keys(UserPesertaLinkService::PESERTA_TYPE_TO_MODEL)),
            ];
        } else {
            $rules['peserta_id']   = 'nullable|integer';
            $rules['peserta_type'] = 'nullable|string';
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
            $rules['email'] = 'required|max:200|email|unique:users,email,'.$this->id;
        } else {
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
            'role_id.required'   => 'Role harus dipilih minimal 1.',
            'role_id.array'      => 'Role harus berupa array.',
            'role_id.min'        => 'Role harus dipilih minimal 1.',
            'role_id.*.required' => 'Role tidak boleh kosong.',
            'role_id.*.exists'   => 'Role yang dipilih tidak valid.',
            'peserta_id.required' => 'Data peserta wajib dipilih untuk role Atlet, Pelatih, atau Tenaga Pendukung.',
        ];

        if ($this->id == null || $this->password) {
            $messages['password.required'] = 'Password wajib diisi.';
            $messages['password.min']      = 'Password minimal 8 karakter.';
            $messages['password.regex']    = 'Password harus mengandung huruf kecil, huruf besar, dan angka.';
            $messages['password.not_in']   = 'Password tidak boleh menggunakan kata yang mudah ditebak.';
        }

        return $messages;
    }
}
