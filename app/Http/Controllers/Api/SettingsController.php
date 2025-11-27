<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\ChangePasswordRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Update user profile
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();

        try {
            $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'no_hp'         => $request->no_hp,
                'tanggal_lahir' => $request->tanggal_lahir,
            ]);

            // Log activity
            activity()->event('Update Profile')->performedOn($user)->log('Settings');

            return response()->json([
                'status'  => 'success',
                'message' => 'Profile berhasil diperbarui',
                'data'    => [
                    'user' => new UserResource($user->load(['role', 'users_role.role'])),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui profile: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Verify old password
        if (!Hash::check($request->old_password, $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Password lama tidak sesuai.'],
            ]);
        }

        // Check if new password is different from old password
        if (Hash::check($request->new_password, $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => ['Password baru harus berbeda dengan password lama.'],
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            // Log activity
            activity()->event('Change Password')->performedOn($user)->log('Settings');

            return response()->json([
                'status'  => 'success',
                'message' => 'Password berhasil diubah',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengubah password: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send reset password link
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Link reset password telah dikirim ke email Anda',
                ]);
            } else {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Gagal mengirim link reset password',
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengirim link reset password: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        // Verify password before deletion
        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password tidak sesuai.'],
            ]);
        }

        try {
            // Log activity before deletion
            activity()->event('Delete Account')->performedOn($user)->log('Settings');

            // Delete all user tokens
            $user->tokens()->delete();

            // Delete user (soft delete if enabled)
            $user->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Akun berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus akun: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user settings data
     */
    public function getSettings(Request $request)
    {
        $user = $request->user()->load(['role', 'users_role.role']);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user' => new UserResource($user),
            ],
        ]);
    }
}
