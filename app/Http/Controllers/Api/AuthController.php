<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ResendOtpRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user - Step 1: Cek credentials & kirim OTP jika belum verified
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Kredensial yang diberikan salah.'],
                ]);
            }

            // Check if user is active
            if ($user->is_active == 0) {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda tidak aktif.'],
                ]);
            }

            // Check if user can login based on role
            if ($user->role && $user->role->is_allow_login == 0) {
                throw ValidationException::withMessages([
                    'email' => ['Role Anda tidak diizinkan untuk login.'],
                ]);
            }

            // Jika email belum verified, kirim OTP
            if (!$user->email_verified_at) {
                // Generate OTP
                $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                
                $user->update([
                    'email_otp' => bcrypt($otpCode),
                    'email_otp_expires_at' => now()->addMinutes(10),
                ]);

                // Kirim email OTP
                $user->notify(new EmailOtpNotification($otpCode));

                Log::info('OTP sent for login', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);

                return response()->json([
                    'status'  => 'otp_required',
                    'message' => 'Kode OTP telah dikirim ke email Anda. Silakan verifikasi untuk melanjutkan.',
                    'data'    => [
                        'email' => $user->email,
                        'requires_otp' => true,
                    ],
                ]);
            }

            // Jika sudah verified, langsung generate token
            $user->tokens()->delete();
            $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;
            $user->update(['last_login' => now()]);

            activity()->event('Mobile Login')->performedOn($user)->log('Auth');

            Log::info('Mobile login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Login berhasil',
                'data'    => [
                    'user'       => new UserResource($user->load(['role', 'users_role.role'])),
                    'token'      => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage(), [
                'exception' => $e,
                'email'     => $request->email,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat login. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Verify OTP - Step 2: Verifikasi OTP dan generate token
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User tidak ditemukan.',
                ], 404);
            }

            // Jika sudah verified, langsung generate token
            if ($user->email_verified_at) {
                $user->tokens()->delete();
                $token = $user->createToken('mobile-app')->plainTextToken;

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Email sudah terverifikasi',
                    'data'    => [
                        'user'       => new UserResource($user->load(['role', 'users_role.role'])),
                        'token'      => $token,
                        'token_type' => 'Bearer',
                    ],
                ]);
            }

            // Cek apakah OTP masih berlaku
            if (!$user->email_otp_expires_at) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP tidak ditemukan. Silakan minta kode baru.',
                ], 400);
            }

            $otpExpiresAt = $user->email_otp_expires_at instanceof \Carbon\Carbon 
                ? $user->email_otp_expires_at 
                : \Carbon\Carbon::parse($user->email_otp_expires_at);

            if ($otpExpiresAt->isPast()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP telah kedaluwarsa. Silakan minta kode baru.',
                ], 400);
            }

            // Verifikasi OTP
            if (!$user->email_otp || !password_verify($request->otp, $user->email_otp)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Kode OTP tidak valid.',
                ], 400);
            }

            // Verifikasi berhasil - update user dan generate token
            $user->update([
                'email_verified_at' => now(),
                'email_otp' => null,
                'email_otp_expires_at' => null,
                'is_verifikasi' => 1,
                'last_login' => now(),
            ]);

            $user->tokens()->delete();
            $token = $user->createToken('mobile-app')->plainTextToken;

            Log::info('Email verified via OTP (Mobile)', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            activity()->event('Mobile Login')->performedOn($user)->log('Auth');

            return response()->json([
                'status'  => 'success',
                'message' => 'Email berhasil diverifikasi',
                'data'    => [
                    'user'       => new UserResource($user->load(['role', 'users_role.role'])),
                    'token'      => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Verify OTP error: ' . $e->getMessage(), [
                'exception' => $e,
                'email'     => $request->email,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat verifikasi OTP. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Resend OTP
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Email sudah terverifikasi.',
                ], 400);
            }

            // Cooldown check menggunakan cache
            $cacheKey = "otp_resend_{$user->id}";
            $lastSent = Cache::get($cacheKey);
            $cooldownSeconds = 60;

            if ($lastSent && now()->diffInSeconds($lastSent) < $cooldownSeconds) {
                $remainingSeconds = $cooldownSeconds - now()->diffInSeconds($lastSent);
                return response()->json([
                    'status'  => 'error',
                    'message' => "Tunggu {$remainingSeconds} detik sebelum meminta kode OTP baru.",
                ], 429);
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
            Cache::put($cacheKey, now(), now()->addMinutes(2));

            Log::info('OTP resent', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Kode OTP baru telah dikirim ke email Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('Resend OTP error: ' . $e->getMessage(), [
                'exception' => $e,
                'email'     => $request->email,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengirim ulang OTP. Silakan coba lagi.',
            ], 500);
        }
    }

    /**
     * Logout user dan revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Log activity
            activity()->event('Mobile Logout')->performedOn($user)->log('Auth');

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            Log::info('Mobile logout', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Logout berhasil',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat logout.',
            ], 500);
        }
    }

    /**
     * Get user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['role', 'users_role.role']);

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'user' => new UserResource($user),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get profile error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data profil.',
            ], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('mobile-app-refresh')->plainTextToken;

            Log::info('Token refreshed', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Token berhasil diperbarui',
                'data'    => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Refresh token error: ' . $e->getMessage(), [
                'exception' => $e,
                'user_id'   => $request->user()->id ?? null,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui token.',
            ], 500);
        }
    }
}

