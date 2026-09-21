<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\Admin\ApproveBookingRequest;
use App\Http\Requests\Booking\Admin\RejectBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking\Booking;
use App\Services\Booking\AdminApprovalService;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class BookingAdminController extends Controller
{
    public function __construct(
        private readonly AdminApprovalService $approval,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Booking::query()
            ->with(['venue:id,code,name', 'area:id,code,name', 'user:id,name,email', 'priorityRule', 'payments'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            $query->whereIn('status', [
                BookingStatus::MENUNGGU_APPROVAL,
                BookingStatus::PERLU_KLARIFIKASI,
                BookingStatus::AWAITING_PAYMENT,
                BookingStatus::APPROVED,
            ]);
        }

        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->integer('venue_id'));
        }

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($query->paginate(20))->response()->getData(true),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $booking = Booking::query()
            ->with(['venue', 'area', 'user', 'penyewaProfile.documents', 'items', 'addonSelected', 'payments', 'statusLogs', 'priorityRule'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => new BookingResource($booking),
                'conflict' => $this->approval->analyze($booking),
            ],
        ]);
    }

    public function conflict(int $id): JsonResponse
    {
        $booking = Booking::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->approval->analyze($booking),
        ]);
    }

    public function approve(ApproveBookingRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->approval->approve(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $message = $result['booking']->status === BookingStatus::PERLU_KLARIFIKASI
            ? 'Booking masuk perlu klarifikasi karena konflik prioritas sama'
            : 'Booking disetujui, menunggu pembayaran';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'booking' => new BookingResource($result['booking']),
                'conflict' => $result['conflict'],
                'payment' => $result['payment'],
            ],
        ]);
    }

    public function reject(RejectBookingRequest $request, int $id): JsonResponse
    {
        try {
            $booking = $this->approval->reject(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking ditolak',
            'data' => new BookingResource($booking),
        ]);
    }

    public function klarifikasi(RejectBookingRequest $request, int $id): JsonResponse
    {
        try {
            $booking = $this->approval->markKlarifikasi(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking ditandai perlu klarifikasi',
            'data' => new BookingResource($booking),
        ]);
    }
}
