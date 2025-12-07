<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemeriksaanKhususRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabor_id'            => 'required|exists:cabor,id',
            'cabor_kategori_id'   => 'required|exists:cabor_kategori,id',
            'nama_pemeriksaan'    => 'required|string|max:200',
            'tanggal_pemeriksaan' => 'required|date',
            'status'              => 'required|in:belum,sebagian,selesai',
        ];
    }

    public function messages(): array
    {
        return [
            'cabor_id.required'          => 'Cabor wajib dipilih.',
            'cabor_id.exists'            => 'Cabor tidak valid.',
            'cabor_kategori_id.required' => 'Kategori wajib dipilih.',
            'cabor_kategori_id.exists'   => 'Kategori tidak valid.',
            'nama_pemeriksaan.required'  => 'Nama pemeriksaan wajib diisi.',
            'tanggal_pemeriksaan.required' => 'Tanggal pemeriksaan wajib diisi.',
            'status.required'            => 'Status wajib dipilih.',
        ];
    }
}

