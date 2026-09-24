<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingFacility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FacilityController extends Controller
{
    public function index(): Response
    {
        $facilities = BookingFacility::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BookingFacility $f) => [
                'id' => $f->id,
                'code' => $f->code,
                'name' => $f->name,
                'icon' => $f->icon,
                'description' => $f->description,
                'is_active' => (bool) $f->is_active,
                'sort_order' => (int) $f->sort_order,
            ]);

        return Inertia::render('modules/e-booking/admin/Facilities', [
            'facilities' => $facilities,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:booking_facilities,code'],
            'name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BookingFacility::query()->create($data + [
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Fasilitas dibuat.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $facility = BookingFacility::query()->findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('booking_facilities', 'code')->ignore($facility->id)],
            'name' => ['required', 'string', 'max:150'],
            'icon' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $facility->update($data);

        return back()->with('success', 'Fasilitas diperbarui.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $facility = BookingFacility::query()->findOrFail($id);
        $facility->update(['is_active' => ! $facility->is_active]);

        return back()->with('success', $facility->is_active ? 'Fasilitas diaktifkan.' : 'Fasilitas dinonaktifkan.');
    }
}
