<?php

namespace App\Http\Controllers;

use App\Models\Booking\BookingVenue;
use App\Services\WorldCup\WorldCupService;
use App\Support\WorldCupFeature;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(WorldCupService $worldCupService): Response
    {
        $payload = [
            'bookingPreview' => Inertia::defer(fn () => $this->bookingPreview()),
        ];

        if (WorldCupFeature::showOnLanding()) {
            $payload['worldcupPreview'] = $worldCupService->getLandingPreview();
        }

        return Inertia::render('Welcome', $payload);
    }

    /**
     * @return array{total: int, venues: \Illuminate\Support\Collection<int, array<string, mixed>>}
     */
    private function bookingPreview(): array
    {
        $query = BookingVenue::query()->where('is_active', true);

        return [
            'total' => (clone $query)->count(),
            'venues' => $query
                ->withCount([
                    'areas' => fn ($q) => $q->where('is_active', true),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(4)
                ->get(['id', 'code', 'name', 'description', 'cover_path', 'sort_order'])
                ->map(fn (BookingVenue $v) => [
                    'id' => $v->id,
                    'code' => $v->code,
                    'name' => $v->name,
                    'description' => $v->description,
                    'cover_url' => $v->cover_url,
                    'areas_count' => (int) $v->areas_count,
                ]),
        ];
    }
}
