<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PemeriksaanPesertaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'ref_status_pemeriksaan_id' => 'nullable|exists:ref_status_pemeriksaan,id',
            'catatan_umum'              => 'nullable|string',
        ];

        if ($this->isMethod('post')) {
            $rules['atlet_ids']              = 'nullable|array';
            $rules['atlet_ids.*']            = 'exists:atlets,id';
            $rules['pelatih_ids']            = 'nullable|array';
            $rules['pelatih_ids.*']          = 'exists:pelatihs,id';
            $rules['tenaga_pendukung_ids']   = 'nullable|array';
            $rules['tenaga_pendukung_ids.*'] = 'exists:tenaga_pendukungs,id';

            // Ensure at least one participant is selected
            $rules['atlet_ids'] = [
                'nullable',
                'array',
                Rule::requiredIf(
                    empty($this->pelatih_ids) && empty($this->tenaga_pendukung_ids)
                ),
            ];
        } else { // For PUT/PATCH to update a single participant
            $rules['peserta_type'] = ['required', Rule::in(['App\\Models\\Atlet', 'App\\Models\\Pelatih', 'App\\Models\\TenagaPendukung'])];
            $rules['peserta_id']   = 'required|integer';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'atlet_ids.required'                 => 'Minimal pilih satu peserta (atlet, pelatih, atau tenaga pendukung).',
        ];
    }
}
