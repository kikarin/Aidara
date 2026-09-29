<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\WorldCupSettingRequest;
use App\Services\AppSettingService;
use App\Services\WorldCup\WorldCupApiService;
use App\Services\WorldCup\WorldCupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class WorldCupSettingController extends Controller
{
    public function __construct(
        private readonly AppSettingService $settings,
        private readonly WorldCupService $worldCupService,
        private readonly WorldCupApiService $apiService,
    ) {
    }

    public function edit(): Response
    {
        Gate::authorize('Pengaturan World Cup Edit');

        return Inertia::render('settings/WorldCup', [
            'settings' => $this->settings->getWorldCupSettings(),
            'apiStatus' => $this->worldCupService->getApiStatus(),
        ]);
    }

    public function update(WorldCupSettingRequest $request): RedirectResponse
    {
        Gate::authorize('Pengaturan World Cup Edit');

        $this->settings->updateWorldCupSettings($request->validated());
        $this->apiService->clearCache();

        return redirect()
            ->route('settings.worldcup.edit')
            ->with('success', 'Pengaturan Live Score Piala Dunia berhasil disimpan.');
    }
}
