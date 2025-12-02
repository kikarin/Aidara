<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaborKategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabor_id'          => 'required|exists:cabor,id',
            'nama'              => 'required|string|max:255',
            'deskripsi'         => 'nullable|string',
            'jenis_kelamin'     => 'required|in:L,P,C',
            'kategori_peserta_id' => 'nullable|exists:mst_kategori_peserta,id',
        ];
    }

    public function messages(): array
    {
        return [
            'cabor_id.required'      => 'Cabor wajib dipilih.',
            'cabor_id.exists'        => 'Cabor tidak valid.',
            'nama.required'          => 'Nama kategori wajib diisi.',
            'nama.string'            => 'Nama kategori harus berupa teks.',
            'nama.max'               => 'Nama kategori tidak boleh lebih dari 255 karakter.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in'       => 'Jenis kelamin harus L (Laki-laki), P (Perempuan), atau C (Campuran).',
            'kategori_peserta_id.exists' => 'Kategori peserta tidak valid.',
        ];
    }
}
