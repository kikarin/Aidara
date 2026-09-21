<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPenyewaDocument;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PenyewaAuthService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, profile: BookingPenyewaProfile}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'no_hp' => $data['no_hp'] ?? null,
                'is_active' => 1,
                'email_verified_at' => now(),
            ]);

            $role = \App\Models\Role::query()
                ->where('name', 'penyewa')
                ->where('guard_name', 'web')
                ->firstOrFail();

            $user->assignRole($role);
            $user->forceFill(['current_role_id' => $role->id])->save();

            $profile = BookingPenyewaProfile::query()->create([
                'user_id' => $user->id,
                'nama' => $data['nama'],
                'nik' => $data['nik'] ?? null,
                'no_hp' => $data['no_hp'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'instansi' => $data['instansi'] ?? null,
                'kategori_default' => $data['kategori_default'] ?? null,
            ]);

            $token = $user->createToken($data['device_name'] ?? 'e-booking')->plainTextToken;

            return compact('user', 'token', 'profile');
        });
    }

    /**
     * @return array{user: User, token: string, profile: ?BookingPenyewaProfile}
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak valid.'],
            ]);
        }

        if ((int) $user->is_active === 0) {
            throw ValidationException::withMessages([
                'email' => ['Akun tidak aktif.'],
            ]);
        }

        if (! $user->hasAnyRole(['penyewa', 'admin_upt'])) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak memiliki akses E-Booking.'],
            ]);
        }

        $user->tokens()->where('name', $deviceName ?? 'e-booking')->delete();
        $token = $user->createToken($deviceName ?? 'e-booking')->plainTextToken;
        $user->forceFill(['last_login' => now()])->save();

        $profile = BookingPenyewaProfile::query()->where('user_id', $user->id)->first();

        return compact('user', 'token', 'profile');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upsertProfile(User $user, array $data): BookingPenyewaProfile
    {
        return BookingPenyewaProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'nama' => $data['nama'] ?? $user->name,
                'nik' => $data['nik'] ?? null,
                'no_hp' => $data['no_hp'] ?? $user->no_hp,
                'alamat' => $data['alamat'] ?? null,
                'instansi' => $data['instansi'] ?? null,
                'kategori_default' => $data['kategori_default'] ?? null,
            ]
        );
    }

    public function uploadKtp(User $user, UploadedFile $file): BookingPenyewaDocument
    {
        $profile = BookingPenyewaProfile::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            throw ValidationException::withMessages([
                'profile' => ['Lengkapi profil terlebih dahulu.'],
            ]);
        }

        $docType = BookingDocumentType::query()->where('code', 'ktp')->where('is_active', true)->firstOrFail();
        $path = $file->store('booking/ktp/'.$user->id, 'public');

        return BookingPenyewaDocument::query()->updateOrCreate(
            [
                'penyewa_profile_id' => $profile->id,
                'document_type_id' => $docType->id,
            ],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'verified_at' => null,
                'verified_by' => null,
            ]
        );
    }
}
