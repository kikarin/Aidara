<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use App\Support\Booking\BookingSatuan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    /** @var list<string> */
    private const COVER_EXT = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

    /** @var list<string> */
    private const CATEGORIES = ['olahraga', 'non_olahraga', 'sewa_lahan', 'ruang'];

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
        ]);
    }

    public function edit(int $id): Response
    {
        $venue = BookingVenue::query()->findOrFail($id);

        return Inertia::render('modules/e-booking/admin/VenueForm', [
            'venue' => [
                'id' => $venue->id,
                'code' => $venue->code,
                'name' => $venue->name,
                'description' => $venue->description,
                'cover_url' => $venue->cover_url,
                'is_active' => (bool) $venue->is_active,
                'sort_order' => (int) $venue->sort_order,
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
                'is_active' => (bool) $t->is_active,
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
            'tarifs' => $tarifs,
            'options' => [
                'satuan' => BookingSatuan::all(),
                'categories' => self::CATEGORIES,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateVenue($request);

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

        return redirect()
            ->route('e-booking.admin.venues.show', $venue->id)
            ->with('success', 'Venue dibuat.');
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

        $venue->tarifs()->create($data + ['is_active' => $data['is_active'] ?? true]);

        return back()->with('success', 'Tarif ditambahkan.');
    }

    public function updateTarif(Request $request, int $id): RedirectResponse
    {
        $tarif = BookingTarif::query()->findOrFail($id);
        $data = $this->validateTarif($request, $tarif->venue_id, $tarif->id);

        $tarif->update($data);

        return back()->with('success', 'Tarif diperbarui.');
    }

    public function toggleTarif(int $id): RedirectResponse
    {
        $tarif = BookingTarif::query()->findOrFail($id);
        $tarif->update(['is_active' => ! $tarif->is_active]);

        return back()->with('success', $tarif->is_active ? 'Tarif diaktifkan.' : 'Tarif dinonaktifkan.');
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
        ]);
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
            'tarif_pemerintah' => ['nullable', 'integer', 'min:0'],
            'tarif_non_pemerintah' => ['nullable', 'integer', 'min:0'],
            'time_slot' => ['nullable', 'string', 'max:32'],
            'event_level' => ['nullable', 'string', 'max:64'],
            'category' => ['nullable', 'string', Rule::in(self::CATEGORIES)],
            'is_active' => ['nullable', 'boolean'],
        ]);
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
}
