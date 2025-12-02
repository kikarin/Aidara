<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RencanaLatihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_latihan_id'     => 'required|exists:program_latihan,id',
            'tanggal'                => 'required|date',
            'lokasi_latihan'         => 'required|string|max:255',
            'materi'                 => 'required|string',
            'catatan'                => 'nullable|string',
            'target_latihan_ids'     => 'nullable|array',
            'target_latihan_ids.*'   => 'exists:target_latihan,id',
            'atlet_ids'              => 'required|array|min:1',
            'atlet_ids.*'            => 'exists:atlets,id',
            'pelatih_ids'            => 'required|array|min:1',
            'pelatih_ids.*'          => 'exists:pelatihs,id',
            'tenaga_pendukung_ids'   => 'nullable|array',
            'tenaga_pendukung_ids.*' => 'exists:tenaga_pendukungs,id',
        ];
    }

    public function messages(): array
    {
        return [
            'program_latihan_id.required'   => 'Program latihan wajib dipilih.',
            'program_latihan_id.exists'     => 'Program latihan tidak valid.',
            'tanggal.required'              => 'Tanggal wajib diisi.',
            'tanggal.date'                  => 'Tanggal tidak valid.',
            'lokasi_latihan.required'       => 'Lokasi latihan wajib diisi.',
            'materi.required'               => 'Materi wajib diisi.',
            'atlet_ids.required'            => 'Minimal 1 atlet harus dipilih.',
            'atlet_ids.array'               => 'Format atlet tidak valid.',
            'atlet_ids.*.exists'            => 'Atlet tidak valid.',
            'pelatih_ids.required'          => 'Minimal 1 pelatih harus dipilih.',
            'pelatih_ids.array'             => 'Format pelatih tidak valid.',
            'pelatih_ids.*.exists'          => 'Pelatih tidak valid.',
            'target_latihan_ids.*.exists'   => 'Target latihan tidak valid.',
            'tenaga_pendukung_ids.*.exists' => 'Tenaga pendukung tidak valid.',
        ];
    }
}
