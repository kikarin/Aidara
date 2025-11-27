<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTargetLatihanPermission
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

        // Superadmin (1), Admin (11), Pelatih (36)
        $allowedRoles = [1, 11, 36];

        if (!in_array($user->current_role_id, $allowedRoles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Anda tidak memiliki izin. Hanya Superadmin, Admin, dan Pelatih yang dapat mengelola target latihan.',
            ], 403);
        }

        return $next($request);
    }
}
