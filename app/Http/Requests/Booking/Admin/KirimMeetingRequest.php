<?php

namespace App\Http\Requests\Booking\Admin;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KirimMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $booking = $this->route('id')
            ? Booking::query()->find($this->route('id'))
            : null;

        $punyaBalasan = $booking !== null
            && $booking->surats()->where('jenis', BookingSurat::JENIS_BALASAN_PERSETUJUAN)->exists();

        return [
            'admin_notes'   => ['nullable', 'string'],
            'meeting_at'    => ['nullable', 'date', 'required_with:meeting_place'],
            'meeting_place' => ['nullable', 'string', 'max:200', 'required_with:meeting_at'],
            'surat_meeting' => [Rule::requiredIf($punyaBalasan), 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'surat_meeting.required' => 'Undangan meeting (PDF) wajib dilampirkan.',
            'surat_meeting.mimes'    => 'Undangan meeting harus berupa berkas PDF.',
        ];
    }
}
