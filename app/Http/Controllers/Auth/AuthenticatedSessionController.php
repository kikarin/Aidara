<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        $recaptchaSiteKey = config('services.recaptcha.site_key');

        // Debug: Log if key is missing
        if (empty($recaptchaSiteKey)) {
            \Log::warning('reCAPTCHA Site Key is not configured. Please check your .env file.');
        }

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => $request->session()->get('status'),
            'recaptchaSiteKey' => $recaptchaSiteKey ?: null,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // Jika user belum verified, redirect ke OTP verification
        if (!$user->email_verified_at) {
            // Jika belum ada OTP, kirim OTP baru
            if (!$user->email_otp || !$user->email_otp_expires_at) {
                $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                
                $user->update([
                    'email_otp' => bcrypt($otpCode),
                    'email_otp_expires_at' => now()->addMinutes(10),
                ]);

                // Kirim email OTP
                $user->notify(new \App\Notifications\EmailOtpNotification($otpCode));
                
                // Simpan waktu terakhir OTP dikirim untuk cooldown
                $request->session()->put('otp_last_sent', now());
            }
            
            return redirect()->route('email.otp.verify')
                ->with('warning', 'Email Anda belum diverifikasi. Silakan masukkan kode OTP yang telah dikirim ke email Anda.');
        }

        // Jika user sudah verified tapi masih dalam proses registrasi, redirect ke registration steps
        $isInRegistrationProcess = !$user->peserta_id || !$user->peserta_type || $user->registration_status === 'pending';
        if ($isInRegistrationProcess) {
            return redirect()->route('registration.steps', ['step' => 1])
                ->with('info', 'Silakan lengkapi data registrasi Anda.');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
