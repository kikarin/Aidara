<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramLatihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'cabor_id'          => 'required|exists:cabor,id',
            'nama_program'      => 'required|string|max:255',
            'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
            'periode_mulai'     => 'required|date',
            'periode_selesai'   => 'required|date|after_or_equal:periode_mulai',
            'tahap'             => 'nullable|in:persiapan umum,persiapan khusus,prapertandingan,pertandingan,transisi',
            'keterangan'        => 'nullable|string',
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'cabor_id.required'              => 'Cabor wajib dipilih.',
            'cabor_id.exists'                => 'Cabor tidak valid.',
            'nama_program.required'          => 'Nama program wajib diisi.',
            'cabor_kategori_id.required'     => 'Kategori wajib dipilih.',
            'cabor_kategori_id.exists'       => 'Kategori tidak valid.',
            'periode_mulai.required'         => 'Periode mulai wajib diisi.',
            'periode_mulai.date'             => 'Periode mulai harus berupa tanggal.',
            'periode_selesai.required'       => 'Periode selesai wajib diisi.',
            'periode_selesai.date'           => 'Periode selesai harus berupa tanggal.',
            'periode_selesai.after_or_equal' => 'Periode selesai harus setelah atau sama dengan periode mulai.',
            'tahap.in'                       => 'Tahap harus salah satu dari: persiapan umum, persiapan khusus, prapertandingan, pertandingan, transisi.',
        ];
    }
}
