<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use App\Models\Role;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Blok jadwal berulang mingguan (Closures) — DatabaseTransactions, tidak wipe DB.
 */
class ClosureRecurringTest extends TestCase
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
            'name' => 'Admin Closure '.$suffix,
            'email' => 'admin.closure.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);

        $this->venue = BookingVenue::query()->create([
            'code' => 'closure_venue_'.$suffix,
            'name' => 'Venue Closure '.$suffix,
            'is_active' => true,
            'sort_order' => 99,
        ]);
    }

    #[Test]
    public function weekly_creates_one_row_per_matching_day_with_shared_batch(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);
        $endDate = $monday->copy()->addWeeks(3);

        $response = $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $endDate->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
            'reason' => 'Latihan atlet Dispora',
        ]);

        $response->assertRedirect(route('e-booking.admin.closures.index', ['venue_id' => $this->venue->id]));

        $rows = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->get();

        $this->assertCount(4, $rows);
        $this->assertCount(1, $rows->pluck('batch_id')->unique());
        $this->assertNotNull($rows->first()->batch_id);

        foreach ($rows as $row) {
            $this->assertSame(Carbon::MONDAY, $row->starts_at->dayOfWeek);
            $this->assertSame('16:00', $row->starts_at->format('H:i'));
            $this->assertSame('21:00', $row->ends_at->format('H:i'));
            $this->assertSame('Latihan atlet Dispora', $row->reason);
        }
    }

    #[Test]
    public function weekly_blocks_availability_on_matching_day(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeeks(2)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
            'reason' => 'Latihan atlet',
        ]);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'starts_at' => $monday->copy()->setTime(16, 0)->toDateTimeString(),
            'ends_at' => $monday->copy()->setTime(21, 0)->toDateTimeString(),
        ]);

        $this->assertSame('merah', $check['status']);
        $this->assertTrue($check['closed']);
        $this->assertFalse($check['bookable']);
    }

    #[Test]
    public function destroys_whole_batch(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1, 3],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeeks(1)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);

        $batchId = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->value('batch_id');
        $this->assertNotNull($batchId);

        $countBefore = BookingVenueClosure::query()->where('batch_id', $batchId)->count();
        $this->assertGreaterThan(1, $countBefore);

        $response = $this->actingAs($this->admin)->delete(
            route('e-booking.admin.closures.destroyBatch', $batchId)
        );

        $response->assertRedirect(route('e-booking.admin.closures.index', ['venue_id' => $this->venue->id]));
        $this->assertSame(0, BookingVenueClosure::query()->where('batch_id', $batchId)->count());
    }

    #[Test]
    public function weekly_full_day_blocks_whole_day_in_month_overview(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);
        $endDate = $monday->copy()->addWeeks(2);

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'full_day' => 1,
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => 'Event Dispora',
        ]);

        $rows = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->get();
        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $this->assertTrue($row->is_full_day);
            $this->assertSame('06:00', $row->starts_at->format('H:i'));
            $this->assertSame('21:00', $row->ends_at->format('H:i'));
        }

        $overview = app(AvailabilityService::class)->monthOverview([
            'venue_id' => $this->venue->id,
            'month' => $monday->format('Y-m'),
        ]);

        $day = collect($overview['days'])->firstWhere('date', $monday->toDateString());
        $this->assertNotNull($day);
        $this->assertSame('merah', $day['status']);
        $this->assertFalse($day['bookable']);
        $this->assertStringContainsString('Penuh', (string) $day['reason']);
        $this->assertStringContainsString('Event Dispora', (string) $day['reason']);
    }

    #[Test]
    public function partial_closure_keeps_other_hours_bookable(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->toDateString(),
            'start_time' => '06:00',
            'end_time' => '09:00',
            'reason' => 'Latihan pagi atlet',
        ]);

        $dayClosure = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->first();
        $this->assertNotNull($dayClosure);
        $this->assertFalse($dayClosure->is_full_day);

        $overview = app(AvailabilityService::class)->monthOverview([
            'venue_id' => $this->venue->id,
            'month' => $monday->format('Y-m'),
        ]);

        $day = collect($overview['days'])->firstWhere('date', $monday->toDateString());
        $this->assertNotNull($day);
        $this->assertTrue($day['bookable']);
        $this->assertNotSame('merah', $day['status']);

        $slots = app(AvailabilityService::class)->daySlots([
            'venue_id' => $this->venue->id,
            'date' => $monday->toDateString(),
            'duration_hours' => 1,
        ]);

        $this->assertFalse($slots['full_day']);
        $this->assertNotEmpty($slots['partial_notes']);

        $closedSlot = collect($slots['slots'])->firstWhere('label', '06:00–07:00');
        $openSlot = collect($slots['slots'])->firstWhere('label', '10:00–11:00');
        $this->assertFalse($closedSlot['bookable']);
        $this->assertTrue($openSlot['bookable']);
    }

    #[Test]
    public function once_full_day_creates_single_row_with_operating_hours(): void
    {
        $date = Carbon::now()->addDays(3)->toDateString();

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'once',
            'full_day' => 1,
            'venue_id' => $this->venue->id,
            'date' => $date,
            'reason' => 'Sewa penuh 1 hari',
        ]);

        $row = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->sole();
        $this->assertTrue($row->is_full_day);
        $this->assertSame($date, $row->starts_at->toDateString());
        $this->assertSame('06:00', $row->starts_at->format('H:i'));
        $this->assertSame('21:00', $row->ends_at->format('H:i'));
    }

    #[Test]
    public function skips_past_dates_and_rejects_all_past_range(): void
    {
        $pastMonday = Carbon::now()->startOfDay()->subWeeks(2)->startOfWeek(Carbon::MONDAY);

        $this->actingAs($this->admin)
            ->from(route('e-booking.admin.closures.index'))
            ->post(route('e-booking.admin.closures.store'), [
                'mode' => 'weekly',
                'venue_id' => $this->venue->id,
                'weekdays' => [1],
                'start_date' => $pastMonday->toDateString(),
                'end_date' => $pastMonday->copy()->addWeek()->toDateString(),
                'start_time' => '16:00',
                'end_time' => '21:00',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, BookingVenueClosure::query()->where('venue_id', $this->venue->id)->count());

        $futureMonday = $pastMonday->copy()->addWeeks(3);

        $this->actingAs($this->admin)->post(route('e-booking.admin.closures.store'), [
            'mode' => 'weekly',
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $pastMonday->toDateString(),
            'end_date' => $futureMonday->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);

        $rows = BookingVenueClosure::query()->where('venue_id', $this->venue->id)->get();
        $this->assertCount(1, $rows);
        $this->assertTrue($rows->first()->starts_at->gte(now()->startOfDay()));
    }
}
