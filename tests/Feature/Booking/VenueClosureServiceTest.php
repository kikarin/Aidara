<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\VenueClosureService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit/service test untuk VenueClosureService (blok jadwal venue).
 * DatabaseTransactions — tidak wipe DB.
 */
class VenueClosureServiceTest extends TestCase
{
    use DatabaseTransactions;

    private BookingVenue $venue;

    private BookingArea $areaA;

    private BookingArea $areaB;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('booking_venue_closures')) {
            $this->markTestSkipped('Tabel booking_venue_closures belum ada — jalankan migrate dulu.');
        }

        $suffix = substr(uniqid(), -6);

        $this->venue = BookingVenue::query()->create([
            'code' => 'svc_venue_'.$suffix,
            'name' => 'Venue Service '.$suffix,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->areaA = BookingArea::query()->create([
            'venue_id' => $this->venue->id,
            'code' => 'court_a',
            'name' => 'Court A',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->areaB = BookingArea::query()->create([
            'venue_id' => $this->venue->id,
            'code' => 'court_b',
            'name' => 'Court B',
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }

    #[Test]
    public function operating_window_uses_default_and_venue_rule(): void
    {
        $service = app(VenueClosureService::class);

        $this->assertSame(['start' => '06:00', 'end' => '21:00'], $service->operatingWindow($this->venue->id));

        BookingRule::query()->create([
            'venue_id' => $this->venue->id,
            'key' => 'operating_hours',
            'value' => ['start' => '07:30', 'end' => '22:00'],
            'is_active' => true,
        ]);

        $this->assertSame(['start' => '07:30', 'end' => '22:00'], $service->operatingWindow($this->venue->id));
    }

    #[Test]
    public function once_full_day_follows_custom_operating_hours(): void
    {
        BookingRule::query()->create([
            'venue_id' => $this->venue->id,
            'key' => 'operating_hours',
            'value' => ['start' => '08:00', 'end' => '23:00'],
            'is_active' => true,
        ]);

        $date = Carbon::now()->addDays(5)->toDateString();

        $closure = app(VenueClosureService::class)->create([
            'venue_id' => $this->venue->id,
            'full_day' => true,
            'date' => $date,
            'reason' => 'Sewa penuh',
        ]);

        $this->assertTrue($closure->is_full_day);
        $this->assertSame('08:00', $closure->starts_at->format('H:i'));
        $this->assertSame('23:00', $closure->ends_at->format('H:i'));
    }

    #[Test]
    public function area_specific_closure_only_blocks_that_area(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => $this->areaA->id,
            'starts_at' => $monday->copy()->setTime(6, 0),
            'ends_at' => $monday->copy()->setTime(9, 0),
            'reason' => 'Latihan pagi',
            'is_active' => true,
        ]);

        $availability = app(AvailabilityService::class);

        $closedArea = $availability->check([
            'venue_id' => $this->venue->id,
            'area_id' => $this->areaA->id,
            'starts_at' => $monday->copy()->setTime(6, 0)->toDateTimeString(),
            'ends_at' => $monday->copy()->setTime(9, 0)->toDateTimeString(),
        ]);
        $this->assertTrue($closedArea['closed']);

        $openArea = $availability->check([
            'venue_id' => $this->venue->id,
            'area_id' => $this->areaB->id,
            'starts_at' => $monday->copy()->setTime(6, 0)->toDateTimeString(),
            'ends_at' => $monday->copy()->setTime(9, 0)->toDateTimeString(),
        ]);
        $this->assertFalse($openArea['closed']);
        $this->assertSame([], $openArea['closures']);
    }

    #[Test]
    public function overlapping_closures_reports_full_day_flag(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'starts_at' => $monday->copy()->setTime(6, 0),
            'ends_at' => $monday->copy()->setTime(21, 0),
            'reason' => 'Event',
            'is_full_day' => true,
            'is_active' => true,
        ]);

        $rows = app(VenueClosureService::class)->overlappingClosures(
            $this->venue->id,
            $monday->copy()->setTime(10, 0),
            $monday->copy()->setTime(11, 0),
        );

        $this->assertCount(1, $rows);
        $this->assertTrue($rows[0]['is_full_day']);
        $this->assertSame('Event', $rows[0]['reason']);
    }

    #[Test]
    public function delete_batch_removes_only_that_series(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);
        $service = app(VenueClosureService::class);

        $seriesOne = $service->createRecurring([
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeeks(2)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);

        $seriesTwo = $service->createRecurring([
            'venue_id' => $this->venue->id,
            'weekdays' => [3],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeeks(2)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);

        $deleted = $service->deleteBatch($seriesOne['batch_id']);

        $this->assertSame($seriesOne['created'], $deleted);
        $this->assertSame(0, BookingVenueClosure::query()->where('batch_id', $seriesOne['batch_id'])->count());
        $this->assertSame(
            $seriesTwo['created'],
            BookingVenueClosure::query()->where('batch_id', $seriesTwo['batch_id'])->count(),
        );
    }

    #[Test]
    public function recurring_rejects_unknown_venue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(VenueClosureService::class)->createRecurring([
            'venue_id' => 99_999_999,
            'weekdays' => [1],
            'start_date' => Carbon::now()->addWeek()->toDateString(),
            'end_date' => Carbon::now()->addWeeks(2)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);
    }

    #[Test]
    public function recurring_rejects_area_from_another_venue(): void
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

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Area tidak valid');

        app(VenueClosureService::class)->createRecurring([
            'venue_id' => $this->venue->id,
            'area_id' => $foreignArea->id,
            'weekdays' => [1],
            'start_date' => Carbon::now()->addWeek()->toDateString(),
            'end_date' => Carbon::now()->addWeeks(2)->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ]);
    }

    #[Test]
    public function recurring_dedupes_identical_rows(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);
        $payload = [
            'venue_id' => $this->venue->id,
            'weekdays' => [1],
            'start_date' => $monday->toDateString(),
            'end_date' => $monday->copy()->addWeek()->toDateString(),
            'start_time' => '16:00',
            'end_time' => '21:00',
        ];

        $service = app(VenueClosureService::class);
        $service->createRecurring($payload);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sudah diblok');

        $service->createRecurring($payload);
    }

    #[Test]
    public function month_overview_marks_past_days(): void
    {
        $overview = app(AvailabilityService::class)->monthOverview([
            'venue_id' => $this->venue->id,
            'month' => Carbon::now()->subMonth()->format('Y-m'),
        ]);

        $this->assertNotEmpty($overview['days']);
        foreach ($overview['days'] as $day) {
            $this->assertSame('past', $day['status']);
            $this->assertFalse($day['bookable']);
        }
    }

    #[Test]
    public function manual_closure_covering_whole_operating_window_marks_day_unavailable(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'starts_at' => $monday->copy()->setTime(6, 0),
            'ends_at' => $monday->copy()->setTime(21, 0),
            'reason' => 'Dipakai penuh',
            'is_full_day' => false,
            'is_active' => true,
        ]);

        $overview = app(AvailabilityService::class)->monthOverview([
            'venue_id' => $this->venue->id,
            'month' => $monday->format('Y-m'),
        ]);

        $day = collect($overview['days'])->firstWhere('date', $monday->toDateString());
        $this->assertNotNull($day);
        $this->assertSame('merah', $day['status']);
        $this->assertFalse($day['bookable']);
    }

    #[Test]
    public function full_day_closure_closes_every_slot(): void
    {
        $monday = Carbon::now()->startOfDay()->next(Carbon::MONDAY);

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'starts_at' => $monday->copy()->setTime(6, 0),
            'ends_at' => $monday->copy()->setTime(21, 0),
            'reason' => 'Event',
            'is_full_day' => true,
            'is_active' => true,
        ]);

        $slots = app(AvailabilityService::class)->daySlots([
            'venue_id' => $this->venue->id,
            'date' => $monday->toDateString(),
            'duration_hours' => 1,
        ]);

        $this->assertTrue($slots['full_day']);
        $this->assertSame('Event', $slots['full_day_reason']);
        $this->assertNotEmpty($slots['slots']);

        foreach ($slots['slots'] as $slot) {
            $this->assertFalse($slot['bookable']);
        }
    }
}
