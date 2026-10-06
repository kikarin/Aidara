<?php

namespace App\Http\Requests\Booking\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'priority_rule_id' => ['nullable', 'integer', 'exists:booking_priority_rules,id'],
            'admin_notes'      => ['nullable', 'string'],
            'note'             => ['nullable', 'string'],
            'force'            => ['sometimes', 'boolean'],
            'meeting_at'       => ['nullable', 'date', 'required_with:meeting_place'],
            'meeting_place'    => ['nullable', 'string', 'max:200', 'required_with:meeting_at'],
        ];
    }
}
