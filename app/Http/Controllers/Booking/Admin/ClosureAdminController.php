<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingVenueClosure;
use App\Services\Booking\VenueClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ClosureAdminController extends Controller
{
    public function __construct(
        private readonly VenueClosureService $closures,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->closures->list([
            'venue_id' => $request->filled('venue_id') ? $request->integer('venue_id') : null,
            'area_id' => $request->filled('area_id') ? $request->integer('area_id') : null,
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'active_only' => ! $request->boolean('include_inactive'),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        try {
            $closure = $this->closures->create($data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $closure->load(['venue:id,code,name', 'area:id,code,name']),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $closure = BookingVenueClosure::query()->findOrFail($id);
        $data = $request->validate([
            'venue_id' => ['sometimes', 'integer', 'exists:booking_venues,id'],
            'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        try {
            $updated = $this->closures->update($closure, $data);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $updated]);
    }

    public function destroy(int $id): JsonResponse
    {
        $closure = BookingVenueClosure::query()->findOrFail($id);
        $this->closures->delete($closure);

        return response()->json(['success' => true, 'message' => 'Closure dihapus.']);
    }
}
