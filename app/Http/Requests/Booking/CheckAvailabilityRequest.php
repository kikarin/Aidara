<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'exclude_booking_id' => ['nullable', 'integer', 'exists:bookings,id'],
        ];
    }
}
