<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;

class AppSettingService
{
    private const CACHE_PREFIX = 'app_settings.';

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX.$key,
            config('worldcup.settings_cache_ttl', 300),
            fn () => AppSetting::getValue($key, $default),
        );
    }

    public function set(string $key, mixed $value): void
    {
        AppSetting::setValue($key, $value);
        Cache::forget(self::CACHE_PREFIX.$key);
        Cache::forget(self::CACHE_PREFIX.'worldcup.all');
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default ? '1' : '0');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, (string) $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_string($value) ? $value : (string) $value;
    }

    public function isWorldCupEnabled(): bool
    {
        return $this->getBool('worldcup.enabled', false);
    }

    public function shouldShowOnLanding(): bool
    {
        return $this->isWorldCupEnabled() && $this->getBool('worldcup.show_on_landing', true);
    }

    public function getPreviewCount(): int
    {
        $count = $this->getInt('worldcup.preview_count', config('worldcup.preview_matches', 7));

        return max(1, min(20, $count));
    }

    public function getWorldCupSettings(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX.'worldcup.all',
            config('worldcup.settings_cache_ttl', 300),
            fn () => [
                'enabled' => $this->getBool('worldcup.enabled', false),
                'show_on_landing' => $this->getBool('worldcup.show_on_landing', true),
                'preview_count' => $this->getPreviewCount(),
                'section_title' => $this->getString('worldcup.section_title', 'Piala Dunia 2026'),
            ],
        );
    }

    public function updateWorldCupSettings(array $settings): void
    {
        $this->set('worldcup.enabled', (bool) ($settings['enabled'] ?? false));
        $this->set('worldcup.show_on_landing', (bool) ($settings['show_on_landing'] ?? true));
        $this->set('worldcup.preview_count', (string) max(1, min(20, (int) ($settings['preview_count'] ?? 7))));
        $this->set('worldcup.section_title', (string) ($settings['section_title'] ?? 'Piala Dunia 2026'));
    }
}
