<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Helpers\RecaptchaHelper;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use App\Repositories\RegistrationRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    protected $repository;

    public function __construct(?RegistrationRepository $repository = null)
    {
        // Fallback untuk test environment jika dependency injection gagal
        $this->repository = $repository ?? app(RegistrationRepository::class);
    }

    /**
     * Show the registration page (Step 0 - Email/Password)
     */
    public function create(): Response
    {
        try {
            $recaptchaSiteKey = null;
            
            // Try to get reCAPTCHA site key, but don't fail if config is not available
            try {
                $recaptchaSiteKey = config('services.recaptcha.site_key');
            } catch (\Exception $e) {
                // Ignore config errors in test environment
                if (!app()->environment('testing')) {
                    \Log::warning('reCAPTCHA Site Key is not configured. Please check your .env file.');
                }
            }

            return Inertia::render('registration/Register', [
                'recaptchaSiteKey' => $recaptchaSiteKey ?: null,
            ]);
        } catch (\Exception $e) {
            // Fallback jika ada error, tetap render halaman tanpa reCAPTCHA
            if (!app()->environment('testing')) {
                \Log::error('RegistrationController: Error rendering registration page', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return Inertia::render('registration/Register', [
                'recaptchaSiteKey' => null,
            ]);
        }
    }

    /**
     * Handle initial registration (Step 0)
     * Create user dengan status pending, redirect ke steps
     */
    public function store(Request $request)
    {
        // Cek apakah email sudah ada (termasuk soft delete)
        $existingUser = User::withTrashed()->where('email', $request->email)->first();
        
        // Validasi email: unique hanya untuk user yang tidak di-soft delete
        $emailRule = [
            'required',
            'string',
            'lowercase',
            'email',
            'max:255',
            new \App\Rules\NotDisposableEmail(),
        ];
        
        // Jika user aktif ada, validasi unique
        if ($existingUser && !$existingUser->trashed()) {
            $emailRule[] = Rule::unique('users', 'email');
        } else {
            // Jika user di-soft delete atau tidak ada, validasi unique dengan whereNull untuk mengabaikan soft delete
            $emailRule[] = Rule::unique('users', 'email')->whereNull('deleted_at');
        }
        
        $rules = [
            'email'    => $emailRule,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Add reCAPTCHA validation if configured
        if (config('services.recaptcha.secret_key')) {
            $rules['recaptcha_token'] = 'required|string';
        }

        $request->validate($rules);

        // Verify reCAPTCHA if configured
        if (config('services.recaptcha.secret_key')) {
            $recaptchaToken = $request->input('recaptcha_token');
            if (!$recaptchaToken || !RecaptchaHelper::verify($recaptchaToken, $request->ip())) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['recaptcha_token' => 'Verifikasi reCAPTCHA gagal. Silakan coba lagi.']);
            }
        }

        try {
            // Cek apakah user sudah ada dan di-soft delete
            $existingUser = User::withTrashed()->where('email', $request->email)->first();
            
            if ($existingUser && $existingUser->trashed()) {
                // Restore user yang sudah di-soft delete
                $existingUser->restore();
                $user = $existingUser;
                
                // Update password
                $user->update([
                    'password' => bcrypt($request->password),
                ]);
                
                Log::info('RegistrationController: Restored soft-deleted user', [
                    'user_id' => $user->id,
                    'email' => $request->email,
                ]);
            } else {
                // Buat user baru
                $user = $this->repository->createRegistrationUser([
                    'email'    => $request->email,
                    'password' => $request->password,
                    'name'     => $request->email, // Temporary, akan diupdate di step 2
                ]);
            }

            // Login user untuk session
            auth()->login($user);

            // Clear any intended URL to prevent redirect to dashboard
            session()->forget('url.intended');

            // IMPORTANT: Cek apakah user sudah verified (untuk backward compatibility)
            if ($user->email_verified_at) {
                // User sudah verified, langsung lanjut ke steps
                Log::info('RegistrationController: User already verified, skipping OTP', [
                    'user_id' => $user->id,
                ]);

                return redirect()->route('registration.steps', ['step' => 1])
                    ->with('success', 'Registrasi berhasil! Silakan lengkapi data Anda.');
            }

            // User baru - kirim OTP
            $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            $user->update([
                'email_otp' => bcrypt($otpCode),
                'email_otp_expires_at' => now()->addMinutes(10),
            ]);

            // Kirim email OTP
            $user->notify(new EmailOtpNotification($otpCode));

            // Simpan waktu terakhir OTP dikirim untuk cooldown
            $request->session()->put('otp_last_sent', now());

            Log::info('RegistrationController: User registered, OTP sent', [
                'user_id' => $user->id,
            ]);

            // IMPORTANT: Pastikan user tetap login dan redirect ke OTP verification
            // User akan tetap login meskipun belum verified, sehingga bisa akses registration steps setelah verify
            return redirect()->route('email.otp.verify')
                ->with('success', 'Kode OTP telah dikirim ke email Anda. Silakan cek inbox email Anda.');
        } catch (\Exception $e) {
            Log::error('RegistrationController: Error creating registration user', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['email' => 'Terjadi kesalahan saat membuat akun. Silakan coba lagi.']);
        }
    }

    /**
     * Show success page setelah submit registration
     */
    public function success(): Response
    {
        return Inertia::render('registration/Success');
    }
}
