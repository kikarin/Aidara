<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class VenueClosureService
{
    /** Batas maksimum baris yang dibuat sekaligus untuk blok berulang. */
    private const MAX_RECURRING_ROWS = 200;

    public function __construct(
        private readonly RulesEngine $rules,
    ) {}
    /**
     * @param  array{
     *   venue_id?: int|null,
     *   area_id?: int|null,
     *   from?: string|null,
     *   to?: string|null,
     *   active_only?: bool,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = BookingVenueClosure::query()
            ->with(['venue:id,code,name', 'area:id,code,name'])
            ->orderByDesc('starts_at');

        if (! empty($filters['venue_id'])) {
            $query->where('venue_id', (int) $filters['venue_id']);
        }
        if (array_key_exists('area_id', $filters) && $filters['area_id'] !== null && $filters['area_id'] !== '') {
            $query->where('area_id', (int) $filters['area_id']);
        }
        if (! empty($filters['from'])) {
            $query->where('ends_at', '>', Carbon::parse($filters['from']));
        }
        if (! empty($filters['to'])) {
            $query->where('starts_at', '<', Carbon::parse($filters['to']));
        }
        if (($filters['active_only'] ?? true) === true) {
            $query->where('is_active', true);
        }

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        return $query->paginate($perPage);
    }

    /**
     * @param  array{
     *   venue_id: int,
     *   area_id?: int|null,
     *   starts_at: string|\DateTimeInterface,
     *   ends_at: string|\DateTimeInterface,
     *   reason?: string|null,
     *   is_active?: bool
     * }  $input
     */
    public function create(array $input): BookingVenueClosure
    {
        $payload = $this->normalizePayload($input);

        return BookingVenueClosure::query()->create($payload);
    }

    /**
     * Buat blok berulang mingguan: satu baris closure untuk setiap tanggal
     * pada rentang yang weekday-nya cocok.
     *
     * @param  array{
     *   venue_id: int,
     *   area_id?: int|null,
     *   weekdays: array<int, int>,
     *   start_date: string,
     *   end_date: string,
     *   start_time: string,
     *   end_time: string,
     *   full_day?: bool,
     *   reason?: string|null,
     *   is_active?: bool
     * }  $input
     * @return array{created: int, batch_id: string}
     */
    public function createRecurring(array $input): array
    {
        ['venue_id' => $venueId, 'area_id' => $areaId] = $this->resolveVenueAndArea($input);

        $weekdays = collect($input['weekdays'] ?? [])
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 0 && $day <= 6)
            ->unique()
            ->values()
            ->all();

        if ($weekdays === []) {
            throw new InvalidArgumentException('Pilih minimal satu hari.');
        }

        $startDate = Carbon::parse($input['start_date'])->startOfDay();
        $endDate = Carbon::parse($input['end_date'])->startOfDay();

        if ($endDate->lt($startDate)) {
            throw new InvalidArgumentException('Tanggal selesai harus setelah tanggal mulai.');
        }
        if ($startDate->diffInDays($endDate) > 366) {
            throw new InvalidArgumentException('Rentang tanggal maksimal 1 tahun.');
        }

        $fullDay = (bool) ($input['full_day'] ?? false);

        if ($fullDay) {
            $window = $this->operatingWindow($venueId);
            $startTime = Carbon::parse($window['start']);
            $endTime = Carbon::parse($window['end']);
        } else {
            $startTime = Carbon::parse($input['start_time']);
            $endTime = Carbon::parse($input['end_time']);
        }

        if ($endTime->lte($startTime)) {
            throw new InvalidArgumentException('Jam selesai harus setelah jam mulai.');
        }

        $reason = isset($input['reason']) ? trim((string) $input['reason']) : null;
        if ($reason === '') {
            $reason = null;
        }
        $isActive = (bool) ($input['is_active'] ?? true);

        $today = now()->startOfDay();
        $batchId = (string) Str::uuid();
        $rows = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            if (in_array($cursor->dayOfWeek, $weekdays, true) && $cursor->gte($today)) {
                if (count($rows) >= self::MAX_RECURRING_ROWS) {
                    throw new InvalidArgumentException('Terlalu banyak tanggal (maks '.self::MAX_RECURRING_ROWS.'). Perkecil rentang atau kurangi hari.');
                }

                $rows[] = [
                    'venue_id' => $venueId,
                    'area_id' => $areaId,
                    'starts_at' => $cursor->copy()->setTime($startTime->hour, $startTime->minute),
                    'ends_at' => $cursor->copy()->setTime($endTime->hour, $endTime->minute),
                    'reason' => $reason,
                    'batch_id' => $batchId,
                    'is_full_day' => $fullDay,
                    'is_active' => $isActive,
                ];
            }

            $cursor->addDay();
        }

        if ($rows === []) {
            throw new InvalidArgumentException('Tidak ada tanggal yang cocok dengan hari yang dipilih.');
        }

        $created = DB::transaction(function () use ($rows) {
            $count = 0;

            foreach ($rows as $row) {
                $exists = BookingVenueClosure::query()
                    ->where('venue_id', $row['venue_id'])
                    ->when(
                        $row['area_id'] !== null,
                        fn ($query) => $query->where('area_id', $row['area_id']),
                        fn ($query) => $query->whereNull('area_id'),
                    )
                    ->where('starts_at', $row['starts_at'])
                    ->where('ends_at', $row['ends_at'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                BookingVenueClosure::query()->create($row);
                $count++;
            }

            return $count;
        });

        if ($created === 0) {
            throw new InvalidArgumentException('Semua tanggal pada rentang tersebut sudah diblok.');
        }

        return ['created' => $created, 'batch_id' => $batchId];
    }

    public function deleteBatch(string $batchId): int
    {
        return BookingVenueClosure::query()->where('batch_id', $batchId)->delete();
    }

    /**
     * @param  array{
     *   venue_id?: int,
     *   area_id?: int|null,
     *   starts_at?: string|\DateTimeInterface,
     *   ends_at?: string|\DateTimeInterface,
     *   reason?: string|null,
     *   is_active?: bool
     * }  $input
     */
    public function update(BookingVenueClosure $closure, array $input): BookingVenueClosure
    {
        $merged = array_merge([
            'venue_id' => $closure->venue_id,
            'area_id' => $closure->area_id,
            'starts_at' => $closure->starts_at,
            'ends_at' => $closure->ends_at,
            'reason' => $closure->reason,
            'is_full_day' => $closure->is_full_day,
            'is_active' => $closure->is_active,
        ], $input);

        $payload = $this->normalizePayload($merged);
        $closure->update($payload);

        return $closure->fresh(['venue:id,code,name', 'area:id,code,name']);
    }

    public function delete(BookingVenueClosure $closure): void
    {
        $closure->delete();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function overlappingClosures(int $venueId, $startsAt, $endsAt, ?int $areaId = null): array
    {
        return BookingVenueClosure::query()
            ->overlapping($venueId, $startsAt, $endsAt, $areaId)
            ->orderBy('starts_at')
            ->get(['id', 'venue_id', 'area_id', 'starts_at', 'ends_at', 'reason', 'is_full_day'])
            ->map(fn (BookingVenueClosure $c) => [
                'id' => $c->id,
                'venue_id' => $c->venue_id,
                'area_id' => $c->area_id,
                'starts_at' => optional($c->starts_at)->toDateTimeString(),
                'ends_at' => optional($c->ends_at)->toDateTimeString(),
                'reason' => $c->reason,
                'is_full_day' => (bool) $c->is_full_day,
            ])
            ->all();
    }

    /**
     * Jam operasional venue (buka–tutup) dalam format H:i.
     *
     * @return array{start: string, end: string}
     */
    public function operatingWindow(int $venueId): array
    {
        $rule = $this->rules->get('operating_hours', $venueId, ['start' => '06:00', 'end' => '21:00']);

        $start = is_array($rule) ? (string) ($rule['start'] ?? '06:00') : '06:00';
        $end = is_array($rule) ? (string) ($rule['end'] ?? '21:00') : '21:00';

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizePayload(array $input): array
    {
        ['venue_id' => $venueId, 'area_id' => $areaId] = $this->resolveVenueAndArea($input);

        $fullDay = (bool) ($input['full_day'] ?? false);

        if ($fullDay) {
            $date = Carbon::parse($input['date'])->startOfDay();
            $window = $this->operatingWindow($venueId);
            $startsAt = $date->copy()->setTime(...array_map('intval', explode(':', (string) $window['start']) + [0, 0]));
            $endsAt = $date->copy()->setTime(...array_map('intval', explode(':', (string) $window['end']) + [0, 0]));
        } else {
            $startsAt = Carbon::parse($input['starts_at']);
            $endsAt = Carbon::parse($input['ends_at']);
        }

        if ($endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('ends_at harus setelah starts_at.');
        }

        $reason = isset($input['reason']) ? trim((string) $input['reason']) : null;
        if ($reason === '') {
            $reason = null;
        }

        return [
            'venue_id' => $venueId,
            'area_id' => $areaId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'reason' => $reason,
            'is_full_day' => $fullDay,
            'is_active' => (bool) ($input['is_active'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{venue_id: int, area_id: int|null}
     */
    private function resolveVenueAndArea(array $input): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        if (! BookingVenue::query()->whereKey($venueId)->exists()) {
            throw new InvalidArgumentException('Venue tidak ditemukan.');
        }

        $areaId = array_key_exists('area_id', $input) && $input['area_id'] !== null && $input['area_id'] !== ''
            ? (int) $input['area_id']
            : null;

        if ($areaId !== null) {
            $area = BookingArea::query()->whereKey($areaId)->first();
            if (! $area || (int) $area->venue_id !== $venueId) {
                throw new InvalidArgumentException('Area tidak valid untuk venue ini.');
            }
        }

        return ['venue_id' => $venueId, 'area_id' => $areaId];
    }
}
