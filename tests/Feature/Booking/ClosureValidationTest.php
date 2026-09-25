<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Validasi form blok jadwal (HTTP) + edge case controller/service.
 * DatabaseTransactions — tidak wipe DB.
 */
class ClosureValidationTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private BookingVenue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('booking_venue_closures')) {
            $this->markTestSkipped('Tabel booking_venue_closures belum ada — jalankan migrate dulu.');
        }

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_upt', 'guard_name' => 'web'],
            ['bg' => 'bg-info', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->admin = User::query()->create([
            'name' => 'Admin Validasi '.$suffix,
            'email' => 'admin.validasi.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);

        $this->venue = BookingVenue::query()->create([
            'code' => 'valid_venue_'.$suffix,
            'name' => 'Venue Validasi '.$suffix,
            'is_active' => true,
            'sort_order' => 99,
        ]);
    }

    #[Test]
    public function weekly_requires_weekdays_and_dates(): void
    {
        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'start_time' => '16:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHasErrors(['weekdays', 'start_date', 'end_date']);
    }

    #[Test]
    public function weekly_rejects_end_date_before_start_date(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'weekdays' => [1],
                'start_date' => $monday->copy()->addWeek()->toDateString(),
                'end_date' => $monday->toDateString(),
                'start_time' => '16:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHasErrors('end_date');
    }

    #[Test]
    public function weekly_requires_times_when_not_full_day(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'weekdays' => [1],
                'start_date' => $monday->toDateString(),
                'end_date' => $monday->toDateString(),
            ])
            ->assertSessionHasErrors(['start_time', 'end_time']);
    }

    #[Test]
    public function weekly_full_day_skips_times_and_creates_rows(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'full_day' => 1,
                'venue_id' => $this->venue->id,
                'weekdays' => [1],
                'start_date' => $monday->toDateString(),
                'end_date' => $monday->copy()->addWeek()->toDateString(),
                'reason' => 'Event',
            ])
            ->assertSessionHasNoErrors();

        $rows = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->get();
        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn (BookingVenueClosure $row) => $row->is_full_day));
    }

    #[Test]
    public function once_full_day_requires_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'once',
                'full_day' => 1,
                'venue_id' => $this->venue->id,
            ])
            ->assertSessionHasErrors('date');
    }

    #[Test]
    public function rejects_unknown_venue(): void
    {
        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'once',
                'venue_id' => 99_999_999,
                'starts_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
                'ends_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            ])
            ->assertSessionHasErrors('venue_id');
    }

    #[Test]
    public function rejects_area_from_another_venue(): void
    {
        $otherVenue = BookingVenue::query()->create([
            'code' => 'other_'.substr(uniqid(), -6),
            'name' => 'Venue Lain',
            'is_active' => true,
        ]);

        $foreignArea = BookingArea::query()->create([
            'venue_id' => $otherVenue->id,
            'code' => 'foreign',
            'name' => 'Foreign Area',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->from(route('e-booking.admin.closures.index'))
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'once',
                'venue_id' => $this->venue->id,
                'area_id' => $foreignArea->id,
                'starts_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
                'ends_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count());
    }

    #[Test]
    public function rejects_recurring_series_over_row_limit(): void
    {
        $this->actingAs($this->admin)
            ->from(route('e-booking.admin.closures.index'))
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'weekdays' => [0, 1, 2, 3, 4, 5, 6],
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(300)->toDateString(),
                'start_time' => '16:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count());
    }

    #[Test]
    public function rejects_range_longer_than_one_year(): void
    {
        $this->actingAs($this->admin)
            ->from(route('e-booking.admin.closures.index'))
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'weekdays' => [1],
                'start_date' => Carbon::now()->toDateString(),
                'end_date' => Carbon::now()->addDays(400)->toDateString(),
                'start_time' => '16:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count());
    }

    #[Test]
    public function duplicate_series_is_rejected_without_extra_rows(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);
        $payload = [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeek()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ];

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), $payload);
        $countAfterFirst = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count();
        $this->assertSame(2, $countAfterFirst);

        $this->actingAs($this->admin)
            ->from(route('e-booking.admin.closures.index'))
            ->post(route('e-booking.admin.closures.store'), $payload)
            ->assertSessionHas('error');

        $this->assertSame(
            $countAfterFirst,
            BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count(),
        );
    }
}
