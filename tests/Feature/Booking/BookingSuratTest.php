<?php

namespace Tests\Feature\Booking;

use App\Mail\Booking\BookingApprovedMail;
use App\Mail\Booking\BookingSuratMail;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingSurat;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Models\Role;
use App\Models\User;
use App\Services\Booking\BookingSubmitService;
use App\Support\Booking\BookingSatuan;
use App\Support\Booking\BookingStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingSuratTest extends TestCase
{
    use DatabaseTransactions;

    private BookingVenue $venue;

    private BookingTarif $tarifVenueWide;

    private User $penyewa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('bookings') || ! Schema::hasTable('booking_surats')) {
            $this->markTestSkipped('Tabel booking/surat belum termigrasi.');
        }

        Storage::fake('local');
        $this->seedMinimal();
    }

    #[Test]
    public function admin_creates_surat_and_pdf_is_generated(): void
    {
        $booking = $this->makeBooking();

        $response = $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.store', $booking->id), [
                'jenis'         => 'undangan_meeting',
                'nomor_surat'   => '042/E-BK/IX/2026',
                'perihal'       => 'Undangan Meeting Penyewaan',
                'isi'           => 'Mohon hadir pada meeting pembahasan pengajuan.',
                'meeting_at'    => now()->addDays(3)->format('Y-m-d H:i:s'),
                'meeting_place' => 'Ruang Rapat UPT',
                'dokumen'       => ['Surat Izin Kepolisian', 'Proposal Kegiatan'],
            ]);

        $response->assertRedirect(route('e-booking.admin.bookings.show', $booking->id));
        $this->assertDatabaseHas('booking_surats', [
            'booking_id'  => $booking->id,
            'jenis'       => 'undangan_meeting',
            'nomor_surat' => '042/E-BK/IX/2026',
        ]);

        $surat = $booking->surats()->first();
        $this->assertNotNull($surat);
        $this->assertSame(['Surat Izin Kepolisian', 'Proposal Kegiatan'], $surat->dokumen);
        Storage::disk('local')->assertExists($surat->file_path);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($surat->file_path));
    }

    #[Test]
    public function undangan_meeting_requires_meeting_fields(): void
    {
        $booking = $this->makeBooking();

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.store', $booking->id), [
                'jenis'       => 'undangan_meeting',
                'nomor_surat' => '043/E-BK/IX/2026',
                'perihal'     => 'Undangan Meeting',
                'isi'         => 'Isi surat.',
            ])
            ->assertSessionHasErrors(['meeting_at', 'meeting_place']);
    }

    #[Test]
    public function penyewa_can_download_own_surat(): void
    {
        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $response = $this->actingAs($this->penyewa)
            ->get(route('e-booking.bookings.surat.download', ['id' => $booking->id, 'surat' => $surat->id]));

        $response->assertOk()->assertDownload();
    }

    #[Test]
    public function other_penyewa_cannot_download_surat(): void
    {
        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $penyewaLain = $this->makeUser('penyewa', 'lain.'.uniqid().'@test.local');

        $this->actingAs($penyewaLain)
            ->get(route('e-booking.bookings.surat.download', ['id' => $booking->id, 'surat' => $surat->id]))
            ->assertNotFound();
    }

    #[Test]
    public function send_email_attaches_pdf_and_marks_sent(): void
    {
        Mail::fake();

        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.email', $surat->id))
            ->assertRedirect();

        Mail::assertSent(BookingSuratMail::class, function (BookingSuratMail $mail) use ($surat) {
            return $mail->surat->id === $surat->id && str_starts_with($mail->pdfContent, '%PDF');
        });

        $this->assertNotNull($surat->fresh()->sent_email_at);
    }

    #[Test]
    public function whatsapp_share_sends_message_via_fonnte_and_marks_sent(): void
    {
        config([
            'fonnte.enabled'  => true,
            'fonnte.token'    => 'test-token',
            'fonnte.base_url' => 'https://api.fonnte.com',
        ]);

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success'], 200),
        ]);

        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.whatsapp', $surat->id))
            ->assertRedirect();

        Http::assertSent(function (HttpRequest $request) use ($surat) {
            $data = $this->multipartData($request);

            return $request->url() === 'https://api.fonnte.com/send'
                && $data['target'] === '6281234567890'
                && str_contains($data['message'], $surat->nomor_surat);
        });

        $this->assertNotNull($surat->fresh()->sent_whatsapp_at);
    }

    #[Test]
    public function whatsapp_share_fails_when_fonnte_not_configured(): void
    {
        config([
            'fonnte.enabled' => false,
            'fonnte.token'   => null,
        ]);

        Http::fake();

        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.whatsapp', $surat->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertNull($surat->fresh()->sent_whatsapp_at);
    }

    #[Test]
    public function shared_signed_link_is_downloadable(): void
    {
        $booking = $this->makeBooking();
        $surat   = $this->makeSurat($booking);

        $unsigned = route('e-booking.surat.shared', $surat->id);

        $this->get($unsigned)->assertForbidden();

        $signed = URL::temporarySignedRoute('e-booking.surat.shared', now()->addHour(), ['surat' => $surat->id]);

        $this->get($signed)->assertOk()->assertDownload();
    }

    #[Test]
    public function approve_sends_email_listing_required_documents(): void
    {
        Mail::fake();

        BookingSetting::setValue('payment_expire_hours', 48);
        BookingDocumentType::query()->create([
            'code'        => 'surat_izin_polisi_'.uniqid(),
            'name'        => 'Surat Izin Kepolisian',
            'is_required' => true,
            'is_active'   => true,
        ]);

        $booking = $this->makeBooking();
        // Jadikan sewa per hari (event) agar dokumen wajib ikut dilampirkan di email.
        $booking->items()->update(['satuan' => BookingSatuan::PER_DAY]);
        $booking->forceFill(['status' => BookingStatus::MENUNGGU_APPROVAL, 'submitted_at' => now()])->save();
        $booking->load('items');

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.approve', $booking->id), [
                'force'            => false,
                'priority_rule_id' => '',
                'admin_notes'      => '',
            ])
            ->assertRedirect();

        Mail::assertSent(BookingApprovedMail::class, function (BookingApprovedMail $mail) {
            return in_array('Surat Izin Kepolisian', $mail->dokumenWajib, true);
        });
    }

    #[Test]
    public function approve_with_meeting_fields_creates_and_sends_invitation(): void
    {
        Mail::fake();

        config([
            'fonnte.enabled'  => true,
            'fonnte.token'    => 'test-token',
            'fonnte.base_url' => 'https://api.fonnte.com',
        ]);

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success'], 200),
        ]);

        $booking = $this->makeBooking();
        $booking->forceFill(['status' => BookingStatus::MENUNGGU_APPROVAL, 'submitted_at' => now()])->save();

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.approve', $booking->id), [
                'force'            => false,
                'priority_rule_id' => '',
                'admin_notes'      => '',
                'meeting_at'       => now()->addDays(3)->setTime(10, 0)->format('Y-m-d H:i:s'),
                'meeting_place'    => 'Ruang Rapat UPT',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $surat = $booking->surats()->where('jenis', BookingSurat::JENIS_UNDANGAN_MEETING)->first();
        $this->assertNotNull($surat);
        $this->assertSame('Ruang Rapat UPT', $surat->meeting_place);
        $this->assertNotNull($surat->sent_email_at);
        $this->assertNotNull($surat->sent_whatsapp_at);

        Mail::assertSent(BookingSuratMail::class, fn (BookingSuratMail $mail) => $mail->surat->id === $surat->id);

        Http::assertSent(function (HttpRequest $request) use ($surat) {
            $data = $this->multipartData($request);

            return $request->url() === 'https://api.fonnte.com/send'
                && $data['target'] === '6281234567890'
                && str_contains($data['message'], $surat->nomor_surat);
        });
    }

    #[Test]
    public function approve_without_meeting_fields_does_not_create_invitation(): void
    {
        Mail::fake();
        Http::fake();

        $booking = $this->makeBooking();
        $booking->forceFill(['status' => BookingStatus::MENUNGGU_APPROVAL, 'submitted_at' => now()])->save();

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.approve', $booking->id), [
                'force'            => false,
                'priority_rule_id' => '',
                'admin_notes'      => '',
                'meeting_at'       => '',
                'meeting_place'    => '',
            ])
            ->assertRedirect();

        $this->assertSame(0, $booking->surats()->where('jenis', BookingSurat::JENIS_UNDANGAN_MEETING)->count());
        Mail::assertNotSent(BookingSuratMail::class);
        Http::assertNothingSent();
    }

    /**
     * @return array<string, mixed>
     */
    private function multipartData(HttpRequest $request): array
    {
        $data = $request->data();

        if (isset($data[0]['name'])) {
            return collect($data)->pluck('contents', 'name')->all();
        }

        return $data;
    }

    private function makeBooking()
    {
        return app(BookingSubmitService::class)->submit($this->penyewa, [
            'kategori_tarif' => 'non_pemerintah',
            'areas'          => [],
            'tarif_id'       => $this->tarifVenueWide->id,
            'starts_at'      => now()->addDays(2)->setTime(9, 0)->format('Y-m-d H:i:s'),
            'ends_at'        => now()->addDays(2)->setTime(11, 0)->format('Y-m-d H:i:s'),
            'qty'            => 1,
            'tujuan'         => 'Latihan rutin',
            'keterangan'     => null,
            'addon_ids'      => [],
            'terms_accepted' => true,
        ]);
    }

    private function makeSurat($booking)
    {
        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.bookings.surat.store', $booking->id), [
                'jenis'       => 'balasan_persetujuan',
                'nomor_surat' => 'SURAT/'.uniqid().'/2026',
                'perihal'     => 'Persetujuan Pengajuan Sewa',
                'isi'         => 'Pengajuan Anda disetujui.',
            ]);

        return $booking->surats()->firstOrFail();
    }

    private function makeUser(string $roleName, string $email): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['bg' => 'bg-success', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $user = User::query()->create([
            'name'              => ucfirst($roleName).' '.substr(uniqid(), -5),
            'email'             => $email,
            'password'          => Hash::make('password123'),
            'is_active'         => 1,
            'email_verified_at' => now(),
            'current_role_id'   => $role->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function seedMinimal(): void
    {
        $this->penyewa = $this->makeUser('penyewa', 'surat.penyewa.'.uniqid().'@test.local');
        $this->admin   = $this->makeUser('admin_upt', 'surat.admin.'.uniqid().'@test.local');

        BookingPenyewaProfile::query()->create([
            'user_id'          => $this->penyewa->id,
            'nama'             => $this->penyewa->name,
            'no_hp'            => '081234567890',
            'instansi'         => 'Klub Surat',
            'kategori_default' => 'non_pemerintah',
        ]);

        $suffix = substr(uniqid(), -6);

        $this->venue = BookingVenue::query()->create([
            'code'       => 'surat_venue_'.$suffix,
            'name'       => 'Venue Surat '.$suffix,
            'is_active'  => true,
            'sort_order' => 99,
        ]);

        $this->tarifVenueWide = BookingTarif::query()->create([
            'venue_id'             => $this->venue->id,
            'area_id'              => null,
            'code'                 => 'tarif_surat_'.$suffix,
            'uraian'               => 'Sewa seluruh venue',
            'satuan'               => BookingSatuan::PER_HOUR,
            'tarif_pemerintah'     => 200_000,
            'tarif_non_pemerintah' => 300_000,
            'category'             => 'olahraga',
            'is_active'            => true,
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

        BookingPriorityRule::query()->firstOrCreate(
            ['code' => 'umum_komersial'],
            ['name' => 'Umum', 'priority_order' => 7, 'is_active' => true]
        );

        BookingSetting::setValue('payment_expire_hours', 48);
    }
}
