<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Jika response sudah dalam format JSON, skip
        if ($response->headers->get('Content-Type') === 'application/json') {
            return $response;
        }

        // Jika ada error validation, format response
        if ($response->status() === 422) {
            $content = json_decode($response->getContent(), true);

            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $content['errors'] ?? $content,
            ], 422);
        }

        // Jika ada error 500 atau error lainnya
        if ($response->status() >= 500) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error',
            ], 500);
        }

        // Jika ada error 404
        if ($response->status() === 404) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource not found',
            ], 404);
        }

        // Jika ada error 401 (Unauthorized)
        if ($response->status() === 401) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Jika ada error 403 (Forbidden)
        if ($response->status() === 403) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        return $response;
    }
}
