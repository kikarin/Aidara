<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UploadBuktiBayarRequest;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingTarif;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingPaymentService;
use App\Services\Booking\BookingSubmitService;
use App\Services\Booking\PricingService;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly AvailabilityService $availability,
        private readonly BookingSubmitService $submitter,
        private readonly BookingPaymentService $payments,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['venue:id,code,name', 'area:id,code,name'])
            ->latest('id');

        if (is_string($status) && $status !== '' && in_array($status, BookingStatus::all(), true)) {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(15)->withQueryString();

        return Inertia::render('modules/e-booking/History', [
            'bookings' => $paginator->through(fn (Booking $b) => $this->summaryPayload($b)),
            'filters' => [
                'status' => is_string($status) ? $status : '',
            ],
            'status_options' => BookingStatus::all(),
        ]);
    }

    public function quote(QuoteBookingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['addon_ids'] = $this->normalizeAddonIds($validated['addon_ids'] ?? []);

        try {
            $quote = $this->pricing->quote($validated);
            $tarif = BookingTarif::query()->findOrFail((int) $validated['tarif_id']);
            $areaId = $request->filled('area_id')
                ? $request->integer('area_id')
                : $tarif->area_id;
            $availability = $this->availability->check([
                'venue_id' => $tarif->venue_id,
                'area_id' => $areaId,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
            ]);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()
            ->withInput()
            ->with('booking_quote', $quote)
            ->with('booking_availability', $availability);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['addon_ids'] = $this->normalizeAddonIds($validated['addon_ids'] ?? []);

        try {
            $booking = $this->submitter->submit($request->user(), $validated);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.bookings.show', $booking->id)
            ->with('success', 'Pengajuan berhasil dikirim. Mohon tunggu peninjauan dari pengelola.');
    }

    public function show(Request $request, int $id): Response
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'venue:id,code,name',
                'area:id,code,name',
                'items',
                'addonSelected',
                'payments' => fn ($q) => $q->latest('id'),
                'statusLogs' => fn ($q) => $q->latest('id')->limit(20),
            ])
            ->findOrFail($id);

        return Inertia::render('modules/e-booking/Detail', [
            'booking' => $this->detailPayload($booking),
        ]);
    }

    public function uploadBukti(UploadBuktiBayarRequest $request, int $id): RedirectResponse
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        try {
            $this->payments->uploadBukti(
                $booking,
                $request->user(),
                $request->file('bukti'),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.bookings.show', $booking->id)
            ->with('success', 'Bukti pembayaran berhasil dikirim. Mohon tunggu pengecekan dari pengelola.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryPayload(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'nomor' => $booking->nomor,
            'status' => $booking->status,
            'priority_flag' => $booking->priority_flag,
            'starts_at' => optional($booking->starts_at)?->format('Y-m-d H:i'),
            'ends_at' => optional($booking->ends_at)?->format('Y-m-d H:i'),
            'grand_total' => (int) $booking->grand_total,
            'venue' => $booking->venue?->only(['id', 'code', 'name']),
            'area' => $booking->area?->only(['id', 'code', 'name']),
            'created_at' => optional($booking->created_at)?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Booking $booking): array
    {
        $payment = $booking->payments->first();

        return [
            'id' => $booking->id,
            'nomor' => $booking->nomor,
            'status' => $booking->status,
            'priority_flag' => $booking->priority_flag,
            'kategori_tarif' => $booking->kategori_tarif,
            'tujuan' => $booking->tujuan,
            'keterangan' => $booking->keterangan,
            'starts_at' => optional($booking->starts_at)?->format('Y-m-d H:i:s'),
            'ends_at' => optional($booking->ends_at)?->format('Y-m-d H:i:s'),
            'grand_total' => (int) $booking->grand_total,
            'subtotal' => (int) $booking->subtotal,
            'addon_total' => (int) $booking->addon_total,
            'can_upload_bukti' => $booking->status === BookingStatus::AWAITING_PAYMENT,
            'venue' => $booking->venue?->only(['id', 'code', 'name']),
            'area' => $booking->area?->only(['id', 'code', 'name']),
            'items' => $booking->items->map(fn ($i) => [
                'uraian' => $i->uraian,
                'satuan' => $i->satuan,
                'qty' => $i->qty,
                'line_total' => (int) $i->line_total,
            ]),
            'addons' => $booking->addonSelected->map(fn ($a) => [
                'name' => $a->name,
                'qty' => $a->qty,
                'line_total' => (int) $a->line_total,
            ]),
            'payment' => $payment ? [
                'id' => $payment->id,
                'gateway' => $payment->gateway,
                'amount' => (int) $payment->amount,
                'status' => $payment->status,
                'bank' => $payment->bank,
                'rekening' => $payment->rekening,
                'atas_nama' => $payment->atas_nama,
                'bukti_url' => $payment->bukti_path
                    ? Storage::disk('public')->url($payment->bukti_path)
                    : null,
                'expires_at' => $payment->meta['expires_at'] ?? null,
                'expire_hours' => $payment->meta['expire_hours'] ?? null,
                'notes' => $payment->notes,
            ] : null,
            'status_logs' => $booking->statusLogs->map(fn ($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'created_at' => optional($log->created_at)?->format('Y-m-d H:i'),
            ]),
        ];
    }

    /**
     * @param  mixed  $raw
     * @return list<array{id: int, qty: int}>
     */
    private function normalizeAddonIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_array($item) && isset($item['id'])) {
                $out[] = [
                    'id' => (int) $item['id'],
                    'qty' => max(1, (int) ($item['qty'] ?? 1)),
                ];
            } elseif (is_numeric($item)) {
                $out[] = ['id' => (int) $item, 'qty' => 1];
            }
        }

        return $out;
    }
}
