<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingPriorityRule;
use App\Models\User;
use App\Services\Booking\Payment\PaymentGatewayFactory;
use App\Support\Booking\BookingStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdminApprovalService
{
    public function __construct(
        private readonly BookingStatusService $statuses,
        private readonly ConflictResolver $conflicts,
        private readonly PaymentGatewayFactory $gateways,
    ) {}

    /**
     * @return array{booking: Booking, conflict: array<string, mixed>, payment: ?BookingPayment}
     */
    public function approve(Booking $booking, User $admin, array $options = []): array
    {
        $this->assertReviewable($booking);

        if (! empty($options['priority_rule_id'])) {
            $rule = BookingPriorityRule::query()->where('is_active', true)->findOrFail($options['priority_rule_id']);
            $booking->priority_rule_id = $rule->id;
            $booking->save();
        } elseif (! $booking->priority_rule_id) {
            $booking->priority_rule_id = $this->conflicts->suggestPriorityRuleId($booking);
            $booking->save();
        }

        $conflict = $this->conflicts->resolve($booking->id);
        $force = (bool) ($options['force'] ?? false);

        if ($conflict['needs_clarification'] && ! $force) {
            $booking = $this->markKlarifikasi(
                $booking,
                $admin,
                'Bentrok tingkat prioritas sama. Hubungi admin: '.$conflict['kontak_klarifikasi']
            );

            return [
                'booking' => $booking->load(['priorityRule', 'payments', 'venue', 'area', 'items']),
                'conflict' => $conflict,
                'payment' => null,
            ];
        }

        return DB::transaction(function () use ($booking, $admin, $conflict, $options, $force) {
            $flag = $conflict['flag'];
            if ($force && $conflict['needs_clarification']) {
                $flag = 'unggul';
            }

            $booking->priority_flag = $flag;
            $booking->admin_notes = $options['admin_notes'] ?? $booking->admin_notes;
            $booking->approved_at = now();
            $booking->save();

            // Tandai peer/loser flags (informasi saja, bukan auto-reject)
            if ($flag === 'unggul') {
                Booking::query()->whereIn('id', $conflict['losers'])->update(['priority_flag' => 'rendah']);
            }

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::APPROVED,
                $options['note'] ?? 'Disetujui admin UPT',
                $admin->id
            );

            $payment = $this->gateways->resolve()->initiate($booking, [
                'initiated_by' => $admin->id,
            ]);

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::AWAITING_PAYMENT,
                'Menunggu transfer manual ke rekening RKUD',
                $admin->id
            );

            return [
                'booking' => $booking->load(['priorityRule', 'payments', 'venue', 'area', 'items', 'addonSelected']),
                'conflict' => $conflict,
                'payment' => $payment,
            ];
        });
    }

    public function reject(Booking $booking, User $admin, string $reason): Booking
    {
        $this->assertReviewable($booking);

        return DB::transaction(function () use ($booking, $admin, $reason) {
            $booking->forceFill([
                'rejected_at' => now(),
                'admin_notes' => $reason,
            ])->save();

            return $this->statuses->transition(
                $booking,
                BookingStatus::REJECTED,
                $reason,
                $admin->id
            )->load(['venue', 'area', 'items']);
        });
    }

    public function markKlarifikasi(Booking $booking, User $admin, ?string $note = null): Booking
    {
        $this->assertReviewable($booking);

        $conflict = $this->conflicts->resolve($booking->id);
        $message = $note ?? ('Perlu klarifikasi konflik. Hubungi: '.$conflict['kontak_klarifikasi']);

        $booking->priority_flag = 'normal';
        $booking->admin_notes = $message;
        $booking->save();

        return $this->statuses->transition(
            $booking,
            BookingStatus::PERLU_KLARIFIKASI,
            $message,
            $admin->id
        )->load(['priorityRule', 'venue', 'area', 'items']);
    }

    public function analyze(Booking $booking): array
    {
        if (! $booking->priority_rule_id) {
            $booking->priority_rule_id = $this->conflicts->suggestPriorityRuleId($booking);
            $booking->save();
        }

        return $this->conflicts->resolve($booking->id);
    }

    private function assertReviewable(Booking $booking): void
    {
        if (! in_array($booking->status, [
            BookingStatus::MENUNGGU_APPROVAL,
            BookingStatus::PERLU_KLARIFIKASI,
        ], true)) {
            throw new InvalidArgumentException(
                "Booking status {$booking->status} tidak bisa di-review."
            );
        }
    }
}
