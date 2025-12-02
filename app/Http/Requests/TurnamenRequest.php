<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

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
        $rules = [
            'nama'              => 'required|string|max:255',
            'foto'              => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'cabor_kategori_id' => 'required|exists:cabor_kategori,id',
            'tanggal_mulai'     => 'required|date',
            'tanggal_selesai'   => 'required|date|after_or_equal:tanggal_mulai',
            'tingkat_id'        => 'required|exists:mst_tingkat,id',
            'lokasi'            => 'required|string|max:255',
            'juara_id'          => 'nullable|exists:mst_juara,id',
            'hasil'             => 'nullable|string',
            'evaluasi'          => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $turnamenId = $this->route('turnamen') ?? $this->route('id') ?? $this->input('id');

            if ($turnamenId) {
                $rules['nama'] = 'required|string|max:255|unique:turnamen,nama,'.$turnamenId;
            } else {
                $rules['nama'] = 'required|string|max:255';
            }
        } else {
            $rules['nama'] = 'required|string|max:255|unique:turnamen,nama';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        if ($this->has('tanggal_mulai') && $this->tanggal_mulai) {
            $this->merge([
                'tanggal_mulai' => Carbon::parse($this->tanggal_mulai)->format('Y-m-d'),
            ]);
        }

        if ($this->has('tanggal_selesai') && $this->tanggal_selesai) {
            $this->merge([
                'tanggal_selesai' => Carbon::parse($this->tanggal_selesai)->format('Y-m-d'),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'nama.required'                  => 'Nama turnamen wajib diisi.',
            'nama.string'                    => 'Nama turnamen harus berupa teks.',
            'nama.max'                       => 'Nama turnamen tidak boleh lebih dari 255 karakter.',
            'nama.unique'                    => 'Nama turnamen sudah ada.',
            'cabor_kategori_id.required'     => 'Cabor kategori wajib dipilih.',
            'cabor_kategori_id.exists'       => 'Cabor kategori yang dipilih tidak valid.',
            'tanggal_mulai.required'         => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.date'             => 'Tanggal mulai harus berupa tanggal yang valid.',
            'tanggal_selesai.required'       => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.date'           => 'Tanggal selesai harus berupa tanggal yang valid.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
            'tingkat_id.required'            => 'Tingkat wajib dipilih.',
            'tingkat_id.exists'              => 'Tingkat yang dipilih tidak valid.',
            'lokasi.required'                => 'Lokasi wajib diisi.',
            'lokasi.string'                  => 'Lokasi harus berupa teks.',
            'lokasi.max'                     => 'Lokasi tidak boleh lebih dari 255 karakter.',
            'juara_id.exists'                => 'Juara yang dipilih tidak valid.',
        ];
    }
}
