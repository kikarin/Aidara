<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\LoginPenyewaRequest;
use App\Http\Requests\Booking\RegisterPenyewaRequest;
use App\Services\Booking\PenyewaAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly PenyewaAuthService $auth,
    ) {}

    public function register(RegisterPenyewaRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Registrasi penyewa berhasil',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'roles' => $result['user']->getRoleNames(),
                ],
                'profile' => $result['profile'],
            ],
        ], 201);
    }

    public function login(LoginPenyewaRequest $request): JsonResponse
    {
        $result = $this->auth->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name')
        );

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'roles' => $result['user']->getRoleNames(),
                ],
                'profile' => $result['profile'],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = \App\Models\Booking\BookingPenyewaProfile::query()
            ->where('user_id', $user->id)
            ->with('documents.documentType')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
                'profile' => $profile,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
