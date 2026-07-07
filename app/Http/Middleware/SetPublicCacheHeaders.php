<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicCacheHeaders
{
  /**
   * @param  Closure(Request): Response  $next
   */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return $response;
        }

        if ($request->routeIs('home', 'event.public.index', 'event.public.show', 'worldcup.index', 'legal.show')) {
            $response->headers->set('Cache-Control', 'public, max-age=3600, stale-while-revalidate=86400');
        }

        return $response;
    }
}
