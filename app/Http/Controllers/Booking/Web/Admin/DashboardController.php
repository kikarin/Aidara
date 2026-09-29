<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\Booking;
use App\Support\Booking\BookingStatus;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('modules/e-booking/admin/Dashboard', [
            'stats' => Inertia::defer(function () {
                $today = now()->toDateString();

                $pendingApproval = Booking::query()
                    ->whereIn('status', [
                        BookingStatus::MENUNGGU_APPROVAL,
                        BookingStatus::PERLU_KLARIFIKASI,
                    ])
                    ->count();

                $awaitingPayment = Booking::query()
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->count();

                $awaitingVerification = Booking::query()
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->whereHas('payments', fn ($q) => $q->where('status', 'awaiting_verification'))
                    ->count();

                $confirmedToday = Booking::query()
                    ->where('status', BookingStatus::CONFIRMED)
                    ->whereDate('confirmed_at', $today)
                    ->count();

                return [
                    'pending_approval' => $pendingApproval,
                    'awaiting_payment' => $awaitingPayment,
                    'awaiting_verification' => $awaitingVerification,
                    'confirmed_today' => $confirmedToday,
                    'needs_attention' => $pendingApproval + $awaitingVerification,
                ];
            }),
            'recent' => Inertia::defer(function () {
                return Booking::query()
                    ->with(['venue:id,code,name', 'user:id,name,email', 'penyewaProfile:id,nama,no_hp'])
                    ->whereIn('status', [
                        BookingStatus::MENUNGGU_APPROVAL,
                        BookingStatus::PERLU_KLARIFIKASI,
                        BookingStatus::AWAITING_PAYMENT,
                    ])
                    ->latest('id')
                    ->limit(8)
                    ->get()
                    ->map(fn (Booking $b) => [
                        'id' => $b->id,
                        'nomor' => $b->nomor,
                        'status' => $b->status,
                        'venue' => $b->venue?->name,
                        'penyewa' => $b->penyewaProfile?->nama ?? $b->user?->name,
                        'starts_at' => optional($b->starts_at)?->format('Y-m-d H:i'),
                        'ends_at' => optional($b->ends_at)?->format('Y-m-d H:i'),
                        'grand_total' => (int) $b->grand_total,
                    ]);
            }),
        ]);
    }
}
