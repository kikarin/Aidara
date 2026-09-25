<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingFacility;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
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

        $facility = BookingFacility::query()->create([
            'code' => 'fac_'.substr(uniqid(), -6),
            'name' => 'Fasilitas Uji',
            'is_active' => true,
        ]);

        $termsKey = 'terms_uji_'.substr(uniqid(), -6);
        BookingSetting::setValue($termsKey, ['title' => 'Tata Tertib Uji', 'points' => ['Poin A']]);

        $response = $this->actingAs($this->admin)->post(route('e-booking.admin.venues.store'), [
            'code' => $code,
            'name' => 'Venue Baru',
            'description' => 'Deskripsi',
            'sort_order' => 5,
            'is_active' => true,
            'operating_start' => '07:00',
            'operating_end' => '22:00',
            'operating_days' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
            'facility_ids' => [$facility->id],
            'terms_key' => $termsKey,
            'cover' => UploadedFile::fake()->image('cover.png', 400, 300),
            'areas' => [
                ['code' => 'lapangan_utama', 'name' => 'Lapangan Utama', 'is_active' => true, 'sort_order' => 1],
            ],
            'tarifs' => [
                [
                    'area_code' => 'lapangan_utama',
                    'uraian' => 'Latihan Malam',
                    'satuan' => 'per_hour',
                    'tarif_pemerintah' => 200_000,
                    'tarif_non_pemerintah' => 500_000,
                    'category' => 'olahraga',
                    'time_slot' => 'malam',
                    'day_type' => 'weekend',
                ],
            ],
        ]);

        $venue = BookingVenue::query()->where('code', $code)->first();
        $this->assertNotNull($venue);

        $response->assertRedirect(route('e-booking.admin.venues.show', $venue->id));

        $this->assertSame('Venue Baru', $venue->name);
        $this->assertNotNull($venue->cover_path);
        Storage::disk('public')->assertExists($venue->cover_path);

        $hours = BookingRule::query()->where('venue_id', $venue->id)->where('key', 'operating_hours')->first();
        $this->assertNotNull($hours);
        $this->assertSame('07:00', $hours->value['start']);
        $this->assertSame('22:00', $hours->value['end']);

        $days = BookingRule::query()->where('venue_id', $venue->id)->where('key', 'operating_days')->first();
        $this->assertNotNull($days);
        $this->assertCount(5, $days->value);

        $area = BookingArea::query()->where('venue_id', $venue->id)->where('code', 'lapangan_utama')->first();
        $this->assertNotNull($area);

        $tarif = BookingTarif::query()->where('venue_id', $venue->id)->where('uraian', 'Latihan Malam')->first();
        $this->assertNotNull($tarif);
        $this->assertSame($area->id, $tarif->area_id);
        $this->assertSame(200_000, (int) $tarif->tarif_pemerintah);
        $this->assertSame(500_000, (int) $tarif->tarif_non_pemerintah);
        $this->assertSame('malam', $tarif->time_slot);
        $this->assertSame('weekend', $tarif->day_type);

        $this->assertTrue($venue->facilities()->whereKey($facility->id)->exists());

        $termsRule = BookingRule::query()->where('venue_id', $venue->id)->where('key', 'terms_key')->first();
        $this->assertNotNull($termsRule);
        $this->assertSame($termsKey, $termsRule->value);
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
    public function admin_can_update_venue_rules(): void
    {
        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Venue Rules',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('e-booking.admin.venues.rules.update', $venue->id), [
                'rules' => [
                    'operating_hours' => ['start' => '07:00', 'end' => '22:00', 'timezone' => 'Asia/Jakarta'],
                    'booking_horizon_days' => 14,
                    'allow_same_day_reschedule' => true,
                ],
            ])
            ->assertRedirect();

        $rule = BookingRule::query()->where('venue_id', $venue->id)->where('key', 'operating_hours')->first();
        $this->assertNotNull($rule);
        $this->assertSame('07:00', $rule->value['start']);

        $horizon = BookingRule::query()->where('venue_id', $venue->id)->where('key', 'booking_horizon_days')->first();
        $this->assertSame(14, (int) $horizon->value);
    }

    #[Test]
    public function tarif_min_max_hours_saved_to_meta(): void
    {
        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Venue Meta',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.tarifs.store', $venue->id), [
                'uraian' => 'Latihan Meta',
                'satuan' => 'per_hour',
                'category' => 'olahraga',
                'tarif_non_pemerintah' => 150_000,
                'min_hours' => 2,
                'max_hours' => 4,
                'is_active' => true,
            ])
            ->assertRedirect();

        $tarif = BookingTarif::query()->where('venue_id', $venue->id)->where('uraian', 'Latihan Meta')->first();
        $this->assertNotNull($tarif);
        $this->assertSame(2, $tarif->meta['min_hours']);
        $this->assertSame(4, $tarif->meta['max_hours']);
    }

    #[Test]
    public function tarif_requires_at_least_one_price(): void
    {
        $venue = BookingVenue::query()->create([
            'code' => 'venue_'.substr(uniqid(), -6),
            'name' => 'Venue Harga',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.tarifs.store', $venue->id), [
                'uraian' => 'Tanpa Harga',
                'satuan' => 'per_hour',
                'category' => 'olahraga',
                'is_active' => true,
            ])
            ->assertSessionHasErrors('tarif_pemerintah');
    }

    #[Test]
    public function venue_creation_supports_different_tarif_per_area(): void
    {
        $code = 'venue_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.venues.store'), [
                'code' => $code,
                'name' => 'Venue Multi Area',
                'is_active' => true,
                'areas' => [
                    ['code' => 'indoor_a', 'name' => 'Indoor A', 'is_active' => true],
                    ['code' => 'outdoor_c', 'name' => 'Outdoor C', 'is_active' => true],
                ],
                'tarifs' => [
                    ['area_code' => 'indoor_a', 'uraian' => 'Indoor A', 'satuan' => 'per_hour', 'tarif_pemerintah' => 200_000, 'tarif_non_pemerintah' => 500_000],
                    ['area_code' => 'outdoor_c', 'uraian' => 'Outdoor C', 'satuan' => 'per_hour', 'tarif_pemerintah' => 100_000, 'tarif_non_pemerintah' => 300_000],
                ],
            ])
            ->assertRedirect();

        $venue = BookingVenue::query()->where('code', $code)->firstOrFail();
        $areas = BookingArea::query()->where('venue_id', $venue->id)->get()->keyBy('code');

        $indoor = BookingTarif::query()->where('venue_id', $venue->id)->where('uraian', 'Indoor A')->first();
        $outdoor = BookingTarif::query()->where('venue_id', $venue->id)->where('uraian', 'Outdoor C')->first();

        $this->assertNotNull($indoor);
        $this->assertNotNull($outdoor);
        $this->assertSame($areas['indoor_a']->id, $indoor->area_id);
        $this->assertSame($areas['outdoor_c']->id, $outdoor->area_id);
        $this->assertSame(500_000, (int) $indoor->tarif_non_pemerintah);
        $this->assertSame(300_000, (int) $outdoor->tarif_non_pemerintah);
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
