<?php

namespace App\Services\Booking;

use App\Models\Booking\Booking;
use App\Models\Booking\BookingAddonSelected;
use App\Models\Booking\BookingItem;
use App\Models\Booking\BookingPenyewaProfile;
use App\Models\User;
use App\Support\Booking\BookingStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BookingSubmitService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly AvailabilityService $availability,
        private readonly BookingStatusService $statuses,
        private readonly RulesEngine $rules,
        private readonly ConflictResolver $conflicts,
        private readonly VenuePolicyService $policies,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function submit(User $user, array $input): Booking
    {
        $profile = BookingPenyewaProfile::query()
            ->where('user_id', $user->id)
            ->first();
        if (! $profile) {
            throw new InvalidArgumentException('Lengkapi profil penyewa sebelum submit booking.');
        }

        if (! $this->hasContactInfo($user, $profile)) {
            throw new InvalidArgumentException(
                'Lengkapi nomor HP dan email yang bisa dihubungi sebelum submit booking.'
            );
        }

        if (empty($input['terms_accepted'])) {
            throw new InvalidArgumentException('Anda harus menyetujui tata tertib / syarat sewa.');
        }

        $quoteInput = [
            'kategori_tarif' => $input['kategori_tarif'],
            'starts_at' => $input['starts_at'],
            'ends_at' => $input['ends_at'],
            'addon_ids' => $input['addon_ids'] ?? [],
        ];

        if (isset($input['areas']) && is_array($input['areas']) && $input['areas'] !== []) {
            $quoteInput['areas'] = $input['areas'];
        } else {
            $quoteInput['tarif_id'] = $input['tarif_id'] ?? null;
            $quoteInput['qty'] = $input['qty'] ?? 1;
            $quoteInput['luas_m2'] = $input['luas_m2'] ?? null;
            $quoteInput['duration_value'] = $input['duration_value'] ?? null;
        }

        $quote = $this->pricing->quote($quoteInput);

        $venueId = (int) ($quote['lines'][0]['snapshot']['venue_id'] ?? 0);
        $areaIds = array_values(array_filter(array_map(
            fn (array $line) => $line['area_id'],
            $quote['lines']
        )));
        $isWholeVenue = $areaIds === [];

        $startsAt = \Carbon\Carbon::parse($input['starts_at']);
        $endsAt = \Carbon\Carbon::parse($input['ends_at']);
        $this->policies->assertOperatingHours($venueId, $startsAt, $endsAt);

        $availability = $this->availability->assertBookable([
            'venue_id' => $venueId,
            'area_ids' => $areaIds,
            'starts_at' => $input['starts_at'],
            'ends_at' => $input['ends_at'],
        ]);

        $bufferBefore = (int) ($this->rules->get('buffer_before_days', $venueId, 1) ?? 1);
        $bufferAfter = (int) ($this->rules->get('buffer_after_days', $venueId, 1) ?? 1);

        return DB::transaction(function () use ($user, $profile, $quote, $input, $venueId, $areaIds, $isWholeVenue, $availability, $bufferBefore, $bufferAfter) {
            $booking = Booking::query()->create([
                'nomor' => $this->generateNomor(),
                'user_id' => $user->id,
                'penyewa_profile_id' => $profile->id,
                'venue_id' => $venueId,
                'priority_rule_id' => null,
                'kategori_tarif' => $input['kategori_tarif'],
                'tujuan' => $input['tujuan'] ?? null,
                'keterangan' => $input['keterangan'] ?? null,
                'status' => BookingStatus::DRAFT,
                'priority_flag' => 'normal',
                'starts_at' => $quote['starts_at'],
                'ends_at' => $quote['ends_at'],
                'buffer_before_days' => $bufferBefore,
                'buffer_after_days' => $bufferAfter,
                'luas_m2' => $isWholeVenue ? ($input['luas_m2'] ?? null) : null,
                'qty' => $isWholeVenue ? ($input['qty'] ?? 1) : null,
                'subtotal' => $quote['subtotal'],
                'addon_total' => $quote['addon_total'],
                'grand_total' => $quote['grand_total'],
                'terms_accepted_at' => now(),
                'meta' => [
                    'availability_at_submit' => $availability['status'],
                    'quote_duration' => $quote['duration'],
                ],
            ]);

            if (! $isWholeVenue) {
                $booking->areas()->sync(array_map('intval', $areaIds));
            }

            foreach ($quote['lines'] as $line) {
                BookingItem::query()->create([
                    'booking_id' => $booking->id,
                    'tarif_id' => $line['tarif_id'],
                    'uraian' => $line['uraian'],
                    'satuan' => $line['satuan'],
                    'qty' => $line['qty'],
                    'luas_m2' => $line['luas_m2'],
                    'duration_value' => $line['duration_value'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'snapshot' => $line['snapshot'],
                ]);
            }

            foreach ($quote['addons'] as $addon) {
                BookingAddonSelected::query()->create([
                    'booking_id' => $booking->id,
                    'addon_id' => $addon['addon_id'],
                    'name' => $addon['name'],
                    'qty' => $addon['qty'],
                    'unit_price' => $addon['unit_price'],
                    'line_total' => $addon['line_total'],
                    'snapshot' => $addon['snapshot'],
                ]);
            }

            $booking->priority_rule_id = $this->conflicts->suggestPriorityRuleId($booking);
            $booking->save();

            $booking = $this->statuses->transition(
                $booking,
                BookingStatus::MENUNGGU_APPROVAL,
                'Booking disubmit penyewa',
                $user->id
            );

            $booking->forceFill(['submitted_at' => now()])->save();

            return $booking->load(['items', 'addonSelected', 'venue', 'areas', 'penyewaProfile']);
        });
    }

    private function hasContactInfo(User $user, BookingPenyewaProfile $profile): bool
    {
        return filled($profile->no_hp) && filled($user->email);
    }

    private function generateNomor(): string
    {
        $prefix = 'EB-'.now()->format('Ymd');
        $last = Booking::query()
            ->withTrashed()
            ->where('nomor', 'like', $prefix.'%')
            ->orderByDesc('nomor')
            ->value('nomor');

        $seq = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return sprintf('%s-%04d', $prefix, $seq);
    }
}
