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
        private readonly VenueClosureService $closures,
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
     *   closed: bool,
     *   closures: array<int, array<string, mixed>>,
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

        $closureRows = $this->closures->overlappingClosures($venueId, $startsAt, $endsAt, $areaId);
        $closed = $closureRows !== [];

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

        if ($closed || $hasHard) {
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
            'closed' => $closed,
            'closures' => $closureRows,
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

        if (! empty($result['closed'])) {
            $reason = $result['closures'][0]['reason'] ?? null;
            throw new InvalidArgumentException(
                $reason
                    ? "Slot ditutup admin: {$reason}"
                    : 'Slot tidak tersedia (ditutup admin).'
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

    /**
     * Slot jam siap pilih untuk satu hari (untuk UI kalender sederhana).
     *
     * @param  array{
     *   venue_id: int,
     *   date: string,
     *   area_id?: int|null,
     *   duration_hours?: int
     * }  $input
     * @return array{
     *   date: string,
     *   duration_hours: int,
     *   operating_hours: array{start: string, end: string},
     *   horizon_days: int|null,
     *   slots: array<int, array<string, mixed>>
     * }
     */
    public function daySlots(array $input): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        $venue = BookingVenue::query()->where('is_active', true)->find($venueId);
        if (! $venue) {
            throw new InvalidArgumentException('Venue tidak ditemukan atau tidak aktif.');
        }

        $date = Carbon::parse($input['date'] ?? now()->toDateString())->startOfDay();
        $areaId = isset($input['area_id']) && $input['area_id'] !== '' && $input['area_id'] !== null
            ? (int) $input['area_id']
            : null;
        $durationHours = max(1, min(12, (int) ($input['duration_hours'] ?? 1)));
        $stepHours = max(1, min(12, (int) ($input['step_hours'] ?? 1)));

        $hoursRule = $this->rules->get('operating_hours', $venueId, ['start' => '06:00', 'end' => '21:00']);
        $openStr = is_array($hoursRule) ? (string) ($hoursRule['start'] ?? '06:00') : '06:00';
        $closeStr = is_array($hoursRule) ? (string) ($hoursRule['end'] ?? '21:00') : '21:00';

        [$openH, $openM] = array_map('intval', explode(':', $openStr) + [0, 0]);
        [$closeH, $closeM] = array_map('intval', explode(':', $closeStr) + [0, 0]);

        $open = $date->copy()->setTime($openH, $openM, 0);
        $close = $date->copy()->setTime($closeH, $closeM, 0);

        $horizonDays = $this->rules->get('booking_horizon_days', $venueId, null);
        $horizonDays = is_numeric($horizonDays) ? (int) $horizonDays : null;

        $slots = [];
        $cursor = $open->copy();
        while ($cursor->copy()->addHours($durationHours)->lte($close)) {
            $startsAt = $cursor->copy();
            $endsAt = $cursor->copy()->addHours($durationHours);

            if ($startsAt->lt(now())) {
                $slots[] = [
                    'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                    'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                    'label' => $startsAt->format('H:i').'–'.$endsAt->format('H:i'),
                    'status' => 'merah',
                    'bookable' => false,
                    'reason' => 'Sudah lewat',
                ];
                $cursor->addHours($stepHours);

                continue;
            }

            $check = $this->check([
                'venue_id' => $venueId,
                'area_id' => $areaId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $reason = null;
            if (! $check['within_horizon']) {
                $reason = $horizonDays
                    ? "Hanya bisa dipesan sampai {$horizonDays} hari ke depan"
                    : 'Di luar batas pemesanan';
            } elseif (! empty($check['closed'])) {
                $reason = $check['closures'][0]['reason'] ?? 'Ditutup pengelola';
            } elseif ($check['status'] === 'merah') {
                $reason = 'Sudah dipesan';
            } elseif ($check['status'] === 'kuning') {
                $reason = 'Sedang ada pengajuan lain';
            }

            $slots[] = [
                'starts_at' => $startsAt->format('Y-m-d H:i:s'),
                'ends_at' => $endsAt->format('Y-m-d H:i:s'),
                'label' => $startsAt->format('H:i').'–'.$endsAt->format('H:i'),
                'status' => $check['status'],
                'bookable' => (bool) $check['bookable'],
                'reason' => $reason,
            ];

            $cursor->addHours($stepHours);
        }

        return [
            'date' => $date->toDateString(),
            'duration_hours' => $durationHours,
            'operating_hours' => [
                'start' => $openStr,
                'end' => $closeStr,
            ],
            'horizon_days' => $horizonDays,
            'slots' => $slots,
        ];
    }

    /**
     * Ringkasan ketersediaan per hari dalam satu bulan (untuk kalender visual).
     *
     * @param  array{
     *   venue_id: int,
     *   month: string,
     *   area_id?: int|null
     * }  $input
     * @return array{
     *   month: string,
     *   operating_hours: array{start: string, end: string},
     *   days: array<int, array{date: string, status: string, bookable: bool, reason: string|null}>
     * }
     */
    public function monthOverview(array $input): array
    {
        $venueId = (int) ($input['venue_id'] ?? 0);
        $venue = BookingVenue::query()->where('is_active', true)->find($venueId);
        if (! $venue) {
            throw new InvalidArgumentException('Venue tidak ditemukan atau tidak aktif.');
        }

        $month = (string) ($input['month'] ?? now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new InvalidArgumentException('Format month harus Y-m.');
        }

        $areaId = isset($input['area_id']) && $input['area_id'] !== '' && $input['area_id'] !== null
            ? (int) $input['area_id']
            : null;

        $hoursRule = $this->rules->get('operating_hours', $venueId, ['start' => '06:00', 'end' => '21:00']);
        $openStr = is_array($hoursRule) ? (string) ($hoursRule['start'] ?? '06:00') : '06:00';
        $closeStr = is_array($hoursRule) ? (string) ($hoursRule['end'] ?? '21:00') : '21:00';
        [$openH, $openM] = array_map('intval', explode(':', $openStr) + [0, 0]);
        [$closeH, $closeM] = array_map('intval', explode(':', $closeStr) + [0, 0]);

        $cursor = Carbon::parse($month.'-01')->startOfMonth();
        $end = $cursor->copy()->endOfMonth();
        $today = now()->startOfDay();

        $days = [];
        while ($cursor->lte($end)) {
            $dateStr = $cursor->toDateString();

            if ($cursor->lt($today)) {
                $days[] = [
                    'date' => $dateStr,
                    'status' => 'past',
                    'bookable' => false,
                    'reason' => 'Sudah lewat',
                ];
                $cursor->addDay();

                continue;
            }

            $startsAt = $cursor->copy()->setTime($openH, $openM, 0);
            $endsAt = $cursor->copy()->setTime($closeH, $closeM, 0);
            if ($endsAt->lte($startsAt)) {
                $endsAt = $startsAt->copy()->addHours(1);
            }

            $check = $this->check([
                'venue_id' => $venueId,
                'area_id' => $areaId,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $reason = null;
            if (! $check['within_horizon']) {
                $reason = $check['horizon_days']
                    ? "Hanya bisa dipesan sampai {$check['horizon_days']} hari ke depan"
                    : 'Di luar batas pemesanan';
            } elseif (! empty($check['closed'])) {
                $reason = $check['closures'][0]['reason'] ?? 'Ditutup pengelola';
            } elseif ($check['status'] === 'merah') {
                $reason = 'Sudah dipesan / penuh';
            } elseif ($check['status'] === 'kuning') {
                $reason = 'Ada pengajuan lain';
            }

            $days[] = [
                'date' => $dateStr,
                'status' => $check['status'],
                'bookable' => (bool) $check['bookable'],
                'reason' => $reason,
            ];

            $cursor->addDay();
        }

        return [
            'month' => $month,
            'operating_hours' => [
                'start' => $openStr,
                'end' => $closeStr,
            ],
            'days' => $days,
        ];
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
