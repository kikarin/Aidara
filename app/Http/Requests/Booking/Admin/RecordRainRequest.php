<?php

namespace App\Http\Requests\Booking\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RecordRainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'play_elapsed_minutes' => ['nullable', 'integer', 'min:0'],
            'play_started_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
