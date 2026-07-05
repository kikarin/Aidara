<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $contentType = $response->headers->get('Content-Type') ?? '';

        // Skip JSON responses
        if (str_contains($contentType, 'application/json')) {
            return $response;
        }

        // Skip file/stream downloads (e.g. profile sertifikat/dokumen preview)
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        $statusCode = $response->getStatusCode();

        // Jika ada error validation, format response
        if ($statusCode === 422) {
            $content = json_decode($response->getContent(), true);

            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error',
                'errors'  => $content['errors'] ?? $content,
            ], 422);
        }

        // Jika ada error 500 atau error lainnya
        if ($statusCode >= 500) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Internal server error',
            ], 500);
        }

        // Jika ada error 404
        if ($statusCode === 404) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource not found',
            ], 404);
        }

        // Jika ada error 401 (Unauthorized)
        if ($statusCode === 401) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Jika ada error 403 (Forbidden)
        if ($statusCode === 403) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Forbidden',
            ], 403);
        }

        return $response;
    }
}
