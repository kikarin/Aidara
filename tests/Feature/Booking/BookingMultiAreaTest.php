<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Models\Role;
use App\Models\User;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingSubmitService;
use App\Services\Booking\PricingService;
use App\Support\Booking\BookingSatuan;
use App\Support\Booking\BookingStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingMultiAreaTest extends TestCase
{
    use DatabaseTransactions;

    private BookingVenue $venue;

    private BookingArea $areaA;

    private BookingArea $areaB;

    private BookingTarif $tarifA;

    private BookingTarif $tarifB;

    private BookingTarif $tarifVenueWide;

    private User $penyewa;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('bookings') || ! Schema::hasTable('booking_area_selected')) {
            $this->markTestSkipped('Tabel booking belum termigrasi.');
        }

        $this->seedMinimal();
    }

    #[Test]
    public function submit_two_areas_creates_two_items_and_pivot_rows(): void
    {
        $booking = app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [
                ['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id],
                ['area_id' => $this->areaB->id, 'tarif_id' => $this->tarifB->id],
            ],
        ]));

        $this->assertCount(2, $booking->items);
        $this->assertSame(2 * 150_000, $booking->subtotal);
        $this->assertSame(2 * 150_000, $booking->grand_total);

        $booking->refresh();
        $this->assertSame(
            [$this->areaA->id, $this->areaB->id],
            $booking->areas->pluck('id')->sort()->values()->all()
        );
    }

    #[Test]
    public function quote_multi_area_sums_each_area_line(): void
    {
        $quote = app(PricingService::class)->quote([
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
            'areas' => [
                ['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id],
                ['area_id' => $this->areaB->id, 'tarif_id' => $this->tarifB->id],
            ],
        ]);

        $this->assertCount(2, $quote['lines']);
        $this->assertSame(300_000, $quote['subtotal']);
        $this->assertSame($this->areaA->id, $quote['lines'][0]['area_id']);
        $this->assertSame('Court B', $quote['lines'][1]['area']['name']);
    }

    #[Test]
    public function pengajuan_does_not_block_but_sets_flag(): void
    {
        app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id]],
        ]));

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaA->id, $this->areaB->id],
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
        ]);

        $this->assertSame('hijau', $check['status']);
        $this->assertTrue($check['bookable']);
        $this->assertTrue($check['pengajuan']);
        $this->assertSame([], $check['conflicts']);
    }

    #[Test]
    public function second_pengajuan_same_slot_still_allowed(): void
    {
        app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id]],
        ]));

        $second = app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaB->id, 'tarif_id' => $this->tarifB->id]],
        ]));

        $this->assertSame(BookingStatus::MENUNGGU_APPROVAL, $second->status);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaB->id],
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
        ]);

        $this->assertSame('hijau', $check['status']);
        $this->assertTrue($check['pengajuan']);
    }

    #[Test]
    public function approved_area_booking_blocks_merah(): void
    {
        $booking = app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id]],
        ]));
        $booking->update(['status' => BookingStatus::APPROVED]);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaA->id],
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
        ]);

        $this->assertSame('merah', $check['status']);
        $this->assertFalse($check['bookable']);
        $this->assertFalse($check['pengajuan']);
    }

    #[Test]
    public function approved_area_booking_blocks_whole_venue_request(): void
    {
        $booking = app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id]],
        ]));
        $booking->update(['status' => BookingStatus::APPROVED]);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_ids' => [],
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
        ]);

        $this->assertSame('merah', $check['status']);
        $this->assertFalse($check['bookable']);
    }

    #[Test]
    public function approved_whole_venue_booking_blocks_area_request(): void
    {
        $booking = app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'tarif_id' => $this->tarifVenueWide->id,
        ]));
        $booking->update(['status' => BookingStatus::APPROVED]);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaB->id],
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
        ]);

        $this->assertSame('merah', $check['status']);
        $this->assertFalse($check['bookable']);
    }

    #[Test]
    public function duplicate_areas_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('lebih dari sekali');

        app(PricingService::class)->quote([
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
            'areas' => [
                ['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id],
                ['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id],
            ],
        ]);
    }

    #[Test]
    public function mismatched_area_tarif_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bukan untuk area');

        app(PricingService::class)->quote([
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
            'areas' => [
                ['area_id' => $this->areaB->id, 'tarif_id' => $this->tarifA->id],
            ],
        ]);
    }

    #[Test]
    public function day_slots_intersect_all_selected_areas(): void
    {
        $date = now()->addDay()->toDateString();

        \App\Models\Booking\BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => $this->areaA->id,
            'starts_at' => $date.' 10:00:00',
            'ends_at' => $date.' 12:00:00',
            'reason' => 'Area A tutup',
            'is_active' => true,
        ]);

        $result = app(AvailabilityService::class)->daySlots([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaA->id, $this->areaB->id],
            'date' => $date,
            'duration_hours' => 1,
        ]);

        $blocked = collect($result['slots'])->firstWhere('starts_at', $date.' 10:00:00');
        $this->assertNotNull($blocked);
        $this->assertFalse($blocked['bookable']);

        $free = collect($result['slots'])->firstWhere('starts_at', $date.' 08:00:00');
        $this->assertNotNull($free);
        $this->assertTrue($free['bookable']);
    }

    #[Test]
    public function day_slots_mark_pengajuan_flag(): void
    {
        app(BookingSubmitService::class)->submit($this->penyewa, $this->payload([
            'areas' => [['area_id' => $this->areaA->id, 'tarif_id' => $this->tarifA->id]],
        ]));

        $date = now()->addDays(2)->toDateString();

        $result = app(AvailabilityService::class)->daySlots([
            'venue_id' => $this->venue->id,
            'area_ids' => [$this->areaA->id, $this->areaB->id],
            'date' => $date,
            'duration_hours' => 1,
        ]);

        $slot = collect($result['slots'])->firstWhere('starts_at', $date.' 08:00:00');
        $this->assertNotNull($slot);
        $this->assertSame('hijau', $slot['status']);
        $this->assertTrue($slot['bookable']);
        $this->assertTrue($slot['pengajuan']);
    }

    /** @param  array<string, mixed>  $overrides */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'kategori_tarif' => 'non_pemerintah',
            'tujuan' => 'Latihan multi area',
            'starts_at' => $this->start(),
            'ends_at' => $this->end(),
            'terms_accepted' => true,
        ], $overrides);
    }

    private function start(): string
    {
        return now()->addDays(2)->setTime(8, 0)->toDateTimeString();
    }

    private function end(): string
    {
        return now()->addDays(2)->setTime(9, 0)->toDateTimeString();
    }

    private function seedMinimal(): void
    {
        $penyewaRole = Role::query()->firstOrCreate(
            ['name' => 'penyewa', 'guard_name' => 'web'],
            ['bg' => 'bg-success', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->venue = BookingVenue::query()->create([
            'code' => 'multi_area_venue_'.$suffix,
            'name' => 'Venue Multi '.$suffix,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->areaA = BookingArea::query()->create([
            'venue_id' => $this->venue->id,
            'code' => 'court_a_'.$suffix,
            'name' => 'Court A',
            'is_tentative' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->areaB = BookingArea::query()->create([
            'venue_id' => $this->venue->id,
            'code' => 'court_b_'.$suffix,
            'name' => 'Court B',
            'is_tentative' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $tarifFactory = fn (BookingArea $area, string $code) => BookingTarif::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => $area->id,
            'code' => $code,
            'uraian' => 'Latihan '.$area->name,
            'satuan' => BookingSatuan::PER_HOUR,
            'tarif_pemerintah' => 100_000,
            'tarif_non_pemerintah' => 150_000,
            'category' => 'olahraga',
            'is_active' => true,
        ]);

        $this->tarifA = $tarifFactory($this->areaA, 'tarif_a_'.$suffix);
        $this->tarifB = $tarifFactory($this->areaB, 'tarif_b_'.$suffix);

        $this->tarifVenueWide = BookingTarif::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'code' => 'tarif_wide_'.$suffix,
            'uraian' => 'Sewa seluruh venue',
            'satuan' => BookingSatuan::PER_HOUR,
            'tarif_pemerintah' => 200_000,
            'tarif_non_pemerintah' => 300_000,
            'category' => 'olahraga',
            'is_active' => true,
        ]);

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'operating_hours'],
            ['value' => ['start' => '06:00', 'end' => '21:00'], 'is_active' => true]
        );
        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'buffer_before_days'],
            ['value' => 0, 'is_active' => true]
        );
        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'buffer_after_days'],
            ['value' => 0, 'is_active' => true]
        );

        BookingSetting::setValue('payment_expire_hours', 48);
        BookingPriorityRule::query()->firstOrCreate(
            ['code' => 'umum_komersial'],
            ['name' => 'Umum', 'priority_order' => 7, 'is_active' => true]
        );

        $this->penyewa = User::query()->create([
            'name' => 'Penyewa Multi '.$suffix,
            'email' => 'multi.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $penyewaRole->id,
        ]);
        $this->penyewa->assignRole($penyewaRole);

        BookingPenyewaProfile::query()->create([
            'user_id' => $this->penyewa->id,
            'nama' => $this->penyewa->name,
            'nik' => '3201010101010099',
            'no_hp' => '081234567890',
            'alamat' => 'Bogor',
            'kategori_default' => 'non_pemerintah',
        ]);
    }
}
