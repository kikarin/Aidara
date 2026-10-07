<?php

namespace Tests\Feature\Booking;

use App\Exports\BookingExport;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingVenue;
use App\Models\Role;
use App\Models\User;
use App\Support\Booking\BookingStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Export pengajuan sewa E-Booking dengan filter periode (tahun/bulan).
 */
class BookingExportTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private User $penyewa;

    private BookingVenue $venue;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('bookings')) {
            $this->markTestSkipped('Tabel booking belum ada — jalankan migrate dulu.');
        }

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_upt', 'guard_name' => 'web'],
            ['bg' => 'bg-info', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->admin = User::query()->create([
            'name'              => 'Admin Export '.$suffix,
            'email'             => 'admin.export.'.$suffix.'@test.local',
            'password'          => Hash::make('password123'),
            'is_active'         => 1,
            'email_verified_at' => now(),
            'current_role_id'   => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);

        $this->penyewa = User::query()->create([
            'name'              => 'Penyewa Export '.$suffix,
            'email'             => 'penyewa.export.'.$suffix.'@test.local',
            'password'          => Hash::make('password123'),
            'is_active'         => 1,
            'email_verified_at' => now(),
        ]);

        $this->venue = BookingVenue::query()->create([
            'code'       => 'venue_export_'.$suffix,
            'name'       => 'Venue Export '.$suffix,
            'is_active'  => true,
            'sort_order' => 1,
        ]);

        $this->travelTo(Carbon::parse('2026-10-08 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function year_filter_limits_export_to_selected_year(): void
    {
        $booking2025 = $this->makeBooking('2025-06-15 10:00:00');
        $july2026    = $this->makeBooking('2026-07-10 10:00:00');
        $august2026  = $this->makeBooking('2026-08-20 10:00:00');

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.export', ['year' => 2026]))
            ->assertOk();

        Excel::assertDownloaded($this->exportFileName(), function (BookingExport $export) use ($booking2025, $july2026, $august2026) {
            $ids = $export->query()->pluck('id')->all();

            return ! in_array($booking2025->id, $ids, true)
                && in_array($july2026->id, $ids, true)
                && in_array($august2026->id, $ids, true);
        });
    }

    #[Test]
    public function month_filter_limits_export_to_selected_month(): void
    {
        $july2026   = $this->makeBooking('2026-07-10 10:00:00');
        $august2026 = $this->makeBooking('2026-08-20 10:00:00');

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.export', ['month' => '2026-07']))
            ->assertOk();

        Excel::assertDownloaded($this->exportFileName(), function (BookingExport $export) use ($july2026, $august2026) {
            $ids = $export->query()->pluck('id')->all();

            return in_array($july2026->id, $ids, true)
                && ! in_array($august2026->id, $ids, true);
        });
    }

    #[Test]
    public function export_without_period_includes_all_bookings(): void
    {
        $expected = [
            $this->makeBooking('2025-06-15 10:00:00')->id,
            $this->makeBooking('2026-07-10 10:00:00')->id,
        ];

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.export'))
            ->assertOk();

        Excel::assertDownloaded($this->exportFileName(), function (BookingExport $export) use ($expected) {
            return empty(array_diff($expected, $export->query()->pluck('id')->all()));
        });
    }

    #[Test]
    public function invalid_period_params_are_ignored(): void
    {
        $expected = $this->makeBooking('2026-07-10 10:00:00')->id;

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.export', ['year' => 'abc', 'month' => 'nope']))
            ->assertOk();

        Excel::assertDownloaded($this->exportFileName(), function (BookingExport $export) use ($expected) {
            return in_array($expected, $export->query()->pluck('id')->all(), true);
        });
    }

    #[Test]
    public function paid_filter_limits_export_to_paid_bookings(): void
    {
        $verified = $this->makeBooking('2026-07-10 10:00:00');
        $pending  = $this->makeBooking('2026-07-11 10:00:00');
        $awaiting = $this->makeBooking('2026-07-12 10:00:00');

        $this->makePayment($verified, 'verified');
        $this->makePayment($pending, 'pending');
        $this->makePayment($awaiting, 'awaiting_verification');

        Excel::fake();

        $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.export', ['has_paid' => '1']))
            ->assertOk();

        Excel::assertDownloaded($this->exportFileName(), function (BookingExport $export) use ($verified, $pending, $awaiting) {
            $ids = $export->query()->pluck('id')->all();

            return in_array($verified->id, $ids, true)
                && ! in_array($pending->id, $ids, true)
                && ! in_array($awaiting->id, $ids, true);
        });
    }

    #[Test]
    public function index_exposes_available_years_from_data(): void
    {
        $this->makeBooking('2025-06-15 10:00:00');
        $this->makeBooking('2026-07-10 10:00:00');

        $response = $this->actingAs($this->admin)
            ->get(route('e-booking.admin.bookings.index'));

        $response->assertOk();

        preg_match('/data-page="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches, 'data-page tidak ditemukan pada respons Inertia.');

        $page = json_decode(html_entity_decode($matches[1], ENT_QUOTES), true);

        $this->assertContains(2025, $page['props']['available_years']);
        $this->assertContains(2026, $page['props']['available_years']);
    }

    private function makeBooking(string $submittedAt): Booking
    {
        return Booking::query()->create([
            'nomor'          => 'EXP-'.strtoupper(substr(uniqid(), -8)),
            'user_id'        => $this->penyewa->id,
            'venue_id'       => $this->venue->id,
            'kategori_tarif' => 'non_pemerintah',
            'status'         => BookingStatus::APPROVED,
            'starts_at'      => $submittedAt,
            'ends_at'        => $submittedAt,
            'submitted_at'   => $submittedAt,
        ]);
    }

    private function exportFileName(): string
    {
        return 'Pengajuan_Sewa_'.now()->format('Ymd_His').'.xlsx';
    }

    private function makePayment(Booking $booking, string $status): BookingPayment
    {
        return BookingPayment::query()->create([
            'booking_id' => $booking->id,
            'status'     => $status,
            'amount'     => 100_000,
        ]);
    }
}
