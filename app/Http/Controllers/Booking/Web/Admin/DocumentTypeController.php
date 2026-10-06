<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking\BookingDocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeController extends Controller
{
    public function index(): Response
    {
        $documentTypes = BookingDocumentType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BookingDocumentType $d) => [
                'id' => $d->id,
                'code' => $d->code,
                'name' => $d->name,
                'is_required' => (bool) $d->is_required,
                'is_active' => (bool) $d->is_active,
                'sort_order' => (int) $d->sort_order,
            ]);

        return Inertia::render('modules/e-booking/admin/DocumentTypes', [
            'documentTypes' => $documentTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:booking_document_types,code'],
            'name' => ['required', 'string', 'max:150'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        BookingDocumentType::query()->create($data + [
            'is_required' => $data['is_required'] ?? false,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('success', 'Jenis dokumen dibuat.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $documentType = BookingDocumentType::query()->findOrFail($id);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('booking_document_types', 'code')->ignore($documentType->id)],
            'name' => ['required', 'string', 'max:150'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $documentType->update($data);

        return back()->with('success', 'Jenis dokumen diperbarui.');
    }

    public function toggle(int $id): RedirectResponse
    {
        $documentType = BookingDocumentType::query()->findOrFail($id);
        $documentType->update(['is_active' => ! $documentType->is_active]);

        return back()->with('success', $documentType->is_active ? 'Dokumen diaktifkan.' : 'Dokumen dinonaktifkan.');
    }
}
