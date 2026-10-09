<?php

namespace App\Support;

use App\Services\AppSettingService;

class WorldCupFeature
{
    public static function enabled(): bool
    {
        return app(AppSettingService::class)->isWorldCupEnabled();
    }

    public static function showOnLanding(): bool
    {
        return app(AppSettingService::class)->shouldShowOnLanding();
    }
}
