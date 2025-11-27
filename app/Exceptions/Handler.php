<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;
use Inertia\Inertia;

class Handler extends ExceptionHandler
{
    protected $levels     = [];
    protected $dontReport = [];
    protected $dontFlash  = ['current_password', 'password', 'password_confirmation'];

    public function register()
    {
        //
    }

    public function render($request, Throwable $exception)
    {
        // Handle CORS preflight requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        // Handle API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        // Handle web requests
        if (
            $exception instanceof AuthorizationException || ($exception instanceof HttpException && $exception->getStatusCode() === 403)
        ) {
            return Inertia::render('errors/Error403')->toResponse($request)->setStatusCode(403);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions
     */
    private function handleApiException($request, Throwable $exception)
    {
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if ($exception instanceof AuthorizationException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        if ($exception instanceof ModelNotFoundException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource not found',
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Route not found',
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Method not allowed',
            ], 405);
        }

        if ($exception instanceof HttpException) {
            return response()->json([
                'status'  => 'error',
                'message' => $exception->getMessage() ?: 'HTTP error',
            ], $exception->getStatusCode());
        }

        // Handle other exceptions
        if (config('app.debug')) {
            return response()->json([
                'status'  => 'error',
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTrace(),
            ], 500);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Internal server error',
        ], 500);
    }
}
