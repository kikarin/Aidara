<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletRequest extends FormRequest
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
            'nik'               => 'nullable|string|size:16|unique:atlets,nik,'.$this->id,
            'nisn'              => 'nullable|string|max:30',
            'nama'              => 'required|string|max:200',
            'jenis_kelamin'     => 'required|in:L,P',
            'tempat_lahir'      => 'nullable|string|max:100',
            'agama'             => 'nullable|string|max:50',
            'tanggal_lahir'     => 'nullable|date',
            'tanggal_bergabung' => 'nullable|date',
            'alamat'            => 'nullable|string',
            'sekolah'           => 'nullable|string',
            'kelas_sekolah'     => 'nullable|string',
            'ukuran_baju'       => 'nullable|string',
            'ukuran_celana'     => 'nullable|string',
            'ukuran_sepatu'     => 'nullable|string',
            'kecamatan_id'      => 'nullable|integer',
            'kelurahan_id'      => 'nullable|integer',
            'no_hp'             => 'nullable|string|max:40',
            'email'             => 'nullable|email|max:200',
            'disabilitas'       => 'nullable|string|max:255',
            'klasifikasi'       => 'nullable|string|max:255',
            'iq'                => 'nullable|string|max:50',
            'is_active'         => 'nullable|boolean',
            'is_delete_foto'    => 'nullable|boolean',

            // Rules for AtletOrangTua
            'atlet_orang_tua_id' => 'nullable|integer',
            'nama_ibu_kandung'   => 'nullable|string|max:255',
            'tempat_lahir_ibu'   => 'nullable|string|max:255',
            'tanggal_lahir_ibu'  => 'nullable|date',
            'alamat_ibu'         => 'nullable|string',
            'no_hp_ibu'          => 'nullable|string|max:20',
            'pekerjaan_ibu'      => 'nullable|string|max:255',
            'nama_ayah_kandung'  => 'nullable|string|max:255',
            'tempat_lahir_ayah'  => 'nullable|string|max:255',
            'tanggal_lahir_ayah' => 'nullable|date',
            'alamat_ayah'        => 'nullable|string',
            'no_hp_ayah'         => 'nullable|string|max:20',
            'pekerjaan_ayah'     => 'nullable|string|max:255',
            'nama_wali'          => 'nullable|string|max:255',
            'tempat_lahir_wali'  => 'nullable|string|max:255',
            'tanggal_lahir_wali' => 'nullable|date',
            'alamat_wali'        => 'nullable|string',
            'no_hp_wali'         => 'nullable|string|max:20',
            'pekerjaan_wali'     => 'nullable|string|max:255',

            // Rules for Atlet Akun
            'users_id'      => 'nullable|integer|exists:users,id',
            'akun_email'    => 'nullable|email|max:200',
            'akun_password' => 'nullable|string|min:8',
        ];

        // Only validate file if it's present in the request
        if ($this->hasFile('file')) {
            $rules['file'] = 'mimes:jpg,png,jpeg,webp|max:2048';
        } else {
            $rules['file'] = 'nullable';
        }

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.max'      => 'NIK tidak boleh lebih dari 16 karakter.',
            'nik.unique'   => 'NIK sudah terdaftar.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Helper function untuk convert empty string ke null
        $nullIfEmpty = function ($value) {
            return ($value === '' || $value === null) ? null : $value;
        };

        // Convert empty strings to null for optional fields
        $mergeData = [
            'kecamatan_id'      => $this->kecamatan_id      && $this->kecamatan_id      !== '' ? (int) $this->kecamatan_id : null,
            'kelurahan_id'      => $this->kelurahan_id      && $this->kelurahan_id      !== '' ? (int) $this->kelurahan_id : null,
            'kategori_atlet_id' => $this->kategori_atlet_id && $this->kategori_atlet_id !== '' ? (int) $this->kategori_atlet_id : null,
            'is_active'         => $this->is_active === '1' || $this->is_active === 1 || $this->is_active === true ? 1 : 0,

            // Convert empty strings to null for nullable fields
            'nik'               => $nullIfEmpty($this->nik),
            'nisn'              => $nullIfEmpty($this->nisn),
            'tempat_lahir'      => $nullIfEmpty($this->tempat_lahir),
            'tanggal_lahir'     => $nullIfEmpty($this->tanggal_lahir),
            'tanggal_bergabung' => $nullIfEmpty($this->tanggal_bergabung),
            'agama'             => $nullIfEmpty($this->agama),
            'alamat'            => $nullIfEmpty($this->alamat),
            'sekolah'           => $nullIfEmpty($this->sekolah),
            'kelas_sekolah'     => $nullIfEmpty($this->kelas_sekolah),
            'ukuran_baju'       => $nullIfEmpty($this->ukuran_baju),
            'ukuran_celana'     => $nullIfEmpty($this->ukuran_celana),
            'ukuran_sepatu'     => $nullIfEmpty($this->ukuran_sepatu),
            'no_hp'             => $nullIfEmpty($this->no_hp),
            'email'             => $nullIfEmpty($this->email),
            'disabilitas'       => $nullIfEmpty($this->disabilitas),
            'klasifikasi'       => $nullIfEmpty($this->klasifikasi),
            'iq'                => $nullIfEmpty($this->iq),

            // Prepare AtletOrangTua fields - convert empty strings to null
            'nama_ibu_kandung'  => $nullIfEmpty($this->nama_ibu_kandung),
            'tempat_lahir_ibu'  => $nullIfEmpty($this->tempat_lahir_ibu),
            'tanggal_lahir_ibu' => $nullIfEmpty($this->tanggal_lahir_ibu),
            'alamat_ibu'        => $nullIfEmpty($this->alamat_ibu),
            'no_hp_ibu'         => $nullIfEmpty($this->no_hp_ibu),
            'pekerjaan_ibu'     => $nullIfEmpty($this->pekerjaan_ibu),

            'nama_ayah_kandung'  => $nullIfEmpty($this->nama_ayah_kandung),
            'tempat_lahir_ayah'  => $nullIfEmpty($this->tempat_lahir_ayah),
            'tanggal_lahir_ayah' => $nullIfEmpty($this->tanggal_lahir_ayah),
            'alamat_ayah'        => $nullIfEmpty($this->alamat_ayah),
            'no_hp_ayah'         => $nullIfEmpty($this->no_hp_ayah),
            'pekerjaan_ayah'     => $nullIfEmpty($this->pekerjaan_ayah),

            'nama_wali'          => $nullIfEmpty($this->nama_wali),
            'tempat_lahir_wali'  => $nullIfEmpty($this->tempat_lahir_wali),
            'tanggal_lahir_wali' => $nullIfEmpty($this->tanggal_lahir_wali),
            'alamat_wali'        => $nullIfEmpty($this->alamat_wali),
            'no_hp_wali'         => $nullIfEmpty($this->no_hp_wali),
            'pekerjaan_wali'     => $nullIfEmpty($this->pekerjaan_wali),

            // Prepare Atlet Akun fields
            'akun_email'    => $nullIfEmpty($this->akun_email),
            'akun_password' => $nullIfEmpty($this->akun_password),
        ];

        if ($this->exists('users_id')) {
            $mergeData['users_id'] = $this->users_id ?: null;
        }

        $this->merge($mergeData);
    }

}
