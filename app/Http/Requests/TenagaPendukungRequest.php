<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenagaPendukungRequest extends FormRequest
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
            'nik'               => 'required|string|size:16|unique:tenaga_pendukungs,nik,'.$this->id,
            'nama'              => 'required|string|max:200',
            'jenis_kelamin'     => 'required|in:L,P',
            'tempat_lahir'      => 'nullable|string|max:100',
            'tanggal_lahir'     => 'nullable|date',
            'tanggal_bergabung' => 'nullable|date',
            'alamat'            => 'nullable|string',
            'kecamatan_id'      => 'nullable|integer',
            'kelurahan_id'      => 'nullable|integer',
            'no_hp'             => 'nullable|string|max:20',
            'email'             => 'nullable|email|max:200',
            'is_active'         => 'nullable|boolean',
            'is_delete_foto'    => 'nullable|boolean',
        ];

        // Only validate file if it's present in the request
        if ($this->hasFile('file')) {
            $rules['file'] = 'mimes:jpg,png,jpeg,webp|max:2048';
        } else {
            $rules['file'] = 'nullable';
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert empty strings to null for optional fields
        $this->merge([
            'kecamatan_id' => $this->kecamatan_id && $this->kecamatan_id !== '' ? (int) $this->kecamatan_id : null,
            'kelurahan_id' => $this->kelurahan_id && $this->kelurahan_id !== '' ? (int) $this->kelurahan_id : null,
            'is_active'    => $this->is_active === '1' || $this->is_active === 1 || $this->is_active === true ? 1 : 0,
        ]);
    }
}
