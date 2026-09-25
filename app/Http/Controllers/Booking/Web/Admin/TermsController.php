<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermsController extends Controller
{
    public function index(): Response
    {
        $terms = BookingSetting::query()
            ->where('key', 'like', 'terms_%')
            ->orderBy('key')
            ->get()
            ->map(fn (BookingSetting $s) => [
                'key' => $s->key,
                'title' => is_array($s->value) ? ($s->value['title'] ?? null) : null,
                'points' => is_array($s->value) ? array_values($s->value['points'] ?? []) : [],
                'description' => $s->description,
            ])
            ->values();

        return Inertia::render('modules/e-booking/admin/Terms', [
            'terms' => $terms,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:96', 'regex:/^terms_[A-Za-z0-9_]+$/', 'unique:booking_settings,key'],
            'title' => ['nullable', 'string', 'max:200'],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'string', 'max:500'],
        ], [
            'key.regex' => 'Kunci harus diawali "terms_" dan hanya huruf/angka/garis bawah.',
            'key.unique' => 'Kunci ini sudah dipakai.',
        ]);

        BookingSetting::setValue($data['key'], [
            'title' => $data['title'] ?? null,
            'points' => $this->cleanPoints($data['points'] ?? []),
        ]);

        return back()->with('success', 'Tata tertib dibuat.');
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        if (! str_starts_with($key, 'terms_')) {
            abort(404);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:200'],
            'points' => ['nullable', 'array'],
            'points.*' => ['nullable', 'string', 'max:500'],
        ]);

        BookingSetting::setValue($key, [
            'title' => $data['title'] ?? null,
            'points' => $this->cleanPoints($data['points'] ?? []),
        ]);

        return back()->with('success', 'Tata tertib disimpan.');
    }

    /**
     * @param  array<int, mixed>  $points
     * @return list<string>
     */
    private function cleanPoints(array $points): array
    {
        return array_values(array_filter(
            array_map(fn ($p) => trim((string) $p), $points),
            fn (string $p) => $p !== ''
        ));
    }
}
