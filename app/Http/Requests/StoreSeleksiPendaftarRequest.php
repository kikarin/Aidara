<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSeleksiPendaftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'periode_id'               => 'required|exists:seleksi_periode,id',
            'cabor_syarat_id'          => 'required|exists:seleksi_cabor_syarat,id',
            'nama'                     => 'required|string|max:255',
            'nik'                      => 'nullable|string|max:32',
            'nisn'                     => 'nullable|string|max:32',
            'jenis_kelamin'            => 'required|in:L,P',
            'tempat_lahir'             => 'nullable|string|max:255',
            'tanggal_lahir'            => 'required|date',
            'alamat'                   => 'nullable|string',
            'no_hp'                    => 'nullable|string|max:32',
            'email'                    => 'nullable|email|max:255',
            'sekolah'                  => 'required|string|max:255',
            'kelas_sekolah'            => 'nullable|string|max:50',
            'asal_kabupaten_bogor'     => 'required|boolean',
            'tinggi_badan'             => 'required|numeric|min:100|max:250',
            'berat_badan'              => 'nullable|numeric|min:20|max:200',
            'posisi'                   => 'nullable|string|max:100',
            'nomor_kelas'              => 'nullable|string|max:100',
            'vertical_jump'            => 'nullable|numeric|min:0|max:200',
            'bisa_dua_posisi'          => 'nullable|boolean',
            'bisa_berenang'            => 'nullable|boolean',
            'kuasai_poomsae'           => 'nullable|boolean',
            'bersedia_pindah_domisili' => 'nullable|boolean',
            'setuju_perjanjian'        => 'required|accepted',
            'berkas_kk'                => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_akta'              => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_bpjs'              => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_ijazah'            => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_surat_sehat'       => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_rekomendasi'       => 'required|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
            'berkas_piagam'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,webp|max:4096',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach ([
            'berat_badan',
            'vertical_jump',
            'nik',
            'nisn',
            'email',
            'posisi',
            'nomor_kelas',
        ] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        foreach ([
            'asal_kabupaten_bogor',
            'bisa_dua_posisi',
            'bisa_berenang',
            'kuasai_poomsae',
            'bersedia_pindah_domisili',
            'setuju_perjanjian',
        ] as $field) {
            $value = $this->input($field);
            if (in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true)) {
                $this->merge([$field => true]);
            } else {
                $this->merge([$field => false]);
            }
        }
    }

    public function messages(): array
    {
        return [
            'sekolah.required'            => 'Nama sekolah (SMP/MTs) wajib diisi.',
            'setuju_perjanjian.accepted'  => 'Calon atlet wajib menyetujui perjanjian PPOPM.',
            'berkas_kk.required'          => 'Kartu Keluarga wajib diunggah.',
            'berkas_akta.required'        => 'Akta kelahiran wajib diunggah.',
            'berkas_bpjs.required'        => 'BPJS Kesehatan wajib diunggah.',
            'berkas_ijazah.required'      => 'Ijazah terakhir wajib diunggah.',
            'berkas_surat_sehat.required' => 'Surat keterangan sehat wajib diunggah.',
            'berkas_rekomendasi.required' => 'Surat rekomendasi sekolah wajib diunggah.',
        ];
    }
}
