<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingFacility;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Support\Booking\BookingSatuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    /** @var list<string> */
    private const COVER_EXT = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

    /** @var list<string> */
    private const CATEGORIES = ['olahraga', 'non_olahraga', 'sewa_lahan', 'ruang'];

    /** @var list<string> */
    private const DAYS = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    /** @var list<string> Rule per-venue yang boleh diubah lewat form terstruktur. */
    private const RULE_KEYS = [
        'operating_hours',
        'operating_days',
        'booking_horizon_days',
        'buffer_before_days',
        'buffer_after_days',
        'cancel_deadline',
        'cancel_on_day_h',
        'allow_same_day_reschedule',
        'allow_same_day_court_change',
        'force_majeure_rain_before_play',
        'force_majeure_rain_after_play_minutes',
        'force_majeure_rain_after_play_decision',
        'tentative_areas',
        'tentative_priority',
        'terms_key',
        'prefer_booking_on_weekday',
        'advance_payment_required',
        'early_arrival_minutes_min',
        'early_arrival_minutes_max',
        'leave_court_after_minutes',
        'adjacent_empty_court_counts_as_rental',
    ];

    public function index(): Response
    {
        $venues = BookingVenue::query()
            ->withCount([
                'areas' => fn ($q) => $q->where('is_active', true),
                'tarifs' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BookingVenue $v) => [
                'id' => $v->id,
                'code' => $v->code,
                'name' => $v->name,
                'description' => $v->description,
                'cover_url' => $v->cover_url,
                'is_active' => (bool) $v->is_active,
                'sort_order' => (int) $v->sort_order,
                'areas_count' => (int) $v->areas_count,
                'tarifs_count' => (int) $v->tarifs_count,
            ]);

        return Inertia::render('modules/e-booking/admin/Venues', [
            'venues' => $venues,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('modules/e-booking/admin/VenueForm', [
            'venue' => null,
            'defaults' => [
                'operating_start' => '06:00',
                'operating_end' => '21:00',
            ],
            'facilities' => $this->facilityOptions(),
            'addons' => $this->addonOptions(),
            'terms' => $this->termsOptions(),
            'options' => [
                'satuan' => BookingSatuan::all(),
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    public function edit(int $id): Response
    {
        $venue = BookingVenue::query()->findOrFail($id);

        $hours = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->where('key', 'operating_hours')
            ->first()?->value;

        $days = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->where('key', 'operating_days')
            ->first()?->value;

        $termsKey = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->where('key', 'terms_key')
            ->first()?->value;

        return Inertia::render('modules/e-booking/admin/VenueForm', [
            'venue' => [
                'id' => $venue->id,
                'code' => $venue->code,
                'name' => $venue->name,
                'description' => $venue->description,
                'cover_url' => $venue->cover_url,
                'is_active' => (bool) $venue->is_active,
                'sort_order' => (int) $venue->sort_order,
                'operating_start' => is_array($hours) ? ($hours['start'] ?? '06:00') : '06:00',
                'operating_end' => is_array($hours) ? ($hours['end'] ?? '21:00') : '21:00',
                'operating_days' => is_array($days) ? array_values($days) : self::DAYS,
                'facility_ids' => $venue->facilities()->pluck('booking_facilities.id')->all(),
                'addon_ids' => $venue->addons()->pluck('booking_addons.id')->all(),
                'terms_key' => is_string($termsKey) ? $termsKey : null,
            ],
            'defaults' => [
                'operating_start' => '06:00',
                'operating_end' => '21:00',
            ],
            'facilities' => $this->facilityOptions(),
            'addons' => $this->addonOptions(),
            'terms' => $this->termsOptions(),
            'options' => [
                'satuan' => BookingSatuan::all(),
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $venue = BookingVenue::query()->findOrFail($id);

        $areas = BookingArea::query()
            ->where('venue_id', $venue->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10, ['*'], 'areas_page')
            ->withQueryString()
            ->through(fn (BookingArea $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'is_tentative' => (bool) $a->is_tentative,
                'is_active' => (bool) $a->is_active,
                'sort_order' => (int) $a->sort_order,
            ]);

        $tarifs = BookingTarif::query()
            ->where('venue_id', $venue->id)
            ->with('area:id,name')
            ->orderBy('category')
            ->orderBy('uraian')
            ->paginate(10, ['*'], 'tarifs_page')
            ->withQueryString()
            ->through(fn (BookingTarif $t) => [
                'id' => $t->id,
                'area_id' => $t->area_id,
                'area_name' => $t->area?->name,
                'code' => $t->code,
                'uraian' => $t->uraian,
                'satuan' => $t->satuan,
                'tarif_pemerintah' => $t->tarif_pemerintah,
                'tarif_non_pemerintah' => $t->tarif_non_pemerintah,
                'time_slot' => $t->time_slot,
                'event_level' => $t->event_level,
                'category' => $t->category,
                'min_hours' => $t->meta['min_hours'] ?? null,
                'max_hours' => $t->meta['max_hours'] ?? null,
                'is_active' => (bool) $t->is_active,
            ]);

        $ruleRows = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->orderBy('key')
            ->get();

        $ruleList = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->orderBy('key')
            ->paginate(10, ['*'], 'rules_page')
            ->withQueryString()
            ->through(fn (BookingRule $r) => [
                'id' => $r->id,
                'key' => $r->key,
                'value' => $r->value,
                'is_active' => (bool) $r->is_active,
                'description' => $r->description,
            ]);

        return Inertia::render('modules/e-booking/admin/VenueDetail', [
            'venue' => [
                'id' => $venue->id,
                'code' => $venue->code,
                'name' => $venue->name,
                'description' => $venue->description,
                'cover_url' => $venue->cover_url,
                'is_active' => (bool) $venue->is_active,
                'sort_order' => (int) $venue->sort_order,
            ],
            'areas' => $areas,
            'allAreas' => BookingArea::query()
                ->where('venue_id', $venue->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'tarifs' => $tarifs,
            'rules' => $ruleRows->mapWithKeys(fn (BookingRule $r) => [$r->key => $r->value])->all(),
            'ruleList' => $ruleList,
            'terms' => $this->termsOptions(),
            'options' => [
                'satuan' => BookingSatuan::all(),
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateVenue($request);
        $areas = $this->validateAreas($request);
        $tarifs = $this->validateTarifs($request);

        $areaCodes = array_column($areas, 'code');
        foreach ($tarifs as $i => $row) {
            if (! empty($row['area_code']) && ! in_array($row['area_code'], $areaCodes, true)) {
                throw ValidationException::withMessages([
                    "tarifs.$i.area_code" => 'Area tidak dikenal.',
                ]);
            }
        }

        $venue = DB::transaction(function () use ($request, $data, $areas, $tarifs) {
            $venue = BookingVenue::query()->create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            if ($request->hasFile('cover')) {
                $venue->update(['cover_path' => $this->storeCover($request->file('cover'), $venue->code)]);
            }

            $this->syncOperatingHours($venue, $data['operating_start'] ?? null, $data['operating_end'] ?? null);
            $this->syncOperatingDays($venue, $data['operating_days'] ?? null);
            $this->syncTermsKey($venue, $data['terms_key'] ?? null);
            $venue->facilities()->sync($data['facility_ids'] ?? []);
            $venue->addons()->sync($data['addon_ids'] ?? []);

            $areaIds = [];
            foreach ($areas as $i => $row) {
                $area = $venue->areas()->create([
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'is_tentative' => $row['is_tentative'] ?? false,
                    'is_active' => $row['is_active'] ?? true,
                    'sort_order' => $row['sort_order'] ?? ($i + 1),
                ]);
                $areaIds[$row['code']] = $area->id;
            }

            foreach ($tarifs as $row) {
                $meta = $this->tarifMeta($row);
                $venue->tarifs()->create([
                    'area_id' => ! empty($row['area_code']) ? ($areaIds[$row['area_code']] ?? null) : null,
                    'code' => $row['code'] ?? null,
                    'uraian' => $row['uraian'],
                    'satuan' => $row['satuan'],
                    'tarif_pemerintah' => $row['tarif_pemerintah'] ?? null,
                    'tarif_non_pemerintah' => $row['tarif_non_pemerintah'] ?? null,
                    'time_slot' => $row['time_slot'] ?? null,
                    'event_level' => $row['event_level'] ?? null,
                    'day_type' => $row['day_type'] ?? null,
                    'vehicle_class' => $row['vehicle_class'] ?? null,
                    'audience_type' => $row['audience_type'] ?? null,
                    'category' => $row['category'] ?? 'olahraga',
                    'effective_from' => $row['effective_from'] ?? null,
                    'meta' => $meta ?: null,
                    'is_active' => true,
                ]);
            }

            return $venue;
        });

        return redirect()
            ->route('e-booking.admin.venues.show', $venue->id)
            ->with('success', 'Venue, area, dan tarif dibuat.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $data = $this->validateVenue($request, $venue);

        $venue->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? $venue->is_active,
            'sort_order' => $data['sort_order'] ?? $venue->sort_order,
        ]);

        if ($request->hasFile('cover')) {
            $this->deleteCover($venue->cover_path);
            $venue->update(['cover_path' => $this->storeCover($request->file('cover'), $venue->code)]);
        }

        $this->syncOperatingHours($venue, $data['operating_start'] ?? null, $data['operating_end'] ?? null);
        $this->syncOperatingDays($venue, $data['operating_days'] ?? null);
        $this->syncTermsKey($venue, $data['terms_key'] ?? null);

        if (array_key_exists('facility_ids', $data)) {
            $venue->facilities()->sync($data['facility_ids'] ?? []);
        }

        if (array_key_exists('addon_ids', $data)) {
            $venue->addons()->sync($data['addon_ids'] ?? []);
        }

        return redirect()
            ->route('e-booking.admin.venues.show', $venue->id)
            ->with('success', 'Venue diperbarui.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $venue->update(['is_active' => ! $venue->is_active]);

        return back()->with('success', $venue->is_active ? 'Venue diaktifkan.' : 'Venue dinonaktifkan.');
    }

    public function uploadCover(Request $request, int $id): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);

        $request->validate([
            'cover' => ['required', 'file', 'mimes:'.implode(',', self::COVER_EXT), 'max:5120'],
        ]);

        $this->deleteCover($venue->cover_path);
        $venue->update(['cover_path' => $this->storeCover($request->file('cover'), $venue->code)]);

        return back()->with('success', 'Cover venue diperbarui.');
    }

    public function storeArea(Request $request, int $venueId): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($venueId);
        $data = $this->validateArea($request, $venue->id);

        $venue->areas()->create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_tentative' => $data['is_tentative'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Area ditambahkan.');
    }

    public function updateArea(Request $request, int $id): RedirectResponse
    {
        $area = BookingArea::query()->findOrFail($id);
        $data = $this->validateArea($request, $area->venue_id, $area->id);

        $area->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_tentative' => $data['is_tentative'] ?? $area->is_tentative,
            'is_active' => $data['is_active'] ?? $area->is_active,
            'sort_order' => $data['sort_order'] ?? $area->sort_order,
        ]);

        return back()->with('success', 'Area diperbarui.');
    }

    public function toggleArea(int $id): RedirectResponse
    {
        $area = BookingArea::query()->findOrFail($id);
        $area->update(['is_active' => ! $area->is_active]);

        return back()->with('success', $area->is_active ? 'Area diaktifkan.' : 'Area dinonaktifkan.');
    }

    public function storeTarif(Request $request, int $venueId): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($venueId);
        $data = $this->validateTarif($request, $venue->id);
        $meta = $this->tarifMeta($data);
        unset($data['min_hours'], $data['max_hours']);

        $venue->tarifs()->create($data + [
            'is_active' => $data['is_active'] ?? true,
            'meta' => $meta ?: null,
        ]);

        return back()->with('success', 'Tarif ditambahkan.');
    }

    public function updateTarif(Request $request, int $id): RedirectResponse
    {
        $tarif = BookingTarif::query()->findOrFail($id);
        $data = $this->validateTarif($request, $tarif->venue_id, $tarif->id);
        $meta = $this->tarifMeta($data, $tarif->meta ?? []);
        unset($data['min_hours'], $data['max_hours']);

        $tarif->update($data + ['meta' => $meta ?: null]);

        return back()->with('success', 'Tarif diperbarui.');
    }

    public function toggleTarif(int $id): RedirectResponse
    {
        $tarif = BookingTarif::query()->findOrFail($id);
        $tarif->update(['is_active' => ! $tarif->is_active]);

        return back()->with('success', $tarif->is_active ? 'Tarif diaktifkan.' : 'Tarif dinonaktifkan.');
    }

    public function updateRules(Request $request, int $id): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $data = $request->validate([
            'rules' => ['required', 'array'],
        ]);

        foreach ($data['rules'] as $key => $value) {
            if (! in_array($key, self::RULE_KEYS, true)) {
                continue;
            }

            BookingRule::query()->updateOrCreate(
                ['venue_id' => $venue->id, 'key' => $key],
                ['value' => $value, 'is_active' => true]
            );
        }

        return back()->with('success', 'Aturan venue disimpan.');
    }

    public function storeRule(Request $request, int $id): RedirectResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $data = $request->validate([
            'key' => [
                'required', 'string', 'max:96',
                Rule::unique('booking_rules', 'key')->where(fn ($q) => $q->where('venue_id', $venue->id)),
            ],
            'value' => ['nullable'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $venue->id, 'key' => $data['key']],
            ['value' => $data['value'], 'is_active' => $data['is_active'] ?? true]
        );

        return back()->with('success', 'Aturan ditambahkan.');
    }

    public function destroyRule(int $id): RedirectResponse
    {
        $rule = BookingRule::query()->findOrFail($id);
        $rule->forceDelete();

        return back()->with('success', 'Aturan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVenue(Request $request, ?BookingVenue $venue = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('booking_venues', 'code')->ignore($venue?->id),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'file', 'mimes:'.implode(',', self::COVER_EXT), 'max:5120'],
            'operating_start' => ['nullable', 'date_format:H:i'],
            'operating_end' => ['nullable', 'date_format:H:i'],
            'operating_days' => ['nullable', 'array'],
            'operating_days.*' => ['string', Rule::in(self::DAYS)],
            'facility_ids' => ['nullable', 'array'],
            'facility_ids.*' => ['integer', Rule::exists('booking_facilities', 'id')],
            'addon_ids' => ['nullable', 'array'],
            'addon_ids.*' => ['integer', Rule::exists('booking_addons', 'id')],
            'terms_key' => ['nullable', 'string', 'max:96', Rule::exists('booking_settings', 'key')->where(fn ($q) => $q->where('key', 'like', 'terms_%'))],
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateAreas(Request $request): array
    {
        $data = $request->validate([
            'areas' => ['required', 'array', 'min:1'],
            'areas.*.code' => ['required', 'string', 'max:64', 'alpha_dash', 'distinct'],
            'areas.*.name' => ['required', 'string', 'max:150'],
            'areas.*.is_tentative' => ['nullable', 'boolean'],
            'areas.*.is_active' => ['nullable', 'boolean'],
            'areas.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        return $data['areas'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function validateTarifs(Request $request): array
    {
        $data = $request->validate([
            'tarifs' => ['required', 'array', 'min:1'],
            'tarifs.*.area_code' => ['nullable', 'string', 'max:64'],
            'tarifs.*.code' => ['nullable', 'string', 'max:96', 'distinct', Rule::unique('booking_tarifs', 'code')],
            'tarifs.*.uraian' => ['required', 'string', 'max:255'],
            'tarifs.*.satuan' => ['required', 'string', Rule::in(BookingSatuan::all())],
            'tarifs.*.tarif_pemerintah' => ['nullable', 'integer', 'min:0'],
            'tarifs.*.tarif_non_pemerintah' => ['nullable', 'integer', 'min:0'],
            'tarifs.*.min_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'tarifs.*.max_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'tarifs.*.category' => ['nullable', 'string', Rule::in(self::CATEGORIES)],
            'tarifs.*.time_slot' => ['nullable', 'string', 'max:32'],
            'tarifs.*.event_level' => ['nullable', 'string', 'max:64'],
            'tarifs.*.day_type' => ['nullable', 'string', 'max:32'],
            'tarifs.*.vehicle_class' => ['nullable', 'string', 'max:64'],
            'tarifs.*.audience_type' => ['nullable', 'string', 'max:32'],
            'tarifs.*.effective_from' => ['nullable', 'date'],
        ]);

        foreach ($data['tarifs'] as $i => $row) {
            if (($row['tarif_pemerintah'] ?? null) === null && ($row['tarif_non_pemerintah'] ?? null) === null) {
                throw ValidationException::withMessages([
                    "tarifs.$i.tarif_pemerintah" => 'Isi minimal salah satu harga (instansi pemerintah atau umum).',
                ]);
            }
        }

        return $data['tarifs'];
    }

    private function syncOperatingHours(BookingVenue $venue, ?string $start, ?string $end): void
    {
        if (! $start || ! $end) {
            return;
        }

        $existing = BookingRule::query()
            ->where('venue_id', $venue->id)
            ->where('key', 'operating_hours')
            ->first()?->value;

        $timezone = is_array($existing) ? ($existing['timezone'] ?? 'Asia/Jakarta') : 'Asia/Jakarta';

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $venue->id, 'key' => 'operating_hours'],
            ['value' => ['start' => $start, 'end' => $end, 'timezone' => $timezone], 'is_active' => true]
        );
    }

    /**
     * @param  array<int, string>|null  $days
     */
    private function syncOperatingDays(BookingVenue $venue, ?array $days): void
    {
        if ($days === null || $days === []) {
            return;
        }

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $venue->id, 'key' => 'operating_days'],
            ['value' => array_values($days), 'is_active' => true]
        );
    }

    private function syncTermsKey(BookingVenue $venue, ?string $key): void
    {
        if (! $key) {
            return;
        }

        BookingRule::query()->updateOrCreate(
            ['venue_id' => $venue->id, 'key' => 'terms_key'],
            ['value' => $key, 'is_active' => true]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArea(Request $request, int $venueId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('booking_areas', 'code')
                    ->where(fn ($q) => $q->where('venue_id', $venueId))
                    ->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'is_tentative' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTarif(Request $request, int $venueId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'area_id' => ['nullable', 'integer', Rule::exists('booking_areas', 'id')->where('venue_id', $venueId)],
            'code' => ['nullable', 'string', 'max:96', Rule::unique('booking_tarifs', 'code')->ignore($ignoreId)],
            'uraian' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', Rule::in(BookingSatuan::all())],
            'tarif_pemerintah' => ['nullable', 'integer', 'min:0', 'required_without:tarif_non_pemerintah'],
            'tarif_non_pemerintah' => ['nullable', 'integer', 'min:0', 'required_without:tarif_pemerintah'],
            'time_slot' => ['nullable', 'string', 'max:32'],
            'event_level' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', Rule::in(self::CATEGORIES)],
            'min_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'max_hours' => ['nullable', 'integer', 'min:1', 'max:24'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'tarif_pemerintah.required_without' => 'Isi minimal salah satu harga (instansi pemerintah atau umum).',
            'tarif_non_pemerintah.required_without' => 'Isi minimal salah satu harga (instansi pemerintah atau umum).',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function tarifMeta(array $data, array $existing = []): array
    {
        foreach (['min_hours', 'max_hours'] as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            if ($data[$key] === null || $data[$key] === '') {
                unset($existing[$key]);
            } else {
                $existing[$key] = (int) $data[$key];
            }
        }

        return $existing;
    }

    private function storeCover(UploadedFile $file, string $code): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');

        return $file->storeAs('booking/venues', $code.'.'.$ext, 'public');
    }

    private function deleteCover(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function facilityOptions()
    {
        return BookingFacility::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'icon']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function addonOptions()
    {
        return BookingAddon::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'harga']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{value: string, label: string}>
     */
    private function termsOptions()
    {
        return BookingSetting::query()
            ->where('key', 'like', 'terms_%')
            ->orderBy('key')
            ->get()
            ->map(fn (BookingSetting $s) => [
                'value' => $s->key,
                'label' => is_array($s->value) ? ($s->value['title'] ?? $s->key) : $s->key,
            ])
            ->values();
    }
}
