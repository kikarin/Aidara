<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

class VenueClosureService
{
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
            ->get(['id', 'venue_id', 'area_id', 'starts_at', 'ends_at', 'reason'])
            ->map(fn (BookingVenueClosure $c) => [
                'id' => $c->id,
                'venue_id' => $c->venue_id,
                'area_id' => $c->area_id,
                'starts_at' => optional($c->starts_at)->toDateTimeString(),
                'ends_at' => optional($c->ends_at)->toDateTimeString(),
                'reason' => $c->reason,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizePayload(array $input): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        if (! BookingVenue::query()->whereKey($venueId)->exists()) {
            throw new InvalidArgumentException('Venue tidak ditemukan.');
        }

        $startsAt = Carbon::parse($input['starts_at']);
        $endsAt = Carbon::parse($input['ends_at']);
        if ($endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('ends_at harus setelah starts_at.');
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
            'is_active' => (bool) ($input['is_active'] ?? true),
        ];
    }
}
