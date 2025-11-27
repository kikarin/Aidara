<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PelatihPrestasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'pelatih_id'                     => 'required|exists:pelatihs,id',
            'kategori_prestasi_pelatih_id'   => 'nullable|exists:mst_kategori_prestasi_pelatih,id',
            'kategori_atlet_id'              => 'nullable|exists:mst_kategori_atlet,id',
            'nama_event'                     => 'required|string|max:255',
            'tingkat_id'                     => 'nullable|integer',
            'tanggal'                        => 'nullable|date',
            'peringkat'                      => 'nullable|string|max:255',
            'keterangan'                     => 'nullable|string',
        ];

        if ($this->isMethod('patch') || $this->isMethod('put')) {
            $rules['id'] = 'required|exists:pelatih_prestasi,id';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'pelatih_id.required' => 'ID Pelatih wajib diisi.',
            'pelatih_id.exists'   => 'ID Pelatih tidak valid.',
            'nama_event.required' => 'Nama event wajib diisi.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(array_map(function ($value) {
            return $value === '' ? null : $value;
        }, $this->all()));

        if (! $this->has('pelatih_id') && $this->route('pelatih_id')) {
            $this->merge(['pelatih_id' => $this->route('pelatih_id')]);
        }

        if (($this->isMethod('patch') || $this->isMethod('put')) && $this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
