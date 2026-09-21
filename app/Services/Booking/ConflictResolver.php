<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingSetting;
use App\Support\Booking\BookingStatus;
use Carbon\Carbon;

class ConflictResolver
{
    public function __construct(
        private readonly RulesEngine $rules,
    ) {}

    /**
     * @return array{
     *   flag: string,
     *   needs_clarification: bool,
     *   winners: array<int, int>,
     *   losers: array<int, int>,
     *   peers: array<int, int>,
     *   conflicts: array<int, array<string, mixed>>,
     *   tentative_context: bool,
     *   kontak_klarifikasi: mixed
     * }
     */
    public function resolve(int $bookingId): array
    {
        $booking = Booking::query()->with(['priorityRule', 'area'])->findOrFail($bookingId);

        $bufferBefore = (int) ($booking->buffer_before_days ?? 1);
        $bufferAfter = (int) ($booking->buffer_after_days ?? 1);
        $windowStart = Carbon::parse($booking->starts_at)->subDays($bufferBefore)->startOfDay();
        $windowEnd = Carbon::parse($booking->ends_at)->addDays($bufferAfter)->endOfDay();

        $others = Booking::query()
            ->with(['priorityRule', 'area'])
            ->where('id', '!=', $booking->id)
            ->where('venue_id', $booking->venue_id)
            ->whereIn('status', BookingStatus::locking())
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart)
            ->when($booking->area_id, function ($q) use ($booking) {
                $q->where(function ($inner) use ($booking) {
                    $inner->where('area_id', $booking->area_id)->orWhereNull('area_id');
                });
            })
            ->get();

        $tentativeCodes = $this->rules->get('tentative_areas', $booking->venue_id, []) ?? [];
        if (! is_array($tentativeCodes)) {
            $tentativeCodes = [];
        }
        $isTentativeContext = $this->isTentativeArea($booking, $tentativeCodes);

        $selfOrder = $this->effectivePriorityOrder($booking, $isTentativeContext);
        $winners = [];
        $losers = [];
        $peers = [];
        $conflicts = [];

        foreach ($others as $other) {
            $otherTentative = $isTentativeContext || $this->isTentativeArea($other, $tentativeCodes);
            $otherOrder = $this->effectivePriorityOrder($other, $otherTentative);

            $relation = 'peer';
            if ($selfOrder < $otherOrder) {
                $relation = 'self_unggul';
                $winners[] = $booking->id;
                $losers[] = $other->id;
            } elseif ($selfOrder > $otherOrder) {
                $relation = 'other_unggul';
                $winners[] = $other->id;
                $losers[] = $booking->id;
            } else {
                $peers[] = $other->id;
            }

            $conflicts[] = [
                'id' => $other->id,
                'nomor' => $other->nomor,
                'status' => $other->status,
                'priority_flag' => $other->priority_flag,
                'priority_order' => $otherOrder,
                'priority_rule' => $other->priorityRule?->only(['id', 'code', 'name', 'priority_order']),
                'relation' => $relation,
                'area_tentative' => $this->isTentativeArea($other, $tentativeCodes),
                'starts_at' => optional($other->starts_at)->toDateTimeString(),
                'ends_at' => optional($other->ends_at)->toDateTimeString(),
            ];
        }

        $needsClarification = $peers !== [];
        $flag = 'normal';
        if ($needsClarification) {
            $flag = 'normal';
        } elseif (in_array($booking->id, $winners, true) && $conflicts !== []) {
            $flag = 'unggul';
        } elseif (in_array($booking->id, $losers, true)) {
            $flag = 'rendah';
        }

        return [
            'flag' => $flag,
            'needs_clarification' => $needsClarification,
            'winners' => array_values(array_unique($winners)),
            'losers' => array_values(array_unique($losers)),
            'peers' => array_values(array_unique($peers)),
            'conflicts' => $conflicts,
            'self_priority_order' => $selfOrder,
            'tentative_context' => $isTentativeContext,
            'kontak_klarifikasi' => BookingSetting::getValue('kontak_klarifikasi', '085777183633'),
        ];
    }

    public function suggestPriorityRuleId(Booking $booking): ?int
    {
        $item = $booking->items()->first();
        $eventLevel = $item?->snapshot['event_level'] ?? null;
        $kategori = $booking->kategori_tarif;

        $code = 'umum_komersial';
        if ($kategori === 'pemerintah') {
            $code = match ($eventLevel) {
                'internasional' => 'event_internasional',
                'nasional' => 'event_nasional',
                'provinsi', 'provinsi_kabupaten' => 'event_provinsi',
                'kabupaten' => 'event_kabupaten',
                default => 'instansi_pemerintah_lain',
            };
        }

        return BookingPriorityRule::query()->where('code', $code)->where('is_active', true)->value('id');
    }

    /**
     * Di area tentatif, kegiatan Pemda (pemda_dispora_upt) menang mutlak.
     */
    private function effectivePriorityOrder(Booking $booking, bool $tentativeContext): int
    {
        $order = $booking->priorityRule?->priority_order ?? 999;
        $code = $booking->priorityRule?->code;

        if ($tentativeContext && $code === 'pemda_dispora_upt') {
            return 0;
        }

        return $order;
    }

    /** @param  list<string>  $tentativeCodes */
    private function isTentativeArea(Booking $booking, array $tentativeCodes): bool
    {
        if ($booking->area?->is_tentative) {
            return true;
        }

        $code = $booking->area?->code;

        return $code !== null && in_array($code, $tentativeCodes, true);
    }
}
