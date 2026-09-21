<?php

namespace App\Services\Chatbot;

use Illuminate\Support\Str;

class ChatHistorySanitizer
{
    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return list<array{role: string, content: string}>
     */
    public function sanitize(array $history): array
    {
        $maxItems   = config('gemini.max_history', 24);
        $maxPerItem = config('gemini.max_history_item_length', 1200);

        $history = array_slice($history, -$maxItems);

        return array_values(array_map(function (array $item) use ($maxPerItem) {
            $content = (string) ($item['content'] ?? '');

            if (mb_strlen($content) > $maxPerItem) {
                $content = Str::limit($content, $maxPerItem, '…');
            }

            return [
                'role'    => $item['role'] ?? 'user',
                'content' => $content,
            ];
        }, $history));
    }
}
