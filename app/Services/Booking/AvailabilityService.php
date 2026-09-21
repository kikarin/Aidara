<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingVenue;
use App\Support\Booking\BookingStatus;
use Carbon\Carbon;
use InvalidArgumentException;

class AvailabilityService
{
    public function __construct(
        private readonly RulesEngine $rules,
    ) {}

    /**
     * @param  array{
     *   venue_id: int,
     *   area_id?: int|null,
     *   starts_at: string|\DateTimeInterface,
     *   ends_at: string|\DateTimeInterface,
     *   exclude_booking_id?: int|null
     * }  $input
     * @return array{
     *   status: string,
     *   color: string,
     *   bookable: bool,
     *   within_horizon: bool,
     *   horizon_days: int|null,
     *   conflicts: array<int, array<string, mixed>>,
     *   buffer: array{before: int, after: int}
     * }
     */
    public function check(array $input): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        $venue = BookingVenue::query()->where('is_active', true)->find($venueId);
        if (! $venue) {
            throw new InvalidArgumentException('Venue tidak ditemukan atau tidak aktif.');
        }

        $startsAt = Carbon::parse($input['starts_at']);
        $endsAt = Carbon::parse($input['ends_at']);
        if ($endsAt->lte($startsAt)) {
            throw new InvalidArgumentException('ends_at harus setelah starts_at.');
        }

        $areaId = isset($input['area_id']) ? (int) $input['area_id'] : null;
        $excludeId = isset($input['exclude_booking_id']) ? (int) $input['exclude_booking_id'] : null;

        $bufferBefore = (int) ($this->rules->get('buffer_before_days', $venueId, 1) ?? 1);
        $bufferAfter = (int) ($this->rules->get('buffer_after_days', $venueId, 1) ?? 1);
        $horizonDays = $this->rules->get('booking_horizon_days', $venueId, null);
        $horizonDays = is_numeric($horizonDays) ? (int) $horizonDays : null;

        $withinHorizon = true;
        if ($horizonDays !== null && $horizonDays > 0) {
            $latest = now()->startOfDay()->addDays($horizonDays)->endOfDay();
            $withinHorizon = $startsAt->lte($latest) && $startsAt->gte(now()->startOfDay());
        }

        $windowStart = $startsAt->copy()->subDays($bufferBefore)->startOfDay();
        $windowEnd = $endsAt->copy()->addDays($bufferAfter)->endOfDay();

        $query = Booking::query()
            ->where('venue_id', $venueId)
            ->whereIn('status', BookingStatus::locking())
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart);

        if ($areaId) {
            $query->where(function ($q) use ($areaId) {
                $q->where('area_id', $areaId)->orWhereNull('area_id');
            });
        }

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $conflicts = $query
            ->orderBy('starts_at')
            ->get(['id', 'nomor', 'status', 'priority_flag', 'area_id', 'starts_at', 'ends_at', 'buffer_before_days', 'buffer_after_days']);

        $hasHard = false;
        $hasSoftOrHold = false;
        $mapped = [];

        foreach ($conflicts as $booking) {
            $lockLevel = $this->lockLevel($booking->status);
            if ($lockLevel === 'hard') {
                $hasHard = true;
            } elseif ($lockLevel !== null) {
                $hasSoftOrHold = true;
            }

            $mapped[] = [
                'id' => $booking->id,
                'nomor' => $booking->nomor,
                'status' => $booking->status,
                'priority_flag' => $booking->priority_flag,
                'area_id' => $booking->area_id,
                'starts_at' => optional($booking->starts_at)->toDateTimeString(),
                'ends_at' => optional($booking->ends_at)->toDateTimeString(),
                'lock_level' => $lockLevel,
            ];
        }

        if ($hasHard) {
            $status = 'merah';
        } elseif ($hasSoftOrHold) {
            $status = 'kuning';
        } else {
            $status = 'hijau';
        }

        $bookable = $status !== 'merah' && $withinHorizon;

        return [
            'status' => $status,
            'color' => $status,
            'bookable' => $bookable,
            'within_horizon' => $withinHorizon,
            'horizon_days' => $horizonDays,
            'conflicts' => $mapped,
            'buffer' => [
                'before' => $bufferBefore,
                'after' => $bufferAfter,
            ],
        ];
    }

    public function assertBookable(array $input): array
    {
        $result = $this->check($input);

        if (! $result['within_horizon']) {
            $days = $result['horizon_days'] ?? 0;
            throw new InvalidArgumentException(
                $days > 0
                    ? "Booking hanya dibuka {$days} hari ke depan untuk venue ini."
                    : 'Tanggal di luar horizon booking venue.'
            );
        }

        if ($result['status'] === 'merah') {
            throw new InvalidArgumentException('Slot tidak tersedia (hard-lock).');
        }

        return $result;
    }

    /**
     * Alias ketat untuk konfirmasi slot: gagal jika ada hard-lock booking lain.
     * (Hold/soft tidak memblokir — race double-confirm dicegah lewat lock overlap di PaymentService.)
     *
     * @param  array{
     *   venue_id: int,
     *   area_id?: int|null,
     *   starts_at: string|\DateTimeInterface,
     *   ends_at: string|\DateTimeInterface,
     *   exclude_booking_id?: int|null
     * }  $input
     * @return array<string, mixed>
     */
    public function assertConfirmable(array $input): array
    {
        return $this->assertBookable($input);
    }

    private function lockLevel(string $status): ?string
    {
        if (in_array($status, BookingStatus::hardLock(), true)) {
            return 'hard';
        }
        if (in_array($status, BookingStatus::holdLock(), true)) {
            return 'hold';
        }
        if (in_array($status, BookingStatus::softLock(), true)) {
            return 'soft';
        }

        return null;
    }
}
