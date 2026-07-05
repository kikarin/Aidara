<?php

namespace App\Http\Controllers;

use App\Services\WorldCup\WorldCupService;
use App\Support\WorldCupFeature;
use Inertia\Inertia;
use Inertia\Response;

class WorldCupController extends Controller
{
    public function __construct(
        private readonly WorldCupService $worldCupService,
    ) {
    }

    public function index(): Response
    {
        abort_unless(WorldCupFeature::enabled(), 404);

        return Inertia::render('worldcup/Index', [
            ...$this->worldCupService->getFullPageData(),
            'sectionTitle' => app(\App\Services\AppSettingService::class)->getWorldCupSettings()['section_title'],
        ]);
    }

    public function preview(WorldCupService $worldCupService)
    {
        abort_unless(WorldCupFeature::enabled(), 404);

        return response()->json($worldCupService->getLandingPreview());
    }

    public function live()
    {
        abort_unless(WorldCupFeature::enabled(), 404);

        return response()->json([
            'matches' => $this->worldCupService->getLiveMatches(),
        ]);
    }

    public function schedule()
    {
        abort_unless(WorldCupFeature::enabled(), 404);

        return response()->json($this->worldCupService->getSchedulePayload());
    }

    public function groups()
    {
        abort_unless(WorldCupFeature::enabled(), 404);

        return response()->json([
            'groups' => $this->worldCupService->getGroupStandings(),
        ]);
    }
}
