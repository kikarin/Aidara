<?php

namespace App\Http\Requests;

use App\Services\Chatbot\ChatHistorySanitizer;
use Illuminate\Foundation\Http\FormRequest;

class ChatbotMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $history = $this->input('history');

        if (! is_array($history)) {
            return;
        }

        $sanitizer = app(ChatHistorySanitizer::class);
        $this->merge(['history' => $sanitizer->sanitize($history)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxMessage     = config('gemini.max_message_length', 2000);
        $maxHistoryItem = config('gemini.max_history_item_length', 1200);
        $maxHistory     = config('gemini.max_history', 24);

        return [
            'message'           => ['required', 'string', 'min:1', 'max:'.$maxMessage],
            'history'           => ['sometimes', 'array', 'max:'.$maxHistory],
            'history.*.role'    => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:'.$maxHistoryItem],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required'        => 'Pesan tidak boleh kosong.',
            'message.max'             => 'Pesan terlalu panjang.',
            'history.max'             => 'Riwayat percakapan terlalu panjang. Hapus riwayat atau mulai percakapan baru.',
            'history.*.content.max'   => 'Salah satu pesan riwayat terlalu panjang.',
        ];
    }
}
