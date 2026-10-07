<?php

namespace App\Services\Booking;

use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPenyewaDocument;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\User;
use App\Services\OtpMailService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PenyewaAuthService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string, profile: BookingPenyewaProfile}
     */
    public function register(array $data, bool $emailVerified = true): array
    {
        return DB::transaction(function () use ($data, $emailVerified) {
            $user = User::query()->create([
                'name' => $data['nama'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'no_hp' => $data['no_hp'] ?? null,
                'is_active' => 1,
                'email_verified_at' => $emailVerified ? now() : null,
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
     * Generate & kirim OTP email untuk verifikasi akun penyewa.
     */
    public function issueEmailOtp(User $user): void
    {
        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'email_otp' => bcrypt($otpCode),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        app(OtpMailService::class)->send($user->email, $otpCode, 'booking-register');

        Log::info('Booking OTP issued', ['user_id' => $user->id, 'email' => $user->email]);
    }

    /**
     * Verifikasi kode OTP. Mengembalikan true jika valid dan menandai email terverifikasi.
     */
    public function verifyEmailOtp(User $user, string $code): bool
    {
        if (! $user->email_otp || ! $user->email_otp_expires_at) {
            return false;
        }

        $expiresAt = $user->email_otp_expires_at instanceof Carbon
            ? $user->email_otp_expires_at
            : Carbon::parse($user->email_otp_expires_at);

        if ($expiresAt->isPast()) {
            return false;
        }

        if (! password_verify($code, $user->email_otp)) {
            return false;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'is_verifikasi' => 1,
            'email_otp' => null,
            'email_otp_expires_at' => null,
        ])->save();

        Log::info('Booking email verified via OTP', ['user_id' => $user->id]);

        return true;
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

    public function uploadDocument(User $user, UploadedFile $file, ?string $documentTypeCode = null): BookingPenyewaDocument
    {
        $profile = BookingPenyewaProfile::query()->where('user_id', $user->id)->first();
        if (! $profile) {
            throw ValidationException::withMessages([
                'profile' => ['Lengkapi profil terlebih dahulu.'],
            ]);
        }

        $code = $documentTypeCode ?: 'dokumen';
        $docType = BookingDocumentType::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $docType) {
            throw ValidationException::withMessages([
                'document_type_code' => ["Jenis dokumen \"{$code}\" tidak ditemukan atau tidak aktif."],
            ]);
        }

        $path = $file->store('booking/documents/'.$user->id, 'public');

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
