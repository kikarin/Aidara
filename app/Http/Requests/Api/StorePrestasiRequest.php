<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nama_event' => 'required|string|max:255',
            'tingkat_id' => 'nullable|integer|exists:mst_tingkat,id',
            'tanggal' => 'nullable|date',
            'peringkat' => 'nullable|string|max:255',
            'keterangan' => 'nullable|string',
            'bonus' => 'nullable|numeric|min:0',
        ];

        // Field khusus untuk Pelatih
        // Akan di-validate di controller berdasarkan role
        if ($this->has('kategori_prestasi_pelatih_id')) {
            $rules['kategori_prestasi_pelatih_id'] = 'nullable|integer|exists:mst_kategori_prestasi_pelatih,id';
        }

        if ($this->has('kategori_atlet_id')) {
            $rules['kategori_atlet_id'] = 'nullable|integer|exists:mst_kategori_atlet,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama_event.required' => 'Nama event wajib diisi.',
            'tingkat_id.exists' => 'Tingkat tidak valid.',
            'kategori_prestasi_pelatih_id.exists' => 'Kategori prestasi pelatih tidak valid.',
            'kategori_atlet_id.exists' => 'Kategori atlet tidak valid.',
            'bonus.numeric' => 'Bonus harus berupa angka.',
            'bonus.min' => 'Bonus tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));
    }
}

