<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Admin UPT web (Inertia) venue/area/tarif management.
 * Memakai DatabaseTransactions — tidak wipe DB.
 */
class VenueAdminTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('booking_venues')) {
            $this->markTestSkipped('Tabel booking belum ada — jalankan migrate dulu.');
        }

        Storage::fake('public');

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_upt', 'guard_name' => 'web'],
            ['bg' => 'bg-info', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );
        Role::query()->firstOrCreate(
            ['name' => 'penyewa', 'guard_name' => 'web'],
            ['bg' => 'bg-success', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->admin = User::query()->create([
            'name' => 'Admin Venue '.$suffix,
            'email' => 'admin.venue.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);
    }

    #[Test]
    public function admin_can_create_venue_with_cover(): void
    {
        $code = 'venue_'.substr(uniqid(), -6);

        $response = $this->actingAs($this->admin)->post(route('e-booking.admin.venues.store'), [
            'code' => $code,
            'name' => 'Venue Baru',
            'description' => 'Deskripsi',
            'sort_order' => 5,
            'is_active' => true,
            'cover' => UploadedFile::fake()->image('cover.png', 400, 300),
        ]);

        $venue = BookingVenue::query()->where('code', $code)->first();
        $this->assertNotNull($venue);

        $response->assertRedirect(route('e-booking.admin.venues.show', $venue->id));

        $this->assertSame('Venue Baru', $venue->name);
        $this->assertNotNull($venue->cover_path);
        Storage::disk('public')->assertExists($venue->cover_path);
    }

    #[Test]
    public function admin_can_open_create_and_edit_forms(): void
    {
        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.venues.create'))
            ->assertOk();

        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Untuk Edit',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.venues.edit', $venue->id))
            ->assertOk();
    }

    #[Test]
    public function venue_code_must_be_unique(): void
    {
        $code = 'venue_'.substr(uniqid(), -6);
        BookingVenue::query()->create(['code' => $code, 'name' => 'Existing', 'is_active' => true]);

        $response = $this->actingAs($this->admin)->post(route('e-booking.admin.venues.store'), [
            'code' => $code,
            'name' => 'Duplikat',
        ]);

        $response->assertSessionHasErrors('code');
    }

    #[Test]
    public function admin_can_update_and_toggle_venue(): void
    {
        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Awal',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put(route('e-booking.admin.venues.update', $venue->id), [
                'code' => $venue->code,
                'name' => 'Diubah',
                'is_active' => true,
                'sort_order' => 3,
            ])
            ->assertRedirect();

        $this->assertSame('Diubah', $venue->fresh()->name);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.venues.toggle', $venue->id))
            ->assertRedirect();

        $this->assertFalse((bool) $venue->fresh()->is_active);
    }

    #[Test]
    public function admin_can_manage_areas_and_tarifs(): void
    {
        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Venue Area Tarif',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.areas.store', $venue->id), [
                'code' => 'lapangan_utama',
                'name' => 'Lapangan Utama',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $area = BookingArea::query()->where('venue_id', $venue->id)->where('code', 'lapangan_utama')->first();
        $this->assertNotNull($area);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.tarifs.store', $venue->id), [
                'area_id' => $area->id,
                'uraian' => 'Latihan',
                'satuan' => 'per_hour',
                'tarif_pemerintah' => 100_000,
                'tarif_non_pemerintah' => 150_000,
                'category' => 'olahraga',
                'is_active' => true,
            ])
            ->assertRedirect();

        $tarif = BookingTarif::query()->where('venue_id', $venue->id)->where('uraian', 'Latihan')->first();
        $this->assertNotNull($tarif);
        $this->assertSame(150_000, (int) $tarif->tarif_non_pemerintah);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.tarifs.toggle', $tarif->id))
            ->assertRedirect();

        $this->assertFalse((bool) $tarif->fresh()->is_active);
    }

    #[Test]
    public function non_admin_is_redirected_to_admin_login(): void
    {
        $penyewaRole = Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail();

        $penyewa = User::query()->create([
            'name' => 'Penyewa',
            'email' => 'penyewa.'.substr(uniqid(), -6).'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'current_role_id' => $penyewaRole->id,
        ]);
        $penyewa->assignRole($penyewaRole);

        $this->actingAs($penyewa)
            ->get(route('e-booking.admin.venues.index'))
            ->assertRedirect(route('e-booking.admin.login'));
    }
}
