<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->toDateString();

        $queueStatuses = [
            BookingStatus::MENUNGGU_APPROVAL,
            BookingStatus::PERLU_KLARIFIKASI,
            BookingStatus::AWAITING_PAYMENT,
            BookingStatus::APPROVED,
        ];

        $menunggu = Booking::query()->where('status', BookingStatus::MENUNGGU_APPROVAL)->count();
        $klarifikasi = Booking::query()->where('status', BookingStatus::PERLU_KLARIFIKASI)->count();
        $awaitingPayment = Booking::query()->where('status', BookingStatus::AWAITING_PAYMENT)->count();
        $confirmed = Booking::query()->where('status', BookingStatus::CONFIRMED)->count();
        $paid = Booking::query()->where('status', BookingStatus::PAID)->count();
        $rejected = Booking::query()->where('status', BookingStatus::REJECTED)->count();
        $queue = Booking::query()->whereIn('status', $queueStatuses)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'pending_approval' => $menunggu + $klarifikasi,
                'awaiting_payment' => $awaitingPayment,
                'awaiting_verification' => Booking::query()
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->whereHas('payments', fn ($q) => $q->where('status', 'awaiting_verification'))
                    ->count(),
                'confirmed_today' => Booking::query()
                    ->where('status', BookingStatus::CONFIRMED)
                    ->whereDate('confirmed_at', $today)
                    ->count(),
                'paid_or_confirmed' => Booking::query()
                    ->whereIn('status', [BookingStatus::PAID, BookingStatus::CONFIRMED])
                    ->count(),
                'by_status' => [
                    '' => $queue,
                    'menunggu_approval' => $menunggu,
                    'perlu_klarifikasi' => $klarifikasi,
                    'awaiting_payment' => $awaitingPayment,
                    'confirmed' => $confirmed,
                    'paid' => $paid,
                    'rejected' => $rejected,
                ],
            ],
        ]);
    }
}
