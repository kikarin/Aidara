<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StorePemeriksaanKhususRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cabor_id' => 'required|exists:cabor,id',
            'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
            'nama_pemeriksaan' => 'required|string|max:200',
            'tanggal_pemeriksaan' => 'required|date',
            'status' => 'sometimes|in:belum,sebagian,selesai',
        ];
    }

    public function messages(): array
    {
        return [
            'cabor_id.required' => 'Cabor wajib dipilih.',
            'cabor_id.exists' => 'Cabor tidak valid.',
            'cabor_kategori_id.required' => 'Kategori wajib dipilih.',
            'cabor_kategori_id.exists' => 'Kategori tidak valid.',
            'nama_pemeriksaan.required' => 'Nama pemeriksaan wajib diisi.',
            'nama_pemeriksaan.max' => 'Nama pemeriksaan maksimal 200 karakter.',
            'tanggal_pemeriksaan.required' => 'Tanggal pemeriksaan wajib diisi.',
            'tanggal_pemeriksaan.date' => 'Tanggal pemeriksaan harus berupa tanggal yang valid.',
            'status.in' => 'Status harus salah satu dari: belum, sebagian, selesai.',
        ];
    }
}

