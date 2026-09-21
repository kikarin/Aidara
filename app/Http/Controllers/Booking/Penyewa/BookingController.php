<?php

namespace App\Http\Controllers\Booking\Penyewa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CancelBookingRequest;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UploadBuktiBayarRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking\Booking;
use App\Services\Booking\BookingPaymentService;
use App\Services\Booking\BookingSubmitService;
use App\Services\Booking\PricingService;
use App\Services\Booking\VenuePolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly BookingSubmitService $submitter,
        private readonly BookingPaymentService $payments,
        private readonly VenuePolicyService $policies,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['venue:id,code,name', 'area:id,code,name', 'items', 'addonSelected'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings)->response()->getData(true),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['venue', 'area', 'items', 'addonSelected', 'payments', 'statusLogs'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    public function quote(QuoteBookingRequest $request): JsonResponse
    {
        try {
            $quote = $this->pricing->quote($request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $quote,
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->submitter->submit($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil disubmit, menunggu approval',
            'data' => new BookingResource($booking),
        ], 201);
    }

    public function paymentInfo(Request $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['payments' => fn ($q) => $q->latest('id')])
            ->findOrFail($id);

        $payment = $booking->payments->first();

        return response()->json([
            'success' => true,
            'data' => [
                'booking_status' => $booking->status,
                'grand_total' => $booking->grand_total,
                'payment' => $payment ? [
                    'id' => $payment->id,
                    'gateway' => $payment->gateway,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'bank' => $payment->bank,
                    'rekening' => $payment->rekening,
                    'atas_nama' => $payment->atas_nama,
                    'bukti_path' => $payment->bukti_path,
                    'bukti_url' => $payment->bukti_path ? Storage::disk('public')->url($payment->bukti_path) : null,
                    'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                    'expires_at' => $payment->meta['expires_at'] ?? null,
                    'expire_hours' => $payment->meta['expire_hours'] ?? null,
                ] : null,
            ],
        ]);
    }

    public function uploadBukti(UploadBuktiBayarRequest $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $payment = $this->payments->uploadBukti(
                $booking,
                $request->user(),
                $request->file('bukti'),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bukti transfer diunggah, menunggu verifikasi admin',
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'bukti_url' => Storage::disk('public')->url($payment->bukti_path),
                'amount' => $payment->amount,
            ],
        ]);
    }

    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $result = $this->policies->cancel(
                $booking,
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

    public function reschedule(RescheduleBookingRequest $request, int $id): JsonResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $booking = $this->policies->applyReschedule(
                $booking,
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal diperbarui',
            'data' => new BookingResource($booking),
        ]);
    }
}
