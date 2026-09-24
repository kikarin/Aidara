<?php

namespace App\Http\Controllers\Booking\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingAddon;
use App\Models\Booking\BookingArea;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingRule;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingTarif;
use App\Models\Booking\BookingVenue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * CRUD master minimal untuk admin UPT.
 */
class MasterAdminController extends Controller
{
    public function venues(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:64', 'unique:booking_venues,code'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
                'cover_path' => ['nullable', 'string', 'max:255'],
                'is_active' => ['boolean'],
                'sort_order' => ['nullable', 'integer'],
            ]);
            $venue = BookingVenue::query()->create($data + ['is_active' => $data['is_active'] ?? true]);

            return response()->json(['success' => true, 'data' => $venue], 201);
        }

        return response()->json([
            'success' => true,
            'data' => BookingVenue::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function updateVenue(Request $request, int $id): JsonResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $venue->update($data);

        return response()->json(['success' => true, 'data' => $venue->fresh()]);
    }

    public function uploadCover(Request $request, int $id): JsonResponse
    {
        $venue = BookingVenue::query()->findOrFail($id);
        $request->validate([
            'cover' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
        ]);

        $file = $request->file('cover');
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $path = $file->storeAs('booking/venues', $venue->code.'.'.$ext, 'public');

        if ($venue->cover_path && $venue->cover_path !== $path) {
            Storage::disk('public')->delete($venue->cover_path);
        }

        $venue->update(['cover_path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Cover venue diperbarui',
            'data' => $venue->fresh(),
        ]);
    }

    public function areas(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
                'code' => ['required', 'string', 'max:64'],
                'name' => ['required', 'string', 'max:150'],
                'is_tentative' => ['boolean'],
                'is_active' => ['boolean'],
                'sort_order' => ['nullable', 'integer'],
            ]);
            $area = BookingArea::query()->create($data + [
                'is_tentative' => $data['is_tentative'] ?? false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return response()->json(['success' => true, 'data' => $area], 201);
        }

        $query = BookingArea::query()->with('venue:id,code,name')->orderBy('sort_order');
        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->integer('venue_id'));
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function updateArea(Request $request, int $id): JsonResponse
    {
        $area = BookingArea::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'is_tentative' => ['boolean'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $area->update($data);

        return response()->json(['success' => true, 'data' => $area]);
    }

    public function tarifs(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'venue_id' => ['required', 'integer', 'exists:booking_venues,id'],
                'area_id' => ['nullable', 'integer', 'exists:booking_areas,id'],
                'code' => ['nullable', 'string', 'max:96', 'unique:booking_tarifs,code'],
                'uraian' => ['required', 'string'],
                'satuan' => ['required', 'string', 'max:32'],
                'tarif_pemerintah' => ['nullable', 'integer', 'min:0'],
                'tarif_non_pemerintah' => ['nullable', 'integer', 'min:0'],
                'time_slot' => ['nullable', 'string', 'max:32'],
                'audience_type' => ['nullable', 'string', 'max:32'],
                'day_type' => ['nullable', 'string', 'max:32'],
                'vehicle_class' => ['nullable', 'string', 'max:64'],
                'event_level' => ['nullable', 'string', 'max:64'],
                'category' => ['nullable', 'string', 'max:64'],
                'is_active' => ['boolean'],
            ]);
            $tarif = BookingTarif::query()->create($data + ['is_active' => $data['is_active'] ?? true]);

            return response()->json(['success' => true, 'data' => $tarif], 201);
        }

        $query = BookingTarif::query()->with(['venue:id,code,name', 'area:id,code,name'])->latest('id');
        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->integer('venue_id'));
        }

        return response()->json(['success' => true, 'data' => $query->paginate(50)]);
    }

    public function updateTarif(Request $request, int $id): JsonResponse
    {
        $tarif = BookingTarif::query()->findOrFail($id);
        $data = $request->validate([
            'uraian' => ['sometimes', 'string'],
            'tarif_pemerintah' => ['nullable', 'integer', 'min:0'],
            'tarif_non_pemerintah' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'time_slot' => ['nullable', 'string', 'max:32'],
            'category' => ['nullable', 'string', 'max:64'],
        ]);
        $tarif->update($data);

        return response()->json(['success' => true, 'data' => $tarif]);
    }

    public function addons(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'code' => ['required', 'string', 'max:64', 'unique:booking_addons,code'],
                'name' => ['required', 'string', 'max:150'],
                'description' => ['nullable', 'string'],
                'harga' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['boolean'],
                'sort_order' => ['nullable', 'integer'],
            ]);
            $addon = BookingAddon::query()->create($data + ['is_active' => $data['is_active'] ?? true]);

            return response()->json(['success' => true, 'data' => $addon], 201);
        }

        return response()->json([
            'success' => true,
            'data' => BookingAddon::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function updateAddon(Request $request, int $id): JsonResponse
    {
        $addon = BookingAddon::query()->findOrFail($id);
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'harga' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $addon->update($data);

        return response()->json(['success' => true, 'data' => $addon]);
    }

    public function rules(Request $request): JsonResponse
    {
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'venue_id' => ['nullable', 'integer', 'exists:booking_venues,id'],
                'key' => ['required', 'string', 'max:96'],
                'value' => ['nullable'],
                'description' => ['nullable', 'string'],
                'is_active' => ['boolean'],
            ]);
            $rule = BookingRule::query()->create($data + ['is_active' => $data['is_active'] ?? true]);

            return response()->json(['success' => true, 'data' => $rule], 201);
        }

        $query = BookingRule::query()->with('venue:id,code,name');
        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->integer('venue_id'));
        }
        if ($request->boolean('global_only')) {
            $query->whereNull('venue_id');
        }

        return response()->json(['success' => true, 'data' => $query->orderBy('key')->get()]);
    }

    public function updateRule(Request $request, int $id): JsonResponse
    {
        $rule = BookingRule::query()->findOrFail($id);
        $data = $request->validate([
            'value' => ['nullable'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);
        $rule->update($data);

        return response()->json(['success' => true, 'data' => $rule]);
    }

    public function settings(Request $request): JsonResponse
    {
        if ($request->isMethod('put') || $request->isMethod('post')) {
            $data = $request->validate([
                'key' => ['required', 'string', 'max:96'],
                'value' => ['nullable'],
                'description' => ['nullable', 'string'],
            ]);
            BookingSetting::setValue($data['key'], $data['value'], $data['description'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Setting disimpan',
                'data' => BookingSetting::query()->where('key', $data['key'])->first(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => BookingSetting::query()->orderBy('key')->get(),
        ]);
    }

    public function priorityRules(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BookingPriorityRule::query()->where('is_active', true)->orderBy('priority_order')->get(),
        ]);
    }
}
