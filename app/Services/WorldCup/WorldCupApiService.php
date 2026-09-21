<?php

namespace App\Services\WorldCup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorldCupApiService
{
    public function getGames(): array
    {
        return $this->fetchCached('games', '/get/games', $this->liveCacheTtl());
    }

    public function getGroups(): array
    {
        return $this->fetchCached('groups', '/get/groups', $this->liveCacheTtl());
    }

    public function getTeams(): array
    {
        return $this->fetchCached('teams', '/get/teams', $this->staticCacheTtl());
    }

    public function getStadiums(): array
    {
        return $this->fetchCached('stadiums', '/get/stadiums', $this->staticCacheTtl());
    }

    public function getGame(string $id): ?array
    {
        $payload = $this->request('/get/game/'.$id);

        if ($payload === null) {
            return null;
        }

        if (isset($payload['game']) && is_array($payload['game'])) {
            return $payload['game'];
        }

        return is_array($payload) ? $payload : null;
    }

    public function getHealth(): ?array
    {
        return $this->request('/health', authenticated: false);
    }

    public function clearCache(): void
    {
        foreach (['games', 'groups', 'teams', 'stadiums', 'health'] as $key) {
            Cache::forget($this->cacheKey($key));
        }
    }

    private function fetchCached(string $cacheKey, string $path, int $ttl): array
    {
        return Cache::remember(
            $this->cacheKey($cacheKey),
            $ttl,
            function () use ($path, $cacheKey) {
                $payload = $this->request($path);

                return $this->normalizeListPayload($payload, $cacheKey);
            },
        );
    }

    private function liveCacheTtl(): int
    {
        return max(1, (int) config('worldcup.cache_ttl', 60));
    }

    private function staticCacheTtl(): int
    {
        return max(1, (int) config('worldcup.cache_ttl_static', 300));
    }

    private function request(string $path, bool $authenticated = true): ?array
    {
        $baseUrl = rtrim((string) config('worldcup.base_url'), '/');
        $url = $baseUrl.$path;

        try {
            $request = Http::timeout(config('worldcup.timeout', 10))
                ->acceptJson();

            if ($authenticated) {
                $token = config('worldcup.token');

                if (empty($token)) {
                    Log::warning('WorldCup API token is not configured.');

                    return null;
                }

                $request = $request->withToken($token);
            }

            $response = $request->get($url);

            if (! $response->successful()) {
                Log::warning('WorldCup API request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();

            return is_array($json) ? $json : null;
        } catch (\Throwable $e) {
            Log::error('WorldCup API request exception', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function normalizeListPayload(?array $payload, string $cacheKey): array
    {
        if ($payload === null) {
            return [];
        }

        if (isset($payload[$cacheKey]) && is_array($payload[$cacheKey])) {
            return array_values($payload[$cacheKey]);
        }

        if (isset($payload['games']) && is_array($payload['games'])) {
            return array_values($payload['games']);
        }

        if (isset($payload['data']) && is_array($payload['data'])) {
            return array_values($payload['data']);
        }

        if (array_is_list($payload)) {
            return $payload;
        }

        return [$payload];
    }

    private function cacheKey(string $suffix): string
    {
        return 'worldcup.api.'.$suffix;
    }
}
