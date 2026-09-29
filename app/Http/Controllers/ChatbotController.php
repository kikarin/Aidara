<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatbotMessageRequest;
use App\Services\Chatbot\ChatHistorySanitizer;
use App\Services\Chatbot\ChatbotScopeGuard;
use App\Services\Gemini\GeminiChatService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class ChatbotController extends Controller
{
    public function __construct(
        private readonly ChatbotScopeGuard $scopeGuard,
        private readonly ChatHistorySanitizer $historySanitizer,
        private readonly GeminiChatService $geminiChat,
    ) {}

    public function message(ChatbotMessageRequest $request): JsonResponse
    {
        if (! config('gemini.enabled')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Asisten Aplikasi sedang nonaktif.',
            ], 503);
        }

        $message = trim($request->validated('message'));
        $history = $this->historySanitizer->sanitize($request->validated('history', []));

        if (! $this->scopeGuard->isInScope($message, $history)) {
            return response()->json([
                'ok'       => false,
                'rejected' => true,
                'message'  => $this->scopeGuard->rejectionMessage(),
            ]);
        }

        try {
            $result = $this->geminiChat->chat($message, $history);

            return response()->json([
                'ok'       => true,
                'rejected' => false,
                'message'  => $result['content'],
                'model'    => $result['model'],
            ]);
        } catch (RuntimeException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 502);
        }
    }
}
