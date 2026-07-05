<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBiodataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Rules akan dinamis berdasarkan role, tapi kita buat yang umum dulu
        // Validasi spesifik akan di-handle di controller
        // Semua field nullable untuk allow partial update (hanya update field yang dikirim)
        $rules = [
            'nik' => 'nullable|string|max:30',
            'nama' => 'nullable|string|max:200',
            'jenis_kelamin' => 'nullable|in:L,P',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'tanggal_bergabung' => 'nullable|date',
            'alamat' => 'nullable|string',
            'kecamatan_id' => 'nullable|integer|exists:mst_kecamatan,id',
            'kelurahan_id' => 'nullable|integer|exists:mst_desa,id',
            'no_hp' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:200',
            'is_delete_foto' => 'nullable|boolean',
            
            // Field khusus untuk Atlet
            'nisn' => 'nullable|string|max:30',
            'agama' => 'nullable|string|max:50',
            'sekolah' => 'nullable|string',
            'kelas_sekolah' => 'nullable|string',
            'ukuran_baju' => 'nullable|string',
            'ukuran_celana' => 'nullable|string',
            'ukuran_sepatu' => 'nullable|string',
            'disabilitas' => 'nullable|string|max:255',
            'klasifikasi' => 'nullable|string|max:255',
            'iq' => 'nullable|string|max:50',
            
            // Field khusus untuk Pelatih
            'pekerjaan_selain_melatih' => 'nullable|string|max:200',
        ];

        // File upload untuk foto
        if ($this->hasFile('file')) {
            $rules['file'] = 'mimes:jpg,png,jpeg,webp|max:2048';
        } else {
            $rules['file'] = 'nullable';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nama.string' => 'Nama harus berupa teks.',
            'nama.max' => 'Nama maksimal 200 karakter.',
            'jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
            'email.email' => 'Format email tidak valid.',
            'kecamatan_id.exists' => 'Kecamatan tidak valid.',
            'kelurahan_id.exists' => 'Kelurahan tidak valid.',
            'file.mimes' => 'File harus berupa gambar (jpg, png, jpeg, webp).',
            'file.max' => 'Ukuran file maksimal 2MB.',
        ];
    }

    protected function prepareForValidation()
    {
        $nullIfEmpty = function ($value) {
            return ($value === '' || $value === null) ? null : $value;
        };

        $mergeData = [
            'kecamatan_id' => $this->kecamatan_id && $this->kecamatan_id !== '' ? (int) $this->kecamatan_id : null,
            'kelurahan_id' => $this->kelurahan_id && $this->kelurahan_id !== '' ? (int) $this->kelurahan_id : null,
            'nik' => $nullIfEmpty($this->nik),
            'tempat_lahir' => $nullIfEmpty($this->tempat_lahir),
            'tanggal_lahir' => $nullIfEmpty($this->tanggal_lahir),
            'tanggal_bergabung' => $nullIfEmpty($this->tanggal_bergabung),
            'alamat' => $nullIfEmpty($this->alamat),
            'no_hp' => $nullIfEmpty($this->no_hp),
            'email' => $nullIfEmpty($this->email),
            
            // Field khusus untuk Atlet
            'nisn' => $nullIfEmpty($this->nisn),
            'agama' => $nullIfEmpty($this->agama),
            'sekolah' => $nullIfEmpty($this->sekolah),
            'kelas_sekolah' => $nullIfEmpty($this->kelas_sekolah),
            'ukuran_baju' => $nullIfEmpty($this->ukuran_baju),
            'ukuran_celana' => $nullIfEmpty($this->ukuran_celana),
            'ukuran_sepatu' => $nullIfEmpty($this->ukuran_sepatu),
            'disabilitas' => $nullIfEmpty($this->disabilitas),
            'klasifikasi' => $nullIfEmpty($this->klasifikasi),
            'iq' => $nullIfEmpty($this->iq),
            
            // Field khusus untuk Pelatih
            'pekerjaan_selain_melatih' => $nullIfEmpty($this->pekerjaan_selain_melatih),
        ];

        $this->merge($mergeData);
    }
}

