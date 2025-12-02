<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TargetLatihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'program_latihan_id' => 'required|exists:program_latihan,id',
            'jenis_target'       => 'required|in:individu,kelompok',
            'deskripsi'          => 'required|string|max:255',
            'satuan'             => 'nullable|string|max:100',
            'nilai_target'       => 'nullable|string|max:100',
            'performa_arah'      => 'nullable|in:min,max',
        ];

        // Peruntukan hanya required untuk target individu
        if (request('jenis_target') === 'individu') {
            $rules['peruntukan'] = 'required|in:atlet,pelatih,tenaga-pendukung';
        } else {
            $rules['peruntukan'] = 'nullable|in:atlet,pelatih,tenaga-pendukung';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'program_latihan_id.required' => 'Program latihan wajib dipilih.',
            'program_latihan_id.exists'   => 'Program latihan tidak valid.',
            'jenis_target.required'       => 'Jenis target wajib diisi.',
            'jenis_target.in'             => 'Jenis target tidak valid.',
            'peruntukan.required'         => 'Peruntukan wajib diisi.',
            'peruntukan.in'               => 'Peruntukan tidak valid.',
            'deskripsi.required'          => 'Deskripsi target wajib diisi.',
            'deskripsi.string'            => 'Deskripsi target harus berupa teks.',
            'deskripsi.max'               => 'Deskripsi target tidak boleh lebih dari 255 karakter.',
            'satuan.max'                  => 'Satuan tidak boleh lebih dari 100 karakter.',
            'nilai_target.max'            => 'Nilai target tidak boleh lebih dari 100 karakter.',
            'performa_arah.in'            => 'Arah performa harus min atau max.',
        ];
    }
}
