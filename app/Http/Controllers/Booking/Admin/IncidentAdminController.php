<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\Admin\RecordChargeRequest;
use App\Http\Requests\Booking\Admin\RecordRainRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking\Booking;
use App\Services\Booking\VenuePolicyService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class IncidentAdminController extends Controller
{
    public function __construct(
        private readonly VenuePolicyService $policies,
    ) {}

    public function rain(RecordRainRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->policies->recordRain(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['decision'] === 'reschedule'
                ? 'Hujan dicatat — booking masuk reschedule_pending'
                : 'Hujan dicatat — tidak ada kompensasi ganti lapangan',
            'data' => [
                'decision' => $result['decision'],
                'incident' => $result['incident'],
                'booking' => new BookingResource($result['booking']),
            ],
        ]);
    }

    public function charge(RecordChargeRequest $request, int $id): JsonResponse
    {
        try {
            $incident = $this->policies->recordAdminCharge(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Incident/charge dicatat',
            'data' => $incident,
        ], 201);
    }

    public function reschedule(RescheduleBookingRequest $request, int $id): JsonResponse
    {
        try {
            $booking = $this->policies->applyReschedule(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal booking diperbarui',
            'data' => new BookingResource($booking),
        ]);
    }

    public function cancel(\App\Http\Requests\Booking\CancelBookingRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->policies->cancel(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['outcome'] === 'forfeited'
                ? 'Pembatalan hari H — booking hangus (forfeited)'
                : 'Booking dibatalkan',
            'data' => [
                'outcome' => $result['outcome'],
                'booking' => new BookingResource($result['booking']),
            ],
        ]);
    }
}
