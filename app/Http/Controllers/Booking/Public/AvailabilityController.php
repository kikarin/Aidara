<?php

namespace App\Http\Controllers\Booking\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckAvailabilityRequest;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\PricingService;
use App\Services\Booking\VenuePolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly VenuePolicyService $policies,
    ) {}

    public function venues(): JsonResponse
    {
        $venues = BookingVenue::query()
            ->where('is_active', true)
            ->with(['areas' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description', 'sort_order']);

        return response()->json([
            'success' => true,
            'data' => $venues,
        ]);
    }

    public function areas(int $venueId): JsonResponse
    {
        $areas = BookingArea::query()
            ->where('venue_id', $venueId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'venue_id', 'code', 'name', 'is_tentative', 'sort_order']);

        return response()->json([
            'success' => true,
            'data' => $areas,
        ]);
    }

    public function tarifs(Request $request): JsonResponse
    {
        $query = BookingTarif::query()
            ->with(['venue:id,code,name', 'area:id,code,name'])
            ->where('is_active', true);

        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->integer('venue_id'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->integer('area_id'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->filled('satuan')) {
            $query->where('satuan', $request->string('satuan'));
        }

        $tarifs = $query->orderBy('uraian')->paginate(min(100, $request->integer('per_page', 50)));

        return response()->json([
            'success' => true,
            'data' => $tarifs,
        ]);
    }

    public function addons(): JsonResponse
    {
        $addons = BookingAddon::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'description', 'harga']);

        return response()->json([
            'success' => true,
            'data' => $addons,
        ]);
    }

    public function settings(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'payment_mode' => BookingSetting::getValue('payment_mode'),
                'rekening_transfer' => BookingSetting::getValue('rekening_transfer'),
                'kontak_klarifikasi' => BookingSetting::getValue('kontak_klarifikasi'),
                'branding_name' => BookingSetting::getValue('branding_name', 'E-Booking'),
            ],
        ]);
    }

    public function check(CheckAvailabilityRequest $request): JsonResponse
    {
        try {
            $result = $this->availability->check($request->validated());
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
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

    public function terms(Request $request): JsonResponse
    {
        $data = $this->policies->termsForVenue(
            $request->query('venue_code'),
            $request->filled('venue_id') ? $request->integer('venue_id') : null
        );

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
