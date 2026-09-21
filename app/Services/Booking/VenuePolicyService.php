<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingIncident;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingVenue;
use App\Models\User;
use App\Support\Booking\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Policy operasional per venue (seed di booking_rules).
 * Utamanya Tennis Kapten Muslihat — Step 5.
 */
class VenuePolicyService
{
    public function __construct(
        private readonly RulesEngine $rules,
        private readonly BookingStatusService $statuses,
        private readonly AvailabilityService $availability,
    ) {}

    public function assertOperatingHours(int $venueId, Carbon $startsAt, Carbon $endsAt): void
    {
        $hours = $this->rules->get('operating_hours', $venueId);
        if (! is_array($hours) || empty($hours['start']) || empty($hours['end'])) {
            return;
        }

        $startTime = $startsAt->format('H:i');
        $endTime = $endsAt->format('H:i');
        $open = $hours['start'];
        $close = $hours['end'];

        if ($startsAt->toDateString() !== $endsAt->toDateString()) {
            throw new InvalidArgumentException('Slot harus dalam satu hari kalender sesuai jam operasional.');
        }

        if ($startTime < $open || $endTime > $close) {
            throw new InvalidArgumentException(
                "Slot harus dalam jam operasional {$open}–{$close}."
            );
        }
    }

    /**
     * @return array{booking: Booking, outcome: string}
     */
    public function cancel(Booking $booking, User $actor, ?string $reason = null): array
    {
        $cancellable = [
            BookingStatus::MENUNGGU_APPROVAL,
            BookingStatus::PERLU_KLARIFIKASI,
            BookingStatus::APPROVED,
            BookingStatus::AWAITING_PAYMENT,
            BookingStatus::PAID,
            BookingStatus::CONFIRMED,
            BookingStatus::RESCHEDULE_PENDING,
        ];

        if (! in_array($booking->status, $cancellable, true)) {
            throw new InvalidArgumentException("Booking status {$booking->status} tidak bisa dibatalkan.");
        }

        if ($actor->id !== $booking->user_id && ! $actor->hasRole('admin_upt')) {
            throw new InvalidArgumentException('Tidak berhak membatalkan booking ini.');
        }

        $starts = Carbon::parse($booking->starts_at)->startOfDay();
        $today = now()->startOfDay();

        if ($today->gt($starts)) {
            throw new InvalidArgumentException('Tidak bisa membatalkan booking yang sudah lewat.');
        }

        $isDayH = $today->equalTo($starts);
        $hardLocked = in_array($booking->status, [
            BookingStatus::CONFIRMED,
            BookingStatus::PAID,
            BookingStatus::RESCHEDULE_PENDING,
        ], true);

        $outcome = ($isDayH && $hardLocked)
            ? BookingStatus::FORFEITED
            : BookingStatus::CANCELLED;

        $note = $reason ?? ($outcome === BookingStatus::FORFEITED
            ? 'Pembatalan hari H — hangus (forfeited)'
            : 'Dibatalkan ≤ H-1');

        return DB::transaction(function () use ($booking, $actor, $note, $outcome) {
            $booking = $this->statuses->transition($booking, $outcome, $note, $actor->id);
            $booking->forceFill(['cancelled_at' => now()])->save();

            return [
                'booking' => $booking->fresh(['venue', 'area', 'statusLogs']),
                'outcome' => $outcome,
            ];
        });
    }

    /**
     * @param  array{play_elapsed_minutes?: int|null, play_started_at?: string|null, notes?: string|null}  $input
     * @return array{incident: BookingIncident, booking: Booking, decision: string}
     */
    public function recordRain(Booking $booking, User $admin, array $input): array
    {
        if (! in_array($booking->status, [BookingStatus::CONFIRMED, BookingStatus::RESCHEDULE_PENDING], true)) {
            throw new InvalidArgumentException('Incident hujan hanya untuk booking confirmed / reschedule_pending.');
        }

        $threshold = (int) ($this->rules->get('force_majeure_rain_after_play_minutes', $booking->venue_id, 20) ?? 20);
        $elapsed = $input['play_elapsed_minutes'] ?? null;

        if ($elapsed === null && ! empty($input['play_started_at'])) {
            $elapsed = (int) Carbon::parse($input['play_started_at'])->diffInMinutes(now());
        }

        $elapsed = $elapsed === null ? 0 : (int) $elapsed;
        $isReschedule = $elapsed < $threshold;

        return DB::transaction(function () use ($booking, $admin, $input, $elapsed, $isReschedule, $threshold) {
            $incident = BookingIncident::query()->create([
                'booking_id' => $booking->id,
                'type' => 'rain',
                'play_elapsed_minutes' => $elapsed,
                'play_started_at' => $input['play_started_at'] ?? null,
                'decision' => $isReschedule ? 'reschedule' : 'no_compensation',
                'notes' => $input['notes'] ?? null,
                'resolved_by' => $admin->id,
                'resolved_at' => now(),
                'meta' => ['threshold_minutes' => $threshold],
            ]);

            $toStatus = $isReschedule
                ? BookingStatus::RESCHEDULE_PENDING
                : BookingStatus::NO_COMPENSATION;

            $note = $isReschedule
                ? "Force majeure hujan (< {$threshold} menit) — berhak ganti jadwal"
                : "Hujan setelah ≥ {$threshold} menit bermain — tidak ada ganti lapangan";

            $booking = $this->statuses->transition($booking, $toStatus, $note, $admin->id);

            return [
                'incident' => $incident,
                'booking' => $booking->fresh(['incidents', 'venue', 'area']),
                'decision' => $incident->decision,
            ];
        });
    }

    /**
     * @param  array{starts_at: string, ends_at: string, area_id?: int|null}  $input
     */
    public function applyReschedule(Booking $booking, User $actor, array $input): Booking
    {
        $newStart = Carbon::parse($input['starts_at']);
        $newEnd = Carbon::parse($input['ends_at']);
        $areaId = array_key_exists('area_id', $input) ? $input['area_id'] : $booking->area_id;

        $isForceMajeure = $booking->status === BookingStatus::RESCHEDULE_PENDING;
        $allowSameDay = (bool) $this->rules->get('allow_same_day_reschedule', $booking->venue_id, false);
        $allowCourtChange = (bool) $this->rules->get('allow_same_day_court_change', $booking->venue_id, false);

        $originalDay = Carbon::parse($booking->starts_at)->toDateString();
        $isSameDayAsOriginal = now()->toDateString() === $originalDay;

        if (! $isForceMajeure) {
            if ($isSameDayAsOriginal && ! $allowSameDay) {
                throw new InvalidArgumentException(
                    'Tidak diperkenankan ganti jadwal pada hari H. Buat booking baru dan bayar ulang.'
                );
            }

            if ($isSameDayAsOriginal && (int) $areaId !== (int) $booking->area_id && ! $allowCourtChange) {
                throw new InvalidArgumentException(
                    'Tidak diperkenankan ganti lapangan pada hari H. Buat booking baru dan bayar ulang.'
                );
            }

            if (! in_array($booking->status, [
                BookingStatus::CONFIRMED,
                BookingStatus::APPROVED,
                BookingStatus::AWAITING_PAYMENT,
            ], true)) {
                throw new InvalidArgumentException('Status booking tidak mengizinkan reschedule.');
            }
        }

        if ($actor->id !== $booking->user_id && ! $actor->hasRole('admin_upt')) {
            throw new InvalidArgumentException('Tidak berhak mengubah jadwal booking ini.');
        }

        $this->assertOperatingHours($booking->venue_id, $newStart, $newEnd);

        $this->availability->assertBookable([
            'venue_id' => $booking->venue_id,
            'area_id' => $areaId,
            'starts_at' => $newStart,
            'ends_at' => $newEnd,
            'exclude_booking_id' => $booking->id,
        ]);

        return DB::transaction(function () use ($booking, $actor, $newStart, $newEnd, $areaId, $isForceMajeure) {
            $old = [
                'starts_at' => optional($booking->starts_at)->toDateTimeString(),
                'ends_at' => optional($booking->ends_at)->toDateTimeString(),
                'area_id' => $booking->area_id,
            ];

            $booking->forceFill([
                'starts_at' => $newStart,
                'ends_at' => $newEnd,
                'area_id' => $areaId,
                'meta' => array_merge($booking->meta ?? [], [
                    'last_reschedule' => [
                        'from' => $old,
                        'by' => $actor->id,
                        'at' => now()->toDateTimeString(),
                        'force_majeure' => $isForceMajeure,
                    ],
                ]),
            ])->save();

            if ($isForceMajeure) {
                $booking = $this->statuses->transition(
                    $booking,
                    BookingStatus::CONFIRMED,
                    'Jadwal diganti setelah force majeure hujan',
                    $actor->id
                );
                $booking->forceFill(['confirmed_at' => now()])->save();
            }

            return $booking->fresh(['venue', 'area', 'statusLogs']);
        });
    }

    /**
     * @param  array{type: string, amount?: int|null, notes?: string|null, meta?: array}  $input
     */
    public function recordAdminCharge(Booking $booking, User $admin, array $input): BookingIncident
    {
        $type = $input['type'];
        $allowed = ['penalty_overtime', 'adjacent_court', 'damage'];
        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Tipe incident tidak valid.');
        }

        if ($type === 'adjacent_court') {
            $flag = $this->rules->get('adjacent_empty_court_counts_as_rental', $booking->venue_id, true);
            if (! $flag) {
                throw new InvalidArgumentException('Aturan lapangan sebelah tidak aktif untuk venue ini.');
            }
        }

        return BookingIncident::query()->create([
            'booking_id' => $booking->id,
            'type' => $type,
            'decision' => 'charge',
            'notes' => $input['notes'] ?? null,
            'resolved_by' => $admin->id,
            'resolved_at' => now(),
            'meta' => array_merge($input['meta'] ?? [], [
                'amount' => $input['amount'] ?? null,
                'amount_tbd' => ! isset($input['amount']),
            ]),
        ]);
    }

    /** @return array<string, mixed> */
    public function termsForVenue(?string $venueCode = null, ?int $venueId = null): array
    {
        $venue = null;
        if ($venueId) {
            $venue = BookingVenue::query()->find($venueId);
        } elseif ($venueCode) {
            $venue = BookingVenue::query()->where('code', $venueCode)->first();
        }

        $termsKey = $venue
            ? ((string) ($this->rules->get('terms_key', $venue->id, 'terms_tennis') ?? 'terms_tennis'))
            : 'terms_tennis';

        $terms = BookingSetting::getValue($termsKey);
        $prefer = $venue ? $this->rules->get('prefer_booking_on_weekday', $venue->id) : null;

        return [
            'venue' => $venue?->only(['id', 'code', 'name']),
            'terms' => $terms,
            'reminders' => [
                'prefer_booking_on_weekday' => $prefer,
                'prefer_booking_copy' => $prefer === 'monday'
                    ? 'Disarankan melakukan pemesanan setiap hari Senin.'
                    : null,
                'early_arrival_minutes_min' => $venue ? $this->rules->get('early_arrival_minutes_min', $venue->id, 15) : 15,
                'early_arrival_minutes_max' => $venue ? $this->rules->get('early_arrival_minutes_max', $venue->id, 30) : 30,
                'leave_court_after_minutes' => $venue ? $this->rules->get('leave_court_after_minutes', $venue->id, 15) : 15,
            ],
            'policies' => $venue ? [
                'booking_horizon_days' => $this->rules->get('booking_horizon_days', $venue->id),
                'operating_hours' => $this->rules->get('operating_hours', $venue->id),
                'cancel_deadline' => $this->rules->get('cancel_deadline', $venue->id),
                'cancel_on_day_h' => $this->rules->get('cancel_on_day_h', $venue->id),
                'allow_same_day_reschedule' => $this->rules->get('allow_same_day_reschedule', $venue->id, false),
                'allow_same_day_court_change' => $this->rules->get('allow_same_day_court_change', $venue->id, false),
                'force_majeure_rain_after_play_minutes' => $this->rules->get('force_majeure_rain_after_play_minutes', $venue->id, 20),
                'tentative_areas' => $this->rules->get('tentative_areas', $venue->id, []),
                'advance_payment_required' => $this->rules->get('advance_payment_required', $venue->id, true),
            ] : null,
        ];
    }
}
