<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    private const VALID_THEMES = ['light', 'slate', 'warm', 'sport', 'dispora', 'dark'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'dispora';

        if ($appearance === 'default') {
            $appearance = 'light';
        }

        $resolvedTheme = $this->resolveTheme($appearance);

        View::share('appearance', $appearance);
        View::share('resolvedTheme', $resolvedTheme);

        return $next($request);
    }

    private function resolveTheme(string $appearance): string
    {
        if ($appearance === 'system') {
            return 'dispora';
        }

        if (in_array($appearance, self::VALID_THEMES, true)) {
            return $appearance;
        }

        return 'dispora';
    }
}
