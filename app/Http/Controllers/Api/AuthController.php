<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user dan generate token
     */
    public function login(LoginRequest $request)
    {
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

        // Delete existing tokens for this user (optional - for security)
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

        // Update last login
        $user->update(['last_login' => now()]);

        // Log activity
        activity()->event('Mobile Login')->performedOn($user)->log('Auth');

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil',
            'data'    => [
                'user'       => new UserResource($user->load(['role', 'users_role.role'])),
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout user dan revoke token
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        // Log activity
        activity()->event('Mobile Logout')->performedOn($user)->log('Auth');

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['role', 'users_role.role']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $request->user()->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('mobile-app-refresh')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Token berhasil diperbarui',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
