<?php

namespace App\Http\Requests\Booking\Admin;

use App\Models\Booking\Booking;
use App\Support\Booking\BookingJenisSewa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanjutMeetingRequest extends FormRequest
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
            'admin_notes'   => ['nullable', 'string'],
            'surat_balasan' => [Rule::requiredIf($butuhSurat), 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'surat_balasan.required' => 'Surat balasan (PDF) wajib dilampirkan untuk sewa per hari.',
            'surat_balasan.mimes'    => 'Surat balasan harus berupa berkas PDF.',
        ];
    }
}
