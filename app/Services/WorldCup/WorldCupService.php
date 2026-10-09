<?php

namespace App\Services\WorldCup;

use App\Services\AppSettingService;

class WorldCupService
{
    public function __construct(
        private readonly WorldCupApiService $api,
        private readonly WorldCupDataTransformer $transformer,
        private readonly AppSettingService $settings,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUpcomingKnockoutMatches(?int $limit = null): array
    {
        $limit ??= $this->settings->getPreviewCount();

        return collect($this->getAllMatches())
            ->filter(fn (array $match) => $match['isKnockout'] && $match['status'] === 'upcoming')
            ->sortBy('localDate')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLiveMatches(): array
    {
        return collect($this->getAllMatches())
            ->filter(fn (array $match) => $match['status'] === 'live')
            ->sortBy('localDate')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMatchesByStage(string $type): array
    {
        return collect($this->getAllMatches())
            ->filter(fn (array $match) => $match['stage'] === $type)
            ->sortBy('localDate')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getKnockoutMatches(): array
    {
        return collect($this->getAllMatches())
            ->filter(fn (array $match) => $match['isKnockout'])
            ->sortBy('localDate')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGroupStandings(): array
    {
        $groups = $this->api->getGroups();
        $teams = $this->api->getTeams();

        return $this->transformer->transformGroups($groups, $teams);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllMatches(): array
    {
        $games = $this->api->getGames();
        $teams = $this->api->getTeams();
        $stadiums = $this->api->getStadiums();

        return collect($games)
            ->filter(fn ($game) => is_array($game))
            ->map(fn (array $game) => $this->transformer->transformMatch($game, $teams, $stadiums))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getApiStatus(): array
    {
        $health = $this->api->getHealth();
        $games = $this->api->getGames();
        $tokenConfigured = ! empty(config('worldcup.token'));

        return [
            'token_configured' => $tokenConfigured,
            'healthy' => is_array($health) && (($health['status'] ?? null) === 'healthy'),
            'health' => $health,
            'games_count' => count($games),
            'cache_ttl_seconds' => config('worldcup.cache_ttl', 60),
            'cache_ttl_static_seconds' => config('worldcup.cache_ttl_static', 300),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getLandingPreview(): array
    {
        $limit = $this->settings->getPreviewCount();

        $liveKnockout = collect($this->getLiveMatches())
            ->filter(fn (array $match) => $match['isKnockout'])
            ->values();

        $liveIds = $liveKnockout->pluck('id')->all();

        $upcomingKnockout = collect($this->getUpcomingKnockoutMatches($limit))
            ->reject(fn (array $match) => in_array($match['id'], $liveIds, true))
            ->values();

        $remaining = max(0, $limit - $liveKnockout->count());
        $previewMatches = $liveKnockout
            ->merge($upcomingKnockout->take($remaining))
            ->values()
            ->all();

        return [
            'matches' => $previewMatches,
            'liveMatches' => $liveKnockout->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSchedulePayload(): array
    {
        $knockoutMatches = $this->getKnockoutMatches();

        return [
            'liveMatches' => $this->getLiveMatches(),
            'upcomingMatches' => collect($knockoutMatches)
                ->filter(fn (array $match) => $match['status'] === 'upcoming')
                ->values()
                ->all(),
            'finishedMatches' => collect($knockoutMatches)
                ->filter(fn (array $match) => $match['status'] === 'finished')
                ->values()
                ->all(),
            'knockoutMatches' => $knockoutMatches,
            'knockoutByStage' => collect($knockoutMatches)
                ->groupBy('stage')
                ->map(fn ($items) => $items->values()->all())
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getFullPageData(): array
    {
        return $this->getSchedulePayload();
    }
}
