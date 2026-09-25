<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\VenuePolicyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class VenueController extends Controller
{
    public function __construct(
        private readonly VenuePolicyService $policies,
        private readonly AvailabilityService $availability,
    ) {}

    public function show(int $id): Response
    {
        $venue = BookingVenue::query()
            ->where('is_active', true)
            ->with([
                'areas' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'facilities' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
            ])
            ->findOrFail($id);

        $tarifs = BookingTarif::query()
            ->where('venue_id', $venue->id)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('uraian')
            ->get()
            ->map(fn (BookingTarif $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'area_id' => $t->area_id,
                'uraian' => $t->uraian,
                'satuan' => $t->satuan,
                'tarif_pemerintah' => $t->tarif_pemerintah,
                'tarif_non_pemerintah' => $t->tarif_non_pemerintah,
                'time_slot' => $t->time_slot,
                'audience_type' => $t->audience_type,
                'day_type' => $t->day_type,
                'vehicle_class' => $t->vehicle_class,
                'event_level' => $t->event_level,
                'category' => $t->category,
                'meta' => $t->meta,
            ]);

        $addons = $venue->addons()
            ->where('booking_addons.is_active', true)
            ->orderBy('booking_addons.sort_order')
            ->get([
                'booking_addons.id',
                'booking_addons.code',
                'booking_addons.name',
                'booking_addons.description',
                'booking_addons.harga',
            ]);

        $terms = $this->policies->termsForVenue($venue->code, $venue->id);

        return Inertia::render('modules/e-booking/Show', [
            'venue' => [
                'id' => $venue->id,
                'code' => $venue->code,
                'name' => $venue->name,
                'description' => $venue->description,
                'cover_url' => $venue->cover_url,
                'areas' => $venue->areas->map(fn ($a) => [
                    'id' => $a->id,
                    'code' => $a->code,
                    'name' => $a->name,
                    'is_tentative' => (bool) $a->is_tentative,
                ]),
                'facilities' => $venue->facilities->map(fn ($f) => [
                    'id' => $f->id,
                    'name' => $f->name,
                    'icon' => $f->icon,
                ]),
            ],
            'tarifs' => $tarifs,
            'addons' => $addons,
            'terms' => $terms,
            'quote' => session('booking_quote'),
            'availability' => session('booking_availability'),
            'oldForm' => [
                'tarif_id' => old('tarif_id'),
                'area_id' => old('area_id'),
                'kategori_tarif' => old('kategori_tarif', 'non_pemerintah'),
                'starts_at' => old('starts_at'),
                'ends_at' => old('ends_at'),
                'qty' => old('qty', 1),
                'luas_m2' => old('luas_m2'),
                'tujuan' => old('tujuan', ''),
                'keterangan' => old('keterangan', ''),
                'addon_ids' => old('addon_ids', []),
            ],
            'branding' => BookingSetting::getValue('branding_name', 'E-Booking') ?? 'E-Booking',
        ]);
    }

    public function daySlots(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'duration_hours' => ['nullable', 'integer', 'min:1', 'max:12'],
            'step_hours' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        try {
            $slots = $this->availability->daySlots([
                'venue_id' => $id,
                'date' => $data['date'],
                'area_id' => $data['area_id'] ?? null,
                'duration_hours' => $data['duration_hours'] ?? 1,
                'step_hours' => $data['step_hours'] ?? 1,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }

    public function monthOverview(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
        ]);

        try {
            $overview = $this->availability->monthOverview([
                'venue_id' => $id,
                'month' => $data['month'],
                'area_id' => $data['area_id'] ?? null,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }
}
