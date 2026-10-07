<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingRegisterTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('users') || ! Schema::hasTable('booking_penyewa_profiles')) {
            $this->markTestSkipped('Tabel user/penyewa belum termigrasi.');
        }

        Role::query()->firstOrCreate(
            ['name' => 'penyewa', 'guard_name' => 'web'],
            ['bg' => 'bg-success', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        Mail::fake();
    }

    #[Test]
    public function register_normalizes_phone_to_whatsapp_format(): void
    {
        $email = 'wa.'.uniqid().'@test.local';

        $this->post(route('e-booking.register.store'), [
            'nama'                  => 'Penyewa WA',
            'email'                 => $email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'no_hp'                 => '0812-3456-7890',
            'instansi'              => 'Klub WA',
            'kategori_default'      => 'non_pemerintah',
            'device_name'           => 'booking-web',
        ])->assertRedirect(route('e-booking.otp.show'));

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertSame('6281234567890', $user->no_hp);

        $profile = BookingPenyewaProfile::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('6281234567890', $profile->no_hp);
    }

    #[Test]
    public function register_rejects_invalid_phone(): void
    {
        $this->post(route('e-booking.register.store'), [
            'nama'                  => 'Penyewa Invalid',
            'email'                 => 'bad.'.uniqid().'@test.local',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'no_hp'                 => 'abc',
            'kategori_default'      => 'non_pemerintah',
        ])->assertSessionHasErrors('no_hp');
    }

    #[Test]
    public function register_creates_unverified_user_and_sends_otp(): void
    {
        $email = 'otp.'.uniqid().'@test.local';

        $this->post(route('e-booking.register.store'), [
            'nama'                  => 'Penyewa OTP',
            'email'                 => $email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'no_hp'                 => '081234567890',
            'kategori_default'      => 'non_pemerintah',
        ])->assertRedirect(route('e-booking.otp.show'));

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_otp);
        $this->assertTrue($user->email_otp_expires_at->isFuture());
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function unverified_user_is_redirected_to_otp_page_when_accessing_penyewa_area(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active'         => 1,
        ]);
        $user->assignRole(Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail());

        $this->actingAs($user)
            ->get(route('e-booking.bookings.index'))
            ->assertRedirect(route('e-booking.otp.show'));
    }

    #[Test]
    public function unverified_user_can_logout(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active'         => 1,
        ]);
        $user->assignRole(Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail());

        $this->actingAs($user)
            ->post(route('e-booking.logout'))
            ->assertRedirect(route('e-booking.catalog'));

        $this->assertGuest();
    }

    #[Test]
    public function user_can_verify_email_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active'         => 1,
        ]);
        $user->assignRole(Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail());

        $user->forceFill([
            'email_otp'            => bcrypt('123456'),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->actingAs($user)
            ->post(route('e-booking.otp.verify'), ['otp' => '123456'])
            ->assertRedirect(route('e-booking.catalog'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull($user->fresh()->email_otp);
    }

    #[Test]
    public function user_cannot_verify_email_with_invalid_otp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'is_active'         => 1,
        ]);
        $user->assignRole(Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail());

        $user->forceFill([
            'email_otp'            => bcrypt('123456'),
            'email_otp_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->actingAs($user)
            ->from(route('e-booking.otp.show'))
            ->post(route('e-booking.otp.verify'), ['otp' => '000000'])
            ->assertRedirect(route('e-booking.otp.show'))
            ->assertSessionHasErrors('otp');

        $this->assertNull($user->fresh()->email_verified_at);
    }
}
