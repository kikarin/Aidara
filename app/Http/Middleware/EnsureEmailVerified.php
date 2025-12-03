<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Handle an incoming request.
     * 
     * Redirect user ke halaman verifikasi OTP jika email belum verified
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip jika user belum login
        if (!$user) {
            return $next($request);
        }

        // Skip untuk route verifikasi OTP dan logout
        if ($request->routeIs('email.otp.*') || 
            $request->routeIs('logout') || 
            $request->routeIs('registration.steps') ||
            $request->routeIs('registration.*')) {
            return $next($request);
        }

        // Cek apakah email sudah verified
        if (!$user->email_verified_at) {
            return redirect()->route('email.otp.verify')
                ->with('warning', 'Silakan verifikasi email Anda terlebih dahulu untuk melanjutkan.');
        }

        return $next($request);
    }
}

