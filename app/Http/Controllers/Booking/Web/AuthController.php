<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\LoginPenyewaRequest;
use App\Http\Requests\Booking\RegisterPenyewaRequest;
use App\Services\Booking\PenyewaAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        if ($user?->hasRole('penyewa')) {
            return redirect()->intended(route('e-booking.catalog'));
        }

        return Inertia::render('modules/e-booking/Login');
    }

    public function login(LoginPenyewaRequest $request): RedirectResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name') ?? 'booking-web'
        );

        Auth::login($result['user'], true);
        $request->session()->regenerate();

        if ($result['user']->hasRole('admin_upt') && ! $result['user']->hasRole('penyewa')) {
            return redirect()->intended(route('e-booking.admin.dashboard'));
        }

        return redirect()->intended(route('e-booking.catalog'));
    }

    public function showRegister(): Response|RedirectResponse
    {
        if ($this->alreadyBookingUser()) {
            return redirect()->route('e-booking.catalog');
        }

        return Inertia::render('modules/e-booking/Register');
    }

    public function register(RegisterPenyewaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['device_name'] = $data['device_name'] ?? 'booking-web';

        $result = $this->auth->register($data);

        Auth::login($result['user'], true);
        $request->session()->regenerate();

        return redirect()
            ->route('e-booking.catalog')
            ->with('success', 'Akun penyewa berhasil dibuat. Silakan pilih venue untuk booking.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('e-booking.catalog');
    }

    private function alreadyBookingUser(): bool
    {
        $user = Auth::user();

        return $user !== null && $user->hasAnyRole(['penyewa', 'admin_upt']);
    }
}
