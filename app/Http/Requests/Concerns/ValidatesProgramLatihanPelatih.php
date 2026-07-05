<?php

namespace App\Http\Requests\Concerns;

use App\Models\CaborKategoriPelatih;
use Illuminate\Validation\Validator;

trait ValidatesProgramLatihanPelatih
{
    protected function preparePelatihPayload(): void
    {
        $ids = $this->input('pelatih_ids');

        if ((empty($ids) || !is_array($ids)) && $this->filled('pelatih_id')) {
            $this->merge(['pelatih_ids' => [(int) $this->input('pelatih_id')]]);
        }

        if ($this->has('wajib_absen_atlet')) {
            $this->merge([
                'wajib_absen_atlet' => filter_var($this->input('wajib_absen_atlet'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        foreach (['absen_jam_mulai', 'absen_jam_selesai'] as $field) {
            $value = $this->input($field);
            if (is_string($value) && strlen($value) === 5) {
                $this->merge([$field => $value . ':00']);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function pelatihBaseRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return [
            'mode_pelatih' => $required . '|in:single,multiple',
            'pelatih_ids' => $required . '|array|min:1',
            'pelatih_ids.*' => 'exists:pelatihs,id',
            'absen_jam_mulai' => 'nullable|date_format:H:i:s',
            'absen_jam_selesai' => 'nullable|date_format:H:i:s',
        ];
    }

    protected function validatePelatihAssignment(Validator $validator, bool $partial = false): void
    {
        $validator->after(function (Validator $validator) use ($partial) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($partial && !$this->filled('pelatih_ids') && !$this->filled('mode_pelatih')) {
                return;
            }

            $mode = $this->input('mode_pelatih');
            $ids = $this->input('pelatih_ids', []);

            if ($mode === 'single' && count($ids) > 1) {
                $validator->errors()->add('pelatih_ids', 'Mode satu pelatih hanya boleh memilih satu pelatih.');

                return;
            }

            $caborId = $this->input('cabor_id');
            $kategoriId = $this->input('cabor_kategori_id');

            if (!$caborId || !$kategoriId) {
                return;
            }

            foreach ($ids as $pelatihId) {
                $exists = CaborKategoriPelatih::query()
                    ->where('pelatih_id', $pelatihId)
                    ->where('cabor_id', $caborId)
                    ->where('cabor_kategori_id', $kategoriId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$exists) {
                    $validator->errors()->add(
                        'pelatih_ids',
                        'Salah satu pelatih tidak terdaftar pada cabor dan kategori yang dipilih.'
                    );
                    break;
                }
            }

            $hasJamMulai = $this->filled('absen_jam_mulai');
            $hasJamSelesai = $this->filled('absen_jam_selesai');

            if ($hasJamMulai xor $hasJamSelesai) {
                $validator->errors()->add('absen_jam_mulai', 'Jam mulai dan jam selesai absen harus diisi keduanya.');
            }

            if ($hasJamMulai && $hasJamSelesai && $this->input('absen_jam_selesai') <= $this->input('absen_jam_mulai')) {
                $validator->errors()->add('absen_jam_selesai', 'Jam selesai absen harus setelah jam mulai.');
            }
        });
    }
}
