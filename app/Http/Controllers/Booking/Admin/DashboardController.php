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

        return response()->json([
            'success' => true,
            'data' => [
                'pending_approval' => Booking::query()
                    ->whereIn('status', [BookingStatus::MENUNGGU_APPROVAL, BookingStatus::PERLU_KLARIFIKASI])
                    ->count(),
                'awaiting_payment' => Booking::query()
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->count(),
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
            ],
        ]);
    }
}
