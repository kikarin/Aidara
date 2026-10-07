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
        if ($redirect = $this->redirectAuthenticatedUser()) {
            return $redirect;
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

        if (! $result['user']->email_verified_at) {
            return $this->sendOtpAndRedirect($request, $result['user'], 'Email Anda belum diverifikasi. Silakan masukkan kode OTP yang telah dikirim ke email Anda.');
        }

        if ($result['user']->hasRole('admin_upt') && ! $result['user']->hasRole('penyewa')) {
            return redirect()->intended(route('e-booking.admin.dashboard'));
        }

        return redirect()->intended(route('e-booking.catalog'));
    }

    public function showRegister(): Response|RedirectResponse
    {
        if ($redirect = $this->redirectAuthenticatedUser()) {
            return $redirect;
        }

        return Inertia::render('modules/e-booking/Register');
    }

    public function register(RegisterPenyewaRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['device_name'] = $data['device_name'] ?? 'booking-web';

        $result = $this->auth->register($data, emailVerified: false);

        Auth::login($result['user'], true);
        $request->session()->regenerate();

        return $this->sendOtpAndRedirect($request, $result['user'], 'Akun berhasil dibuat. Kode OTP telah dikirim ke email Anda untuk verifikasi.');
    }

    public function showOtp(): Response|RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('e-booking.login');
        }

        if ($user->email_verified_at) {
            return $this->redirectVerifiedUser($user);
        }

        return Inertia::render('modules/e-booking/VerifyOtp', [
            'email' => $user->email,
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $user = Auth::user();

        if (! $user) {
            return redirect()->route('e-booking.login');
        }

        if ($user->email_verified_at) {
            return $this->redirectVerifiedUser($user);
        }

        if (! $this->auth->verifyEmailOtp($user, $request->input('otp'))) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
        }

        return $this->redirectVerifiedUser($user->fresh())
            ->with('success', 'Email berhasil diverifikasi. Selamat menggunakan Si Bola.');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('e-booking.login');
        }

        if ($user->email_verified_at) {
            return $this->redirectVerifiedUser($user);
        }

        $lastSent = $request->session()->get('booking_otp_last_sent');
        if ($lastSent && now()->diffInSeconds($lastSent) < 60) {
            $remaining = (int) ceil(60 - now()->diffInSeconds($lastSent));

            return back()->withErrors(['otp' => "Tunggu {$remaining} detik sebelum meminta kode OTP baru."]);
        }

        return $this->sendOtpAndRedirect($request, $user, 'Kode OTP baru telah dikirim ke email Anda.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('e-booking.catalog');
    }

    private function sendOtpAndRedirect(Request $request, $user, string $message): RedirectResponse
    {
        $this->auth->issueEmailOtp($user);
        $request->session()->put('booking_otp_last_sent', now());

        return redirect()->route('e-booking.otp.show')->with('success', $message);
    }

    private function redirectVerifiedUser($user): RedirectResponse
    {
        if ($user->hasRole('admin_upt') && ! $user->hasRole('penyewa')) {
            return redirect()->route('e-booking.admin.dashboard');
        }

        return redirect()->intended(route('e-booking.catalog'));
    }

    private function redirectAuthenticatedUser(): ?RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if (! $user->email_verified_at) {
            return redirect()->route('e-booking.otp.show');
        }

        if ($user->hasAnyRole(['penyewa', 'admin_upt'])) {
            return $this->redirectVerifiedUser($user);
        }

        return null;
    }
}
