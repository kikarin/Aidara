<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use App\Services\Booking\VenueClosureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class ClosureController extends Controller
{
    public function __construct(
        private readonly VenueClosureService $closures,
    ) {}

    public function index(Request $request): Response
    {
        $venueId = $request->filled('venue_id') ? $request->integer('venue_id') : null;

        $paginator = $this->closures->list([
            'venue_id' => $venueId,
            'active_only' => ! $request->boolean('include_inactive'),
            'per_page' => 20,
        ]);

        $venues = BookingVenue::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name']);

        $areas = BookingArea::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'venue_id', 'code', 'name']);

        return Inertia::render('modules/e-booking/admin/Closures', [
            'closures' => $paginator->through(fn (BookingVenueClosure $c) => [
                'id' => $c->id,
                'venue_id' => $c->venue_id,
                'venue_name' => $c->venue?->name,
                'area_id' => $c->area_id,
                'area_name' => $c->area?->name,
                'starts_at' => optional($c->starts_at)->format('Y-m-d\TH:i'),
                'ends_at' => optional($c->ends_at)->format('Y-m-d\TH:i'),
                'starts_at_label' => optional($c->starts_at)->format('d/m/Y H:i'),
                'ends_at_label' => optional($c->ends_at)->format('d/m/Y H:i'),
                'reason' => $c->reason,
                'is_active' => $c->is_active,
            ]),
            'venues' => $venues,
            'areas' => $areas,
            'filters' => [
                'venue_id' => $venueId ? (string) $venueId : '',
                'include_inactive' => $request->boolean('include_inactive'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->closures->create($data);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.closures.index', array_filter([
                'venue_id' => $data['venue_id'] ?? null,
            ]))
            ->with('success', 'Blok jadwal ditambahkan.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $closure = BookingVenueClosure::query()->findOrFail($id);
        $venueId = $closure->venue_id;
        $this->closures->delete($closure);

        return redirect()
            ->route('e-booking.admin.closures.index', ['venue_id' => $venueId])
            ->with('success', 'Blok jadwal dihapus.');
    }
}
