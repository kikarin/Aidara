<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CsrfController extends Controller
{
    /**
     * Get CSRF cookie untuk frontend
     */
    public function getCsrfCookie(Request $request): JsonResponse
    {
        // Sanctum akan otomatis menangani CSRF cookie
        return response()->json([
            'status'  => 'success',
            'message' => 'CSRF cookie set successfully',
        ], 204);
    }
}
