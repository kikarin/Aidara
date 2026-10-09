<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingAddon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AddonController extends Controller
{
    public function index(): Response
    {
        $addons = BookingAddon::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BookingAddon $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'name' => $a->name,
                'description' => $a->description,
                'harga' => $a->harga,
                'is_active' => (bool) $a->is_active,
                'sort_order' => (int) $a->sort_order,
            ]);

        return Inertia::render('modules/e-booking/admin/Addons', [
            'addons' => $addons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:booking_addons,code'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'harga' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BookingAddon::query()->create($data + [
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Tambahan layanan dibuat.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $addon = BookingAddon::query()->findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('booking_addons', 'code')->ignore($addon->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'harga' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $addon->update($data);

        return back()->with('success', 'Tambahan layanan diperbarui.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $addon = BookingAddon::query()->findOrFail($id);
        $addon->update(['is_active' => ! $addon->is_active]);

        return back()->with('success', $addon->is_active ? 'Layanan diaktifkan.' : 'Layanan dinonaktifkan.');
    }
}
