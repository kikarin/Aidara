<?php

namespace App\Http\Controllers;

use App\Services\EventService;
use App\Services\WorldCup\WorldCupService;
use App\Support\WorldCupFeature;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(
        WorldCupService $worldCupService,
        EventService $eventService,
    ): Response {
        $payload = [
            'eventPreview' => $eventService->getLandingPreview(),
        ];

        if (WorldCupFeature::showOnLanding()) {
            $payload['worldcupPreview'] = $worldCupService->getLandingPreview();
        }

        return Inertia::render('Welcome', $payload);
    }
}
