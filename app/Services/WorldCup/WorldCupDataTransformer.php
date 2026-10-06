<?php

namespace App\Services\WorldCup;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class WorldCupDataTransformer
{
    private const STAGE_LABELS = [
        'group' => 'Fase Grup',
        'r32' => 'Babak 32 Besar',
        'r16' => 'Babak 16 Besar',
        'qf' => 'Perempat Final',
        'sf' => 'Semi Final',
        'third' => 'Perebutan Juara 3',
        'final' => 'Final',
    ];

    private const KNOCKOUT_TYPES = ['r32', 'r16', 'qf', 'sf', 'third', 'final'];

    /**
     * @param  array<int, array<string, mixed>>  $teams
     * @param  array<int, array<string, mixed>>  $stadiums
     */
    public function transformMatch(array $game, array $teams = [], array $stadiums = []): array
    {
        $teamsById = $this->indexById($teams, 'id');
        $stadiumsById = $this->indexById($stadiums, 'id');

        $homeTeamId = (string) ($game['home_team_id'] ?? '0');
        $awayTeamId = (string) ($game['away_team_id'] ?? '0');

        $homeTeam = $this->resolveTeam($homeTeamId, $teamsById, $game, 'home');
        $awayTeam = $this->resolveTeam($awayTeamId, $teamsById, $game, 'away');

        $stadiumId = (string) ($game['stadium_id'] ?? '');
        $stadium = $stadiumsById[$stadiumId] ?? null;

        $type = (string) ($game['type'] ?? 'group');
        $status = $this->resolveStatus($game);
        $localDate = $this->parseLocalDate($game['local_date'] ?? null);

        return [
            'id' => (string) ($game['id'] ?? ''),
            'homeTeam' => $homeTeam,
            'awayTeam' => $awayTeam,
            'homeScore' => (int) ($game['home_score'] ?? 0),
            'awayScore' => (int) ($game['away_score'] ?? 0),
            'homeScorers' => $this->normalizeNullableString($game['home_scorers'] ?? null),
            'awayScorers' => $this->normalizeNullableString($game['away_scorers'] ?? null),
            'stage' => $type,
            'stageLabel' => self::STAGE_LABELS[$type] ?? ucfirst($type),
            'group' => (string) ($game['group'] ?? ''),
            'matchday' => (string) ($game['matchday'] ?? ''),
            'status' => $status,
            'statusLabel' => match ($status) {
                'live' => 'Live',
                'finished' => 'Selesai',
                default => 'Akan Datang',
            },
            'timeElapsed' => (string) ($game['time_elapsed'] ?? 'notstarted'),
            'localDate' => $localDate?->toIso8601String(),
            'localDateFormatted' => $localDate?->timezone('Asia/Jakarta')->format('d M Y, H:i').' WIB',
            'isKnockout' => in_array($type, self::KNOCKOUT_TYPES, true),
            'stadium' => $stadium ? [
                'id' => (string) ($stadium['id'] ?? ''),
                'name' => (string) ($stadium['name_en'] ?? $stadium['fifa_name'] ?? ''),
                'city' => (string) ($stadium['city_en'] ?? ''),
                'country' => (string) ($stadium['country_en'] ?? ''),
            ] : null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     * @param  array<int, array<string, mixed>>  $teams
     * @return array<int, array<string, mixed>>
     */
    public function transformGroups(array $groups, array $teams): array
    {
        $teamsById = $this->indexById($teams, 'id');

        return collect($groups)
            ->map(function (array $group) use ($teamsById) {
                $groupName = (string) ($group['group'] ?? '');

                $standings = collect($group['teams'] ?? [])
                    ->map(function (array $row) use ($teamsById) {
                        $teamId = (string) ($row['team_id'] ?? '');
                        $team = $teamsById[$teamId] ?? null;

                        return [
                            'teamId' => $teamId,
                            'name' => (string) ($team['name_en'] ?? 'Tim #'.$teamId),
                            'flag' => (string) ($team['flag'] ?? ''),
                            'fifaCode' => (string) ($team['fifa_code'] ?? ''),
                            'pts' => (int) ($row['pts'] ?? 0),
                            'gf' => (int) ($row['gf'] ?? 0),
                            'ga' => (int) ($row['ga'] ?? 0),
                            'gd' => (int) ($row['gf'] ?? 0) - (int) ($row['ga'] ?? 0),
                        ];
                    })
                    ->sortByDesc('pts')
                    ->values()
                    ->all();

                return [
                    'group' => $groupName,
                    'standings' => $standings,
                ];
            })
            ->sortBy('group')
            ->values()
            ->all();
    }

    public function knockoutTypes(): array
    {
        return self::KNOCKOUT_TYPES;
    }

    private function resolveTeam(string $teamId, array $teamsById, array $game, string $side): array
    {
        $labelKey = $side.'_team_label';
        $nameKeyEn = $side.'_team_name_en';
        $nameKeyFa = $side.'_team_name_fa';

        if ($teamId !== '0' && isset($teamsById[$teamId])) {
            $team = $teamsById[$teamId];

            return [
                'id' => $teamId,
                'name' => (string) ($team['name_en'] ?? $game[$nameKeyEn] ?? 'Tim #'.$teamId),
                'flag' => (string) ($team['flag'] ?? ''),
                'fifaCode' => (string) ($team['fifa_code'] ?? ''),
                'label' => null,
            ];
        }

        $label = $this->normalizeNullableString($game[$labelKey] ?? null);
        $name = $this->normalizeNullableString($game[$nameKeyEn] ?? null)
            ?? $label
            ?? 'TBD';

        return [
            'id' => $teamId !== '0' ? $teamId : null,
            'name' => $name,
            'flag' => '',
            'fifaCode' => '',
            'label' => $label,
        ];
    }

    private function resolveStatus(array $game): string
    {
        $finished = strtoupper((string) ($game['finished'] ?? 'FALSE')) === 'TRUE';
        $timeElapsed = strtolower((string) ($game['time_elapsed'] ?? 'notstarted'));

        if ($finished) {
            return 'finished';
        }

        if ($timeElapsed !== 'notstarted' && $timeElapsed !== '') {
            return 'live';
        }

        return 'upcoming';
    }

    private function parseLocalDate(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('m/d/Y H:i', trim($value), 'UTC');
        } catch (\Throwable) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, array<string, mixed>>
     */
    private function indexById(array $items, string $key): array
    {
        $indexed = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = (string) ($item[$key] ?? '');

            if ($id !== '') {
                $indexed[$id] = $item;
            }
        }

        return $indexed;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '' || strtolower($string) === 'null') {
            return null;
        }

        return $string;
    }
}
