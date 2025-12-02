<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProgramLatihanPermission
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

        // Check if user has permission to manage program latihan
        // Superadmin (role_id = 1) dan Pelatih (role_id = 36) dapat mengakses
        $allowedRoles = [1, 11, 36]; // Superadmin dan Pelatih

        if (!in_array($user->current_role_id, $allowedRoles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki izin untuk mengakses fitur ini. Hanya Superadmin dan Pelatih yang dapat mengelola program latihan.',
            ], 403);
        }

        return $next($request);
    }
}
