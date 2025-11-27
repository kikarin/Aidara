<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class TurnamenRequest extends FormRequest
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
        return [
            'cabor_kategori_id'      => 'required|exists:cabor_kategori,id',
            'nama'                   => 'required|string|max:255',
            'tanggal_mulai'          => 'required|date',
            'tanggal_selesai'        => 'required|date|after_or_equal:tanggal_mulai',
            'tingkat_id'             => 'required|exists:mst_tingkat,id',
            'lokasi'                 => 'required|string|max:255',
            'juara_id'               => 'nullable|exists:mst_juara,id',
            'hasil'                  => 'nullable|string|max:500',
            'evaluasi'               => 'nullable|string',
            'atlet_ids'              => 'nullable|array',
            'atlet_ids.*'            => 'exists:atlets,id',
            'pelatih_ids'            => 'nullable|array',
            'pelatih_ids.*'          => 'exists:pelatihs,id',
            'tenaga_pendukung_ids'   => 'nullable|array',
            'tenaga_pendukung_ids.*' => 'exists:tenaga_pendukungs,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'cabor_kategori_id.required'     => 'Cabor kategori wajib diisi.',
            'cabor_kategori_id.exists'       => 'Cabor kategori tidak valid.',
            'nama.required'                  => 'Nama turnamen wajib diisi.',
            'nama.string'                    => 'Nama turnamen harus berupa string.',
            'nama.max'                       => 'Nama turnamen maksimal 255 karakter.',
            'tanggal_mulai.required'         => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date'             => 'Format tanggal mulai tidak valid.',
            'tanggal_selesai.required'       => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date'           => 'Format tanggal selesai tidak valid.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'tingkat_id.required'            => 'Tingkat wajib diisi.',
            'tingkat_id.exists'              => 'Tingkat tidak valid.',
            'lokasi.required'                => 'Lokasi wajib diisi.',
            'lokasi.string'                  => 'Lokasi harus berupa string.',
            'lokasi.max'                     => 'Lokasi maksimal 255 karakter.',
            'juara_id.exists'                => 'Juara tidak valid.',
            'hasil.string'                   => 'Hasil harus berupa string.',
            'hasil.max'                      => 'Hasil maksimal 500 karakter.',
            'evaluasi.string'                => 'Evaluasi harus berupa string.',
            'atlet_ids.array'                => 'Atlet harus berupa array.',
            'atlet_ids.*.exists'             => 'Salah satu atlet tidak valid.',
            'pelatih_ids.array'              => 'Pelatih harus berupa array.',
            'pelatih_ids.*.exists'           => 'Salah satu pelatih tidak valid.',
            'tenaga_pendukung_ids.array'     => 'Tenaga pendukung harus berupa array.',
            'tenaga_pendukung_ids.*.exists'  => 'Salah satu tenaga pendukung tidak valid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'cabor_kategori_id'    => 'cabor kategori',
            'nama'                 => 'nama turnamen',
            'tanggal_mulai'        => 'tanggal mulai',
            'tanggal_selesai'      => 'tanggal selesai',
            'tingkat_id'           => 'tingkat',
            'lokasi'               => 'lokasi',
            'juara_id'             => 'juara',
            'hasil'                => 'hasil',
            'evaluasi'             => 'evaluasi',
            'atlet_ids'            => 'atlet',
            'pelatih_ids'          => 'pelatih',
            'tenaga_pendukung_ids' => 'tenaga pendukung',
        ];
    }
}
