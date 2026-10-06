<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\Admin\RejectPaymentRequest;
use App\Http\Requests\Booking\Admin\VerifyPaymentRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking\BookingPayment;
use App\Services\Booking\BookingPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $payments,
    ) {}

    public function verify(VerifyPaymentRequest $request, int $paymentId): JsonResponse
    {
        try {
            $result = $this->payments->verify(
                BookingPayment::query()->findOrFail($paymentId),
                $request->user(),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran diverifikasi, booking confirmed',
            'data' => [
                'booking' => new BookingResource($result['booking']),
                'payment' => $this->presentPayment($result['payment']),
            ],
        ]);
    }

    public function reject(RejectPaymentRequest $request, int $paymentId): JsonResponse
    {
        try {
            $payment = $this->payments->rejectBukti(
                BookingPayment::query()->findOrFail($paymentId),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bukti ditolak, penyewa dapat upload ulang',
            'data' => $this->presentPayment($payment),
        ]);
    }

    /** @return array<string, mixed> */
    private function presentPayment(BookingPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'gateway' => $payment->gateway,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'bank' => $payment->bank,
            'rekening' => $payment->rekening,
            'atas_nama' => $payment->atas_nama,
            'bukti_path' => $payment->bukti_path,
            'bukti_url' => $payment->bukti_path ? Storage::disk('public')->url($payment->bukti_path) : null,
            'paid_at' => optional($payment->paid_at)->toDateTimeString(),
            'verified_at' => optional($payment->verified_at)->toDateTimeString(),
            'notes' => $payment->notes,
        ];
    }
}
