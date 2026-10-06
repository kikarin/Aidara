<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        // Klien API lama (mobile) tidak mengirim centang tata tertib — cukup wajib untuk web (Inertia).
        if (! $this->inertia()) {
            $this->merge(['tata_tertib_accepted' => true]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tarif_id' => ['nullable', 'integer', 'exists:booking_tarifs,id', 'required_without:areas'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'areas' => ['nullable', 'array', 'required_without:tarif_id'],
            'areas.*.area_id' => ['required', 'integer', 'exists:booking_areas,id'],
            'areas.*.tarif_id' => ['required', 'integer', 'exists:booking_tarifs,id'],
            'areas.*.qty' => ['nullable', 'integer', 'min:1'],
            'areas.*.luas_m2' => ['nullable', 'numeric', 'min:0.01'],
            'kategori_tarif' => ['required', 'in:pemerintah,non_pemerintah'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'luas_m2' => ['nullable', 'numeric', 'min:0.01'],
            'duration_value' => ['nullable', 'integer', 'min:1'],
            'addon_ids' => ['nullable', 'array'],
            'tujuan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'terms_accepted' => ['required', 'accepted'],
            'tata_tertib_accepted' => ['required', 'accepted'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'starts_at.after_or_equal' => 'Waktu mulai harus sekarang atau setelahnya. Pilih jam yang belum lewat.',
            'ends_at.after' => 'Waktu selesai harus setelah waktu mulai.',
            'terms_accepted.accepted' => 'Anda harus menyetujui syarat sewa.',
            'tata_tertib_accepted.accepted' => 'Anda harus menyatakan sudah membaca tata tertib.',
            'tujuan.required' => 'Tujuan sewa wajib diisi.',
            'tarif_id.required_without' => 'Pilih jenis sewa terlebih dahulu.',
            'areas.required_without' => 'Pilih minimal satu area yang akan disewa.',
            'areas.*.area_id.required' => 'Setiap baris wajib memilih area.',
            'areas.*.tarif_id.required' => 'Setiap area wajib memilih jenis sewa.',
            'kategori_tarif.in' => 'Kategori tarif tidak valid.',
        ];
    }
}
