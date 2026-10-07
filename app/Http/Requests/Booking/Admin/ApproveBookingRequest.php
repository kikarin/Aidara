<?php

namespace App\Http\Requests\Booking\Admin;

use App\Models\Booking\Booking;
use App\Support\Booking\BookingJenisSewa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $booking = $this->route('id')
            ? Booking::query()->with('items')->find($this->route('id'))
            : null;

        $butuhSurat = $booking !== null && BookingJenisSewa::isPerHari($booking);

        return [
            'priority_rule_id' => ['nullable', 'integer', 'exists:booking_priority_rules,id'],
            'admin_notes'      => ['nullable', 'string'],
            'note'             => ['nullable', 'string'],
            'force'            => ['sometimes', 'boolean'],
            'meeting_at'       => ['nullable', 'date', 'required_with:meeting_place'],
            'meeting_place'    => ['nullable', 'string', 'max:200', 'required_with:meeting_at'],
            'surat_balasan'    => [Rule::requiredIf($butuhSurat), 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'surat_balasan.required' => 'Surat persetujuan (PDF) wajib dilampirkan untuk sewa per hari.',
            'surat_balasan.mimes'    => 'Surat persetujuan harus berupa berkas PDF.',
        ];
    }
}
