<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRekapAbsenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $today = now()->format('Y-m-d');
        
        return [
            'program_latihan_id' => 'required|exists:program_latihan,id',
            'tanggal' => [
                'required',
                'date',
                'date_format:Y-m-d',
                Rule::in([$today]), // Hanya bisa input untuk tanggal hari ini
            ],
            'jenis_latihan' => 'required|in:latihan_fisik,latihan_strategi,latihan_teknik,latihan_mental,latihan_pemulihan',
            'keterangan' => 'nullable|string',
            'foto_absen.*' => 'nullable|image|mimes:jpeg,png,gif|max:5120',
            'file_nilai.*' => 'nullable|file|mimes:pdf,xls,xlsx|max:10240',
        ];
    }

    public function messages(): array
    {
        $today = now()->format('Y-m-d');
        
        return [
            'program_latihan_id.required' => 'Program latihan wajib dipilih.',
            'program_latihan_id.exists' => 'Program latihan tidak valid.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'tanggal.date_format' => 'Format tanggal harus YYYY-MM-DD.',
            'tanggal.in' => "Hanya dapat input rekap absen untuk tanggal hari ini ({$today}).",
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

