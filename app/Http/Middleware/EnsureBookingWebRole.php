<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBookingWebRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $adminOnly = $roles === ['admin_upt'];

        if (! $user) {
            return redirect()->guest(
                $adminOnly ? route('e-booking.admin.login') : route('e-booking.login')
            );
        }

        if ($roles !== [] && ! $user->hasAnyRole($roles)) {
            if ($adminOnly) {
                return redirect()
                    ->route('e-booking.admin.login')
                    ->with('error', 'Akses admin UPT saja. Login dengan akun admin.upt.');
            }

            return redirect()
                ->route('e-booking.login')
                ->with('error', 'Gunakan akun penyewa E-Booking (bukan akun dashboard Dispora).');
        }

        return $next($request);
    }
}
