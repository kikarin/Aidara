<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InjectUserPermissions;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetPublicCacheHeaders;
use App\Http\Middleware\ApiResponseMiddleware;
use App\Http\Middleware\CheckProgramLatihanPermission;
use App\Http\Middleware\CheckPemeriksaanPermission;
use App\Http\Middleware\CheckTurnamenPermission;
use App\Http\Middleware\CheckRegistrationStatus;
use App\Http\Middleware\EnsureBookingRole;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/booking')
                ->group(base_path('routes/booking.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            InjectUserPermissions::class,
            SetPublicCacheHeaders::class,
            SecurityHeaders::class,
            // EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api(append: [
            HandleCors::class,
            ApiResponseMiddleware::class,
        ]);

        // Register custom middleware
        $middleware->alias([
            'program.latihan.permission' => CheckProgramLatihanPermission::class,
            'pemeriksaan.permission'     => CheckPemeriksaanPermission::class,
            'turnamen.permission'        => CheckTurnamenPermission::class,
            'check.registration.status'  => CheckRegistrationStatus::class,
            'ensure.email.verified'       => \App\Http\Middleware\EnsureEmailVerified::class,
            'booking.role'               => EnsureBookingRole::class,
        ]);

        // Sanctum middleware untuk stateful API (Remove)
        $middleware->statefulApi([
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // CSRF token validation exceptions
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $response;
            }

            if (! $request->acceptsHtml()) {
                return $response;
            }

            $status = match (true) {
                $exception instanceof TokenMismatchException => 419,
                $exception instanceof TooManyRequestsHttpException => 429,
                $exception instanceof AuthorizationException => 403,
                $exception instanceof NotFoundHttpException => 404,
                default => $response->getStatusCode(),
            };

            if (! in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                return $response;
            }

            if ($status === 500 && config('app.debug')) {
                return $response;
            }

            return Inertia::render("errors/Error{$status}")
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
