<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRekapAbsenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jenis_latihan' => 'nullable|in:latihan_fisik,latihan_strategi,latihan_teknik,latihan_mental,latihan_pemulihan',
            'keterangan' => 'nullable|string',
            'foto_absen' => 'nullable|array',
            'foto_absen.*' => 'nullable|image|mimes:jpeg,png,gif|max:5120',
            'file_nilai' => 'nullable|array',
            'file_nilai.*' => 'nullable|file|mimes:pdf,xls,xlsx|max:10240',
            'deleted_media_ids' => 'nullable|array',
            'deleted_media_ids.*' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'jenis_latihan.required' => 'Jenis latihan wajib dipilih.',
            'jenis_latihan.in' => 'Jenis latihan harus salah satu dari: latihan_fisik, latihan_strategi, latihan_teknik, latihan_mental, latihan_pemulihan.',
            'foto_absen.*.image' => 'Foto absen harus berupa gambar.',
            'foto_absen.*.mimes' => 'Foto absen harus berformat: jpeg, png, atau gif.',
            'foto_absen.*.max' => 'Foto absen maksimal 5MB.',
            'file_nilai.*.file' => 'File nilai harus berupa file.',
            'file_nilai.*.mimes' => 'File nilai harus berformat: pdf, xls, atau xlsx.',
            'file_nilai.*.max' => 'File nilai maksimal 10MB.',
        ];
    }
}

