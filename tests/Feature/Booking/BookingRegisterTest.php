<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
        ])->assertRedirect(route('e-booking.catalog'));

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
}
