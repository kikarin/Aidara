<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class WorldCupSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'preview_count' => ['required', 'integer', 'min:1', 'max:20'],
            'show_on_landing' => ['required', 'boolean'],
            'section_title' => ['required', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => filter_var($this->input('enabled'), FILTER_VALIDATE_BOOLEAN),
            'show_on_landing' => filter_var($this->input('show_on_landing'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
