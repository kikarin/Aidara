<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingPriorityRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PriorityRuleController extends Controller
{
    public function index(): Response
    {
        $rules = BookingPriorityRule::query()
            ->orderBy('priority_order')
            ->orderBy('name')
            ->get()
            ->map(fn (BookingPriorityRule $r) => [
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'priority_order' => (int) $r->priority_order,
                'description' => $r->description,
                'is_active' => (bool) $r->is_active,
            ])
            ->values();

        return Inertia::render('modules/e-booking/admin/PriorityRules', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:booking_priority_rules,code'],
            'name' => ['required', 'string', 'max:150'],
            'priority_order' => ['required', 'integer', 'min:1', 'max:65535'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        BookingPriorityRule::query()->create($data + ['is_active' => $data['is_active'] ?? true]);

        return back()->with('success', 'Prioritas konflik dibuat.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $rule = BookingPriorityRule::query()->findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('booking_priority_rules', 'code')->ignore($rule->id)],
            'name' => ['required', 'string', 'max:150'],
            'priority_order' => ['required', 'integer', 'min:1', 'max:65535'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $rule->update($data);

        return back()->with('success', 'Prioritas konflik diperbarui.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $rule = BookingPriorityRule::query()->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);

        return back()->with('success', $rule->is_active ? 'Prioritas diaktifkan.' : 'Prioritas dinonaktifkan.');
    }
}
