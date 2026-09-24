<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingVenue;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('modules/e-booking/Catalog', [
            'venues' => Inertia::defer(fn () => BookingVenue::query()
                ->where('is_active', true)
                ->withCount([
                    'areas' => fn ($q) => $q->where('is_active', true),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'description', 'cover_path', 'sort_order'])
                ->map(fn (BookingVenue $v) => [
                    'id' => $v->id,
                    'code' => $v->code,
                    'name' => $v->name,
                    'description' => $v->description,
                    'cover_url' => $v->cover_url,
                    'areas_count' => (int) $v->areas_count,
                ])),
            'branding' => 'E-Booking',
        ]);
    }
}
