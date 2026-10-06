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

        $batchCounts = collect($paginator->items())
            ->pluck('batch_id')
            ->filter()
            ->unique()
            ->values();

        $batchCounts = $batchCounts->isEmpty()
            ? collect()
            : BookingVenueClosure::query()
                ->whereIn('batch_id', $batchCounts)
                ->selectRaw('batch_id, COUNT(*) as aggregate')
                ->groupBy('batch_id')
                ->pluck('aggregate', 'batch_id');

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
                'batch_id' => $c->batch_id,
                'batch_size' => $c->batch_id ? (int) ($batchCounts[$c->batch_id] ?? 1) : null,
                'is_full_day' => (bool) $c->is_full_day,
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
        $mode = $request->input('mode', 'once') === 'weekly' ? 'weekly' : 'once';
        $fullDay = $request->boolean('full_day');

        $rules = [
            'mode' => ['nullable', 'in:once,weekly'],
            'full_day' => ['nullable', 'boolean'],
            'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];

        if ($mode === 'weekly') {
            $rules += [
                'weekdays' => ['required', 'array', 'min:1'],
                'weekdays.*' => ['integer', 'between:0,6'],
                'start_date' => ['required', 'date'],
                'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            ];

            if (! $fullDay) {
                $rules += [
                    'start_time' => ['required', 'date_format:H:i'],
                    'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
                ];
            }
        } elseif ($fullDay) {
            $rules += ['date' => ['required', 'date']];
        } else {
            $rules += [
                'starts_at' => ['required', 'date'],
                'ends_at' => ['required', 'date', 'after:starts_at'],
            ];
        }

        $data = $request->validate($rules);
        $data['full_day'] = $fullDay;
        $message = '';

        try {
            if ($mode === 'weekly') {
                $result = $this->closures->createRecurring($data);
                $message = "Blok berulang ditambahkan ({$result['created']} tanggal).";
            } else {
                $this->closures->create($data);
                $message = 'Blok jadwal ditambahkan.';
            }
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.closures.index', array_filter([
                'venue_id' => $data['venue_id'] ?? null,
            ]))
            ->with('success', $message);
    }

    public function destroyBatch(string $batchId): RedirectResponse
    {
        $closure = BookingVenueClosure::query()->where('batch_id', $batchId)->firstOrFail();
        $venueId = $closure->venue_id;
        $deleted = $this->closures->deleteBatch($batchId);

        return redirect()
            ->route('e-booking.admin.closures.index', ['venue_id' => $venueId])
            ->with('success', "Blok berulang dihapus ({$deleted} tanggal).");
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
