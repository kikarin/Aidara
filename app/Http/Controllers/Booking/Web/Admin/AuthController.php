<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\LoginPenyewaRequest;
use App\Services\Booking\PenyewaAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly PenyewaAuthService $auth,
    ) {}

    public function showLogin(): Response|RedirectResponse
    {
        $user = Auth::user();
        if ($user?->hasRole('admin_upt')) {
            return redirect()->route('e-booking.admin.dashboard');
        }

        return Inertia::render('modules/e-booking/admin/Login');
    }

    public function login(LoginPenyewaRequest $request): RedirectResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name') ?? 'booking-admin'
        );

        if (! $result['user']->hasRole('admin_upt')) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini bukan admin UPT E-Booking.'],
            ]);
        }

        Auth::login($result['user'], true);
        $request->session()->regenerate();

        return redirect()->intended(route('e-booking.admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('e-booking.admin.login');
    }
}
