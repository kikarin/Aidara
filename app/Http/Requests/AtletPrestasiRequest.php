<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtletPrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'atlet_id'          => 'required|exists:atlets,id',
            'jenis_prestasi'    => 'required|in:individu,ganda/mixed/beregu/double',
            'juara'             => 'nullable|string|max:255',
            'medali'            => 'nullable|in:Emas,Perak,Perunggu',
            'nama_event'        => 'required|string|max:255',
            'tingkat_id'        => 'nullable|integer',
            'tanggal'           => 'nullable|date',
            'keterangan'        => 'nullable|string',
            'bonus'             => 'nullable|numeric|min:0',
            'anggota_beregu'    => 'nullable|array',
            'anggota_beregu.*'  => 'required|integer|exists:atlets,id',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:atlet_prestasi,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'atlet_id.required'   => 'ID Atlet wajib diisi.',
            'atlet_id.exists'     => 'ID Atlet tidak valid.',
            'nama_event.required' => 'Nama event wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('atlet_id') && $this->route('atlet_id')) {
            $this->merge(['atlet_id' => $this->route('atlet_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
