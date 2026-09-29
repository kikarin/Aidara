<?php

namespace Tests\Feature\Booking;

use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingFacility;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingSetting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Master data E-Booking via web admin (addon, dokumen, tata tertib, prioritas).
 */
class BookingMasterAdminTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('booking_addons')) {
            $this->markTestSkipped('Tabel booking belum ada — jalankan migrate dulu.');
        }

        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin_upt', 'guard_name' => 'web'],
            ['bg' => 'bg-info', 'init_page_login' => 'dashboard', 'is_allow_login' => 1, 'is_vertical_menu' => true]
        );

        $suffix = substr(uniqid(), -6);

        $this->admin = User::query()->create([
            'name' => 'Admin Master '.$suffix,
            'email' => 'admin.master.'.$suffix.'@test.local',
            'password' => Hash::make('password123'),
            'is_active' => 1,
            'email_verified_at' => now(),
            'current_role_id' => $adminRole->id,
        ]);
        $this->admin->assignRole($adminRole);
    }

    #[Test]
    public function admin_can_manage_addons(): void
    {
        $code = 'addon_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.addons.store'), [
                'code' => $code,
                'name' => 'Layanan Baru',
                'harga' => 250_000,
                'is_active' => true,
            ])
            ->assertRedirect();

        $addon = BookingAddon::query()->where('code', $code)->first();
        $this->assertNotNull($addon);
        $this->assertSame(250_000, (int) $addon->harga);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.addons.toggle', $addon->id))
            ->assertRedirect();

        $this->assertFalse((bool) $addon->fresh()->is_active);
    }

    #[Test]
    public function admin_can_manage_document_types(): void
    {
        $code = 'doc_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.document-types.store'), [
                'code' => $code,
                'name' => 'Surat Permohonan',
                'is_required' => true,
                'is_active' => true,
            ])
            ->assertRedirect();

        $doc = BookingDocumentType::query()->where('code', $code)->first();
        $this->assertNotNull($doc);
        $this->assertTrue((bool) $doc->is_required);
    }

    #[Test]
    public function admin_can_manage_terms(): void
    {
        $key = 'terms_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.terms.store'), [
                'key' => $key,
                'title' => 'Tata Tertib Uji',
                'points' => ['Poin satu', 'Poin dua', ''],
            ])
            ->assertRedirect();

        $stored = BookingSetting::getValue($key);
        $this->assertSame('Tata Tertib Uji', $stored['title']);
        $this->assertSame(['Poin satu', 'Poin dua'], $stored['points']);

        $this->actingAs($this->admin)
            ->put(route('e-booking.admin.terms.update', $key), [
                'title' => 'Tata Tertib Revisi',
                'points' => ['Poin baru'],
            ])
            ->assertRedirect();

        $this->assertSame('Tata Tertib Revisi', BookingSetting::getValue($key)['title']);
    }

    #[Test]
    public function admin_can_manage_facilities(): void
    {
        $code = 'fac_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.facilities.store'), [
                'code' => $code,
                'name' => 'Fasilitas Uji',
                'icon' => 'wifi',
                'is_active' => true,
            ])
            ->assertRedirect();

        $facility = BookingFacility::query()->where('code', $code)->first();
        $this->assertNotNull($facility);
        $this->assertSame('wifi', $facility->icon);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.facilities.toggle', $facility->id))
            ->assertRedirect();

        $this->assertFalse((bool) $facility->fresh()->is_active);
    }

    #[Test]
    public function admin_can_manage_priority_rules(): void
    {
        $code = 'prio_'.substr(uniqid(), -6);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.priority-rules.store'), [
                'code' => $code,
                'name' => 'Prioritas Uji',
                'priority_order' => 9,
                'is_active' => true,
            ])
            ->assertRedirect();

        $rule = BookingPriorityRule::query()->where('code', $code)->first();
        $this->assertNotNull($rule);
        $this->assertSame(9, (int) $rule->priority_order);

        $this->actingAs($this->admin)
            ->post(route('e-booking.admin.priority-rules.toggle', $rule->id))
            ->assertRedirect();

        $this->assertFalse((bool) $rule->fresh()->is_active);
    }
}
