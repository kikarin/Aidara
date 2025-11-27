<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPemeriksaanPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check if user has permission to manage pemeriksaan
        // Superadmin (role_id = 1), Admin (role_id = 11), dan Tenaga Pendukung (role_id = 37) dapat mengakses
        $allowedRoles = [1, 11, 37]; // Superadmin, Admin, dan Tenaga Pendukung

        if (!in_array($user->current_role_id, $allowedRoles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki izin untuk mengakses fitur ini. Hanya Superadmin, Admin, dan Tenaga Pendukung yang dapat mengelola pemeriksaan.',
            ], 403);
        }

        return $next($request);
    }
}
