<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EmailOtpVerificationController extends Controller
{
    /**
     * Tampilkan halaman verifikasi OTP
     */
    public function show(): Response|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        
        // IMPORTANT: Jika user sudah verified (users existing), skip OTP verification
        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('auth/VerifyEmailOtp', [
            'email' => $user->email,
        ]);
    }

    /**
     * Kirim ulang OTP
     */
    public function resend(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();

        // IMPORTANT: Jika user sudah verified, tidak perlu kirim OTP lagi
        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        // Cooldown: Cek kapan terakhir kali OTP dikirim (hanya setelah pertama kali klik)
        $lastOtpSent = $request->session()->get('otp_last_sent');
        $cooldownSeconds = 60; // 60 detik cooldown
        
        // Hanya cek cooldown jika sudah pernah klik resend sebelumnya
        if ($lastOtpSent && now()->diffInSeconds($lastOtpSent) < $cooldownSeconds) {
            $remainingSeconds = $cooldownSeconds - now()->diffInSeconds($lastOtpSent);
            return back()->withErrors(['otp' => "Tunggu {$remainingSeconds} detik sebelum meminta kode OTP baru."]);
        }

        // Generate OTP baru
        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->update([
            'email_otp' => bcrypt($otpCode),
            'email_otp_expires_at' => now()->addMinutes(10),
        ]);

        // Kirim email OTP
        $user->notify(new EmailOtpNotification($otpCode));

        // Simpan waktu terakhir OTP dikirim
        $request->session()->put('otp_last_sent', now());

        return back()->with('success', 'Kode OTP baru telah dikirim ke email Anda.');
    }

    /**
     * Verifikasi OTP
     */
    public function verify(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        // IMPORTANT: Jika user sudah verified, langsung redirect
        if ($user->email_verified_at) {
            return redirect()->route('dashboard');
        }

        // Cek apakah OTP masih berlaku
        if (!$user->email_otp_expires_at) {
            return back()->withErrors(['otp' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.']);
        }

        // Convert ke Carbon jika masih string (untuk backward compatibility)
        $otpExpiresAt = $user->email_otp_expires_at instanceof \Carbon\Carbon 
            ? $user->email_otp_expires_at 
            : \Carbon\Carbon::parse($user->email_otp_expires_at);

        if ($otpExpiresAt->isPast()) {
            return back()->withErrors(['otp' => 'Kode OTP telah kedaluwarsa. Silakan minta kode baru.']);
        }

        // Verifikasi OTP
        if (!$user->email_otp || !password_verify($request->otp, $user->email_otp)) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid.']);
        }

        // Verifikasi berhasil
        $user->update([
            'email_verified_at' => now(),
            'email_otp' => null,
            'email_otp_expires_at' => null,
            'is_verifikasi' => 1,
        ]);

        Log::info('Email verified via OTP', ['user_id' => $user->id]);

        return redirect()->route('registration.steps', ['step' => 1])
            ->with('success', 'Email berhasil diverifikasi! Silakan lengkapi data Anda.');
    }
}

