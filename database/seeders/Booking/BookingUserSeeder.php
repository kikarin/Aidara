<?php

namespace Database\Seeders\Booking;

use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User demo E-Booking (admin UPT + penyewa).
 *
 * Password default: password123
 */
class BookingUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('name', 'admin_upt')->where('guard_name', 'web')->firstOrFail();
        $penyewaRole = Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin.upt@test.local'],
            [
                'name' => 'Admin UPT E-Booking',
                'password' => Hash::make('password123'),
                'no_hp' => '085777183633',
                'is_active' => 1,
                'email_verified_at' => now(),
                'is_verifikasi' => 1,
                'current_role_id' => $adminRole->id,
            ]
        );
        if (! $admin->hasRole('admin_upt')) {
            $admin->assignRole($adminRole);
        }
        $admin->forceFill(['current_role_id' => $adminRole->id])->save();

        $penyewa = User::query()->updateOrCreate(
            ['email' => 'penyewa.demo@test.local'],
            [
                'name' => 'Penyewa Demo',
                'password' => Hash::make('password123'),
                'no_hp' => '081234567890',
                'is_active' => 1,
                'email_verified_at' => now(),
                'is_verifikasi' => 1,
                'current_role_id' => $penyewaRole->id,
            ]
        );
        if (! $penyewa->hasRole('penyewa')) {
            $penyewa->assignRole($penyewaRole);
        }
        $penyewa->forceFill(['current_role_id' => $penyewaRole->id])->save();

        BookingPenyewaProfile::query()->updateOrCreate(
            ['user_id' => $penyewa->id],
            [
                'nama' => 'Penyewa Demo',
                'nik' => '3201010101010001',
                'no_hp' => '081234567890',
                'alamat' => 'Cibinong, Kabupaten Bogor',
                'instansi' => 'Demo Instansi',
                'kategori_default' => 'non_pemerintah',
            ]
        );

        $this->command?->info('Booking users seeded:');
        $this->command?->info('  admin.upt@test.local / password123 (admin_upt)');
        $this->command?->info('  penyewa.demo@test.local / password123 (penyewa)');
    }
}
