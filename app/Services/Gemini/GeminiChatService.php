<?php

namespace App\Services\Gemini;

use App\Services\Chatbot\ChatbotKnowledgeBase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GeminiChatService
{
    public function __construct(
        private readonly ChatbotKnowledgeBase $knowledgeBase,
    ) {}

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{content: string, model: string}
     */
    public function chat(string $userMessage, array $history = []): array
    {
        $apiKey = config('gemini.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('GEMINI_API_KEY belum dikonfigurasi.');
        }

        $model   = config('gemini.model', 'gemini-2.0-flash');
        $baseUrl = rtrim((string) config('gemini.base_url'), '/');
        $url     = "{$baseUrl}/models/{$model}:generateContent";

        $contents = $this->buildContents($history, $userMessage);

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $this->knowledgeBase->buildSystemPrompt()],
                ],
            ],
            'contents'          => $contents,
            'generationConfig'  => [
                'temperature'     => config('gemini.temperature', 0.2),
                'maxOutputTokens' => config('gemini.max_output_tokens', 1024),
            ],
        ];

        try {
            $response = Http::timeout(60)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, $payload);

            $response->throw();
        } catch (RequestException $e) {
            Log::error('Gemini API error', [
                'status' => $e->response?->status(),
                'body'   => $e->response?->json() ?? $e->response?->body(),
            ]);

            throw new RuntimeException('Gagal menghubungi layanan AI. Silakan coba lagi.');
        }

        $data = $response->json();

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null || $text === '') {
            $blockReason = $data['candidates'][0]['finishReason'] ?? 'unknown';
            Log::warning('Gemini empty response', ['finishReason' => $blockReason, 'data' => $data]);

            throw new RuntimeException('Tidak ada respons dari AI. Silakan ulangi pertanyaan.');
        }

        return [
            'content' => trim($text),
            'model'   => $model,
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return list<array{role: string, parts: list<array{text: string}>}>
     */
    private function buildContents(array $history, string $userMessage): array
    {
        $maxHistory = config('gemini.max_history', 10);
        $history    = array_slice($history, -$maxHistory);

        $contents = [];

        foreach ($history as $item) {
            $role    = $item['role'] === 'assistant' ? 'model' : 'user';
            $content = trim((string) ($item['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            $contents[] = [
                'role'  => $role,
                'parts' => [['text' => $content]],
            ];
        }

        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userMessage]],
        ];

        return $contents;
    }
}
