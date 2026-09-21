<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'tarif_id' => ['required', 'integer', 'exists:booking_tarifs,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
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
        ];
    }
}
