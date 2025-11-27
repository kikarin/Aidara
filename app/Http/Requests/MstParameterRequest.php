<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MstParameterRequest extends FormRequest
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
            'nama'          => 'required|string|max:255',
            'satuan'        => 'required|string|max:255',
            'kategori'      => 'required|in:kesehatan,khusus,umum',
            'nilai_target'  => 'nullable|string|max:100',
            'performa_arah' => 'nullable|in:min,max',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['nama'] = 'required|string|max:255|unique:mst_parameter,nama,'.$this->id;
            // Hapus unique di satuan agar boleh duplikat
            $rules['satuan'] = 'required|string|max:255';
        } else {
            $rules['nama'] = 'required|string|max:255|unique:mst_parameter,nama';
            // Hapus unique di satuan agar boleh duplikat
            $rules['satuan'] = 'required|string|max:255';
        }

        // Validasi khusus untuk parameter khusus dan umum
        if ($this->kategori === 'khusus' || $this->kategori === 'umum') {
            $rules['nilai_target']  = 'required|string|max:100';
            $rules['performa_arah'] = 'required|in:min,max';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.required'          => 'Nama parameter wajib diisi.',
            'nama.string'            => 'Nama parameter harus berupa teks.',
            'nama.max'               => 'Nama parameter tidak boleh lebih dari 255 karakter.',
            'nama.unique'            => 'Nama parameter sudah ada.',
            'satuan.required'        => 'Satuan parameter wajib diisi.',
            'satuan.string'          => 'Satuan parameter harus berupa teks.',
            'satuan.max'             => 'Satuan parameter tidak boleh lebih dari 255 karakter.',
            'kategori.required'      => 'Kategori parameter wajib diisi.',
            'kategori.in'            => 'Kategori harus kesehatan, khusus, atau umum.',
            'nilai_target.required'  => 'Nilai target wajib diisi untuk parameter khusus/umum.',
            'nilai_target.max'       => 'Nilai target tidak boleh lebih dari 100 karakter.',
            'performa_arah.required' => 'Arah performa wajib diisi untuk parameter khusus/umum.',
            'performa_arah.in'       => 'Arah performa harus min atau max.',
        ];
    }
}
