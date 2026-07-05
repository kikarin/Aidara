<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        require_once app_path('Helpers/GlobalHelper.php');
        require_once app_path('Helpers/dateid_helper.php');

        RateLimiter::for('gemini-chat', function (Request $request) {
            $perMinute = (int) config('gemini.rate_limit', 20);

            return Limit::perMinute($perMinute)->by($request->user()?->id ?: $request->ip());
        });
    }
}
