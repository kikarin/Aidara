<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class AtletOrangTuaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Pastikan user terautentikasi dan memiliki izin yang sesuai jika diperlukan
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
            'atlet_id'          => 'required|exists:atlets,id',
            'nama_ibu_kandung'  => 'nullable|string|max:255',
            'tempat_lahir_ibu'  => 'nullable|string|max:255',
            'tanggal_lahir_ibu' => 'nullable|date',
            'alamat_ibu'        => 'nullable|string',
            'no_hp_ibu'         => 'nullable|string|max:20',
            'pekerjaan_ibu'     => 'nullable|string|max:255',

            'nama_ayah_kandung'  => 'nullable|string|max:255',
            'tempat_lahir_ayah'  => 'nullable|string|max:255',
            'tanggal_lahir_ayah' => 'nullable|date',
            'alamat_ayah'        => 'nullable|string',
            'no_hp_ayah'         => 'nullable|string|max:20',
            'pekerjaan_ayah'     => 'nullable|string|max:255',

            'nama_wali'          => 'nullable|string|max:255',
            'tempat_lahir_wali'  => 'nullable|string|max:255',
            'tanggal_lahir_wali' => 'nullable|date',
            'alamat_wali'        => 'nullable|string',
            'no_hp_wali'         => 'nullable|string|max:20',
            'pekerjaan_wali'     => 'nullable|string|max:255',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:atlet_orang_tua,id';
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'atlet_id.required' => 'ID Atlet wajib diisi.',
            'atlet_id.exists'   => 'ID Atlet tidak valid.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('atlet_id') && $this->route('atlet_id')) {
            $this->merge(['atlet_id' => $this->route('atlet_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }

        Log::info('AtletOrangTuaRequest: Data after prepareForValidation', $this->all());
    }
}
