<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingStatusLog;
use App\Support\Booking\BookingStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingStatusService
{
    /** @var array<string, list<string>> */
    private array $transitions = [
        BookingStatus::DRAFT => [BookingStatus::MENUNGGU_APPROVAL, BookingStatus::CANCELLED],
        BookingStatus::MENUNGGU_APPROVAL => [
            BookingStatus::APPROVED,
            BookingStatus::PERLU_KLARIFIKASI,
            BookingStatus::REJECTED,
            BookingStatus::CANCELLED,
        ],
        BookingStatus::PERLU_KLARIFIKASI => [
            BookingStatus::APPROVED,
            BookingStatus::REJECTED,
            BookingStatus::CANCELLED,
        ],
        BookingStatus::APPROVED => [BookingStatus::AWAITING_PAYMENT, BookingStatus::CANCELLED],
        BookingStatus::AWAITING_PAYMENT => [
            BookingStatus::PAID,
            BookingStatus::EXPIRED,
            BookingStatus::CANCELLED,
        ],
        BookingStatus::PAID => [BookingStatus::CONFIRMED, BookingStatus::CANCELLED],
        BookingStatus::CONFIRMED => [
            BookingStatus::COMPLETED,
            BookingStatus::CANCELLED,
            BookingStatus::RESCHEDULE_PENDING,
            BookingStatus::NO_COMPENSATION,
            BookingStatus::FORFEITED,
        ],
        BookingStatus::RESCHEDULE_PENDING => [BookingStatus::CONFIRMED, BookingStatus::CANCELLED, BookingStatus::FORFEITED],
        BookingStatus::NO_COMPENSATION => [BookingStatus::COMPLETED],
    ];

    public function transition(Booking $booking, string $toStatus, ?string $note = null, ?int $changedBy = null): Booking
    {
        if (! in_array($toStatus, BookingStatus::all(), true)) {
            throw new InvalidArgumentException("Status tidak dikenal: {$toStatus}");
        }

        $from = $booking->status;
        if ($from !== $toStatus) {
            $allowed = $this->transitions[$from] ?? [];
            if ($allowed !== [] && ! in_array($toStatus, $allowed, true)) {
                throw new InvalidArgumentException("Transisi status {$from} → {$toStatus} tidak diizinkan.");
            }
        }

        return DB::transaction(function () use ($booking, $toStatus, $note, $changedBy, $from) {
            $booking->status = $toStatus;
            $booking->save();

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => $from,
                'to_status' => $toStatus,
                'note' => $note,
                'changed_by' => $changedBy,
                'created_at' => now(),
            ]);

            return $booking->refresh();
        });
    }

    /** @return list<string> */
    public function allowedStatuses(): array
    {
        return BookingStatus::all();
    }
}
