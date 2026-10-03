<?php

namespace App\Http\Requests\Booking;

use App\Services\Fonnte\FonnteService;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePenyewaProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('no_hp')) {
            $this->merge([
                'no_hp' => app(FonnteService::class)->normalizePhone($this->input('no_hp')),
            ]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nama'             => ['required', 'string', 'max:150'],
            'nik'              => ['nullable', 'string', 'max:32'],
            'no_hp'            => ['required', 'string', 'max:32', 'regex:/^62[0-9]{8,15}$/'],
            'alamat'           => ['nullable', 'string'],
            'instansi'         => ['nullable', 'string', 'max:150'],
            'kategori_default' => ['nullable', 'in:pemerintah,non_pemerintah'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'no_hp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
        ];
    }
}
