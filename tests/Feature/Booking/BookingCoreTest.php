<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Models\Booking\BookingVenueClosure;
use App\Models\Role;
use App\Models\User;
use App\Services\Booking\AdminApprovalService;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingPaymentExpireService;
use App\Services\Booking\BookingSubmitService;
use App\Services\Booking\PricingService;
use App\Services\Booking\VenuePolicyService;
use App\Support\Booking\BookingSatuan;
use App\Support\Booking\BookingStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests memakai DatabaseTransactions (rollback) — tidak wipe DB.
 * Membutuhkan tabel booking_* sudah termigrasi.
 */
class BookingCoreTest extends TestCase
{
    use DatabaseTransactions;

    private BookingVenue $venue;

    private BookingArea $area;

    private BookingTarif $tarif;

    private User $penyewa;

    private User $admin;

    private BookingPenyewaProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('bookings')) {
            $this->markTestSkipped('Tabel booking belum ada — jalankan migrate dulu.');
        }

        Storage::fake('public');
        $this->seedMinimal();
    }

    #[Test]
    public function quote_supports_per_hour_and_addons(): void
    {
        $addon = BookingAddon::query()->updateOrCreate(
            ['code' => 'loading_test_'.uniqid()],
            ['name' => 'Loading Test', 'harga' => 100_000, 'is_active' => true, 'sort_order' => 99]
        );

        $quote = app(PricingService::class)->quote([
            'tarif_id' => $this->tarif->id,
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
            'addon_ids' => [['id' => $addon->id, 'qty' => 1]],
        ]);

        $this->assertSame(2 * 150_000, $quote['subtotal']);
        $this->assertSame(100_000, $quote['addon_total']);
        $this->assertSame(400_000, $quote['grand_total']);
    }

    #[Test]
    public function submit_requires_contact_phone(): void
    {
        $this->profile->forceFill(['no_hp' => null])->save();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nomor HP dan email');

        app(BookingSubmitService::class)->submit($this->penyewa, $this->validSubmitPayload());
    }

    #[Test]
    public function tennis_horizon_rejects_beyond_7_days(): void
    {
        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'booking_horizon_days'],
            ['value' => 7, 'is_active' => true]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('7 hari');

        app(AvailabilityService::class)->assertBookable([
            'venue_id' => $this->venue->id,
            'area_id' => $this->area->id,
            'starts_at' => now()->addDays(10)->setTime(8, 0)->toDateTimeString(),
            'ends_at' => now()->addDays(10)->setTime(9, 0)->toDateTimeString(),
        ]);
    }

    #[Test]
    public function venue_closure_marks_slot_merah_and_rejects_bookable(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('booking_venue_closures')) {
            $this->markTestSkipped('Tabel booking_venue_closures belum ada — jalankan migrate dulu.');
        }

        $start = now()->addDay()->setTime(8, 0);
        $end = now()->addDay()->setTime(10, 0);

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'starts_at' => $start->copy()->subHour(),
            'ends_at' => $end->copy()->addHour(),
            'reason' => 'Maintenance',
            'is_active' => true,
        ]);

        $check = app(AvailabilityService::class)->check([
            'venue_id' => $this->venue->id,
            'area_id' => $this->area->id,
            'starts_at' => $start->toDateTimeString(),
            'ends_at' => $end->toDateTimeString(),
        ]);

        $this->assertSame('merah', $check['status']);
        $this->assertTrue($check['closed']);
        $this->assertFalse($check['bookable']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maintenance');

        app(AvailabilityService::class)->assertBookable([
            'venue_id' => $this->venue->id,
            'area_id' => $this->area->id,
            'starts_at' => $start->toDateTimeString(),
            'ends_at' => $end->toDateTimeString(),
        ]);
    }

    #[Test]
    public function quote_enforces_meta_min_and_max_hours(): void
    {
        $this->tarif->forceFill(['meta' => ['min_hours' => 3]])->save();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('minimal');

        app(PricingService::class)->quote([
            'tarif_id' => $this->tarif->id,
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
        ]);
    }

    #[Test]
    public function quote_enforces_meta_max_hours(): void
    {
        $this->tarif->forceFill(['meta' => ['max_hours' => 2]])->save();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('maksimal');

        app(PricingService::class)->quote([
            'tarif_id' => $this->tarif->id,
            'kategori_tarif' => 'non_pemerintah',
            'starts_at' => now()->addDay()->setTime(8, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
        ]);
    }

    #[Test]
    public function day_slots_marks_past_and_closed_hours(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('booking_venue_closures')) {
            $this->markTestSkipped('Tabel booking_venue_closures belum ada — jalankan migrate dulu.');
        }

        $date = now()->addDay()->toDateString();

        BookingVenueClosure::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => null,
            'starts_at' => $date.' 10:00:00',
            'ends_at' => $date.' 12:00:00',
            'reason' => 'Maintenance',
            'is_active' => true,
        ]);

        $result = app(AvailabilityService::class)->daySlots([
            'venue_id' => $this->venue->id,
            'area_id' => $this->area->id,
            'date' => $date,
            'duration_hours' => 1,
        ]);

        $this->assertNotEmpty($result['slots']);
        $closed = collect($result['slots'])->firstWhere('starts_at', $date.' 10:00:00');
        $this->assertNotNull($closed);
        $this->assertFalse($closed['bookable']);
        $this->assertSame('merah', $closed['status']);
    }

    #[Test]
    public function rain_before_20_minutes_allows_reschedule(): void
    {
        $booking = $this->makeConfirmedBooking();

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'force_majeure_rain_after_play_minutes'],
            ['value' => 20, 'is_active' => true]
        );

        $result = app(VenuePolicyService::class)->recordRain($booking, $this->admin, [
            'play_elapsed_minutes' => 10,
        ]);

        $this->assertSame('reschedule', $result['decision']);
        $this->assertSame(BookingStatus::RESCHEDULE_PENDING, $result['booking']->status);
    }

    #[Test]
    public function rain_after_20_minutes_no_compensation(): void
    {
        $booking = $this->makeConfirmedBooking();

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $this->venue->id, 'key' => 'force_majeure_rain_after_play_minutes'],
            ['value' => 20, 'is_active' => true]
        );

        $result = app(VenuePolicyService::class)->recordRain($booking, $this->admin, [
            'play_elapsed_minutes' => 25,
        ]);

        $this->assertSame('no_compensation', $result['decision']);
        $this->assertSame(BookingStatus::NO_COMPENSATION, $result['booking']->status);
    }

    #[Test]
    public function peer_conflict_syncs_perlu_klarifikasi(): void
    {
        $rule = BookingPriorityRule::query()->updateOrCreate(
            ['code' => 'umum_komersial'],
            ['name' => 'Umum', 'priority_order' => 7, 'is_active' => true]
        );

        $a = $this->makeSubmittedBooking(['priority_rule_id' => $rule->id]);
        $bUser = $this->makePenyewa('peer.'.uniqid().'@test.local');
        $b = $this->makeSubmittedBooking([
            'user_id' => $bUser->id,
            'penyewa_profile_id' => BookingPenyewaProfile::query()->where('user_id', $bUser->id)->value('id'),
            'priority_rule_id' => $rule->id,
        ]);

        $result = app(AdminApprovalService::class)->approve($a, $this->admin);

        $this->assertSame(BookingStatus::PERLU_KLARIFIKASI, $result['booking']->fresh()->status);
        $this->assertTrue($result['conflict']['needs_clarification']);
        $this->assertSame(BookingStatus::PERLU_KLARIFIKASI, $b->fresh()->status);
    }

    #[Test]
    public function expire_service_marks_overdue_awaiting_payment(): void
    {
        $booking = $this->makeSubmittedBooking();
        app(AdminApprovalService::class)->approve($booking, $this->admin, ['force' => true]);
        $booking->refresh();
        $this->assertSame(BookingStatus::AWAITING_PAYMENT, $booking->status);

        $payment = BookingPayment::query()->where('booking_id', $booking->id)->firstOrFail();
        $payment->update([
            'meta' => array_merge($payment->meta ?? [], [
                'expires_at' => now()->subHour()->toDateTimeString(),
            ]),
        ]);

        $result = app(BookingPaymentExpireService::class)->expireDue();

        $this->assertGreaterThanOrEqual(1, $result['expired']);
        $this->assertSame(BookingStatus::EXPIRED, $booking->fresh()->status);
        $this->assertSame('expired', $payment->fresh()->status);
    }

    /** @param  array<string, mixed>  $overrides */
    private function validSubmitPayload(array $overrides = []): array
    {
        $start = now()->addDay()->setTime(8, 0);
        $end = now()->addDay()->setTime(9, 0);

        return array_merge([
            'tarif_id' => $this->tarif->id,
            'area_id' => $this->area->id,
            'kategori_tarif' => 'non_pemerintah',
            'tujuan' => 'Latihan demo',
            'starts_at' => $start->toDateTimeString(),
            'ends_at' => $end->toDateTimeString(),
            'terms_accepted' => true,
        ], $overrides);
    }

    /** @param  array<string, mixed>  $attrs */
    private function makeSubmittedBooking(array $attrs = []): Booking
    {
        $payload = $this->validSubmitPayload();
        $userId = $attrs['user_id'] ?? $this->penyewa->id;

        $booking = app(BookingSubmitService::class)->submit(
            User::query()->findOrFail($userId),
            $payload
        );

        if (! empty($attrs['priority_rule_id'])) {
            $booking->forceFill(['priority_rule_id' => $attrs['priority_rule_id']])->save();
        }

        return $booking->fresh();
    }

    private function makeConfirmedBooking(): Booking
    {
        $booking = $this->makeSubmittedBooking();
        app(AdminApprovalService::class)->approve($booking, $this->admin, ['force' => true]);
        $booking->refresh();

        $booking->forceFill([
            'status' => BookingStatus::CONFIRMED,
            'confirmed_at' => now(),
        ])->save();

        return $booking->fresh();
    }

    private function seedMinimal(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_upt', 'guard_name' => 'web'],
            ['bg' => 'bg-info', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );
        $penyewaRole = Role::query()->firstOrCreate(
            ['name' => 'penyewa', 'guard_name' => 'web'],
            ['bg' => 'bg-success', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->venue = BookingVenue::query()->create([
            'code' => 'test_venue_'.$suffix,
            'name' => 'Venue Test '.$suffix,
            'is_active' => true,
            'sort_order' => 99,
        ]);

        $this->area = BookingArea::query()->create([
            'venue_id' => $this->venue->id,
            'code' => 'court_a',
            'name' => 'Court A',
            'is_tentative' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->tarif = BookingTarif::query()->create([
            'venue_id' => $this->venue->id,
            'area_id' => $this->area->id,
            'code' => 'tarif_'.$suffix,
            'uraian' => 'Latihan Test',
            'satuan' => BookingSatuan::PER_HOUR,
            'tarif_pemerintah' => 100_000,
            'tarif_non_pemerintah' => 150_000,
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
        BookingSetting::setValue('rekening_transfer', [
            'bank' => 'BJB',
            'rekening' => '123',
            'atas_nama' => 'RKUD',
        ]);
        BookingSetting::setValue('kontak_klarifikasi', '085777183633');

        $docType = BookingDocumentType::query()->firstOrCreate(
            ['code' => 'dokumen'],
            ['name' => 'Dokumen pendukung', 'is_required' => false, 'is_active' => true, 'sort_order' => 1]
        );

        $this->admin = User::query()->create([
            'name' => 'Admin Test '.$suffix,
            'email' => 'admin.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);

        $this->penyewa = $this->makePenyewa('penyewa.'.$suffix.'@test.local', $penyewaRole);
        $this->profile = BookingPenyewaProfile::query()->where('user_id', $this->penyewa->id)->firstOrFail();
    }

    private function makePenyewa(string $email, ?Role $role = null): User
    {
        $role ??= Role::query()->where('name', 'penyewa')->where('guard_name', 'web')->firstOrFail();

        $user = User::query()->create([
            'name' => 'Penyewa '.$email,
            'email' => $email,
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $role->id,
        ]);
        $user->assignRole($role);

        BookingPenyewaProfile::query()->create([
            'user_id' => $user->id,
            'nama' => $user->name,
            'nik' => '3201010101010001',
            'no_hp' => '081234567890',
            'alamat' => 'Bogor',
            'kategori_default' => 'non_pemerintah',
        ]);

        return $user;
    }
}
