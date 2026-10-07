<?php

namespace App\Http\Requests\Booking\Admin;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingSurat;
use App\Support\Booking\BookingJenisSewa;
use App\Support\Booking\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RejectBookingRequest extends FormRequest
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

        // Surat penolakan wajib hanya untuk penolakan dini (sebelum tahap keputusan
        // akhir). Keputusan akhir setelah meeting cukup tombol tolak.
        $punyaBalasan = $booking !== null
            && $booking->surats()->where('jenis', BookingSurat::JENIS_BALASAN_PERSETUJUAN)->exists();

        $finalStage = $booking !== null
            && ($booking->status === BookingStatus::MENUNGGU_MEETING || $punyaBalasan);

        $butuhSurat = $booking !== null
            && BookingJenisSewa::isPerHari($booking)
            && $this->routeIs('e-booking.admin.bookings.reject')
            && ! $finalStage;

        return [
            'reason'        => ['required', 'string', 'max:1000'],
            'surat_balasan' => [Rule::requiredIf($butuhSurat), 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'surat_balasan.required' => 'Surat alasan penolakan (PDF) wajib dilampirkan untuk sewa per hari.',
            'surat_balasan.mimes'    => 'Surat alasan penolakan harus berupa berkas PDF.',
        ];
    }
}
