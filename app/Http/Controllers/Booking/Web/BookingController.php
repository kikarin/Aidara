<?php

namespace App\Http\Controllers\Booking\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\QuoteBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UploadBuktiBayarRequest;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingSetting;
use App\Models\Booking\BookingSurat;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingPaymentService;
use App\Services\Booking\BookingPaymentWindow;
use App\Services\Booking\BookingSubmitService;
use App\Services\Booking\PricingService;
use App\Services\Booking\SuratService;
use App\Support\Booking\BookingJenisSewa;
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
        private readonly BookingPaymentWindow $paymentWindow,
    ) {}

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $query = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with(['venue:id,code,name', 'areas:id,code,name'])
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
            $areaIds = array_values(array_filter(array_map(
                fn (array $line) => $line['area_id'],
                $quote['lines']
            )));
            $availability = $this->availability->check([
                'venue_id' => (int) $quote['lines'][0]['snapshot']['venue_id'],
                'area_ids' => $areaIds,
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'is_per_hari' => BookingJenisSewa::satuanPerHari(array_map(
                    fn (array $line) => $line['satuan'],
                    $quote['lines']
                )),
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

        if (BookingJenisSewa::isReguler($booking)) {
            return redirect()
                ->route('e-booking.bookings.show', $booking->id)
                ->with(
                    'success',
                    'Booking per jam berhasil dibuat. Silakan lanjutkan pembayaran sesuai instruksi.'
                );
        }

        $slaHari = max(1, (int) (BookingSetting::getValue('pengajuan_sla_hari_kerja', 7) ?? 7));

        return redirect()
            ->route('e-booking.bookings.show', $booking->id)
            ->with(
                'success',
                "Pengajuan berhasil dikirim. Proses peninjauan maksimal {$slaHari} hari kerja — balasan berupa surat akan dikirim setelah selesai."
            );
    }

    public function show(Request $request, int $id): Response
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'venue:id,code,name',
                'areas:id,code,name',
                'items',
                'addonSelected',
                'payments' => fn ($q) => $q->latest('id'),
                'statusLogs' => fn ($q) => $q->latest('id')->limit(20),
                'surats' => fn ($q) => $q->latest('id'),
            ])
            ->findOrFail($id);

        return Inertia::render('modules/e-booking/Detail', [
            'booking' => $this->detailPayload($booking),
            'slaHariKerja' => max(1, (int) (BookingSetting::getValue('pengajuan_sla_hari_kerja', 7) ?? 7)),
        ]);
    }

    public function downloadSurat(Request $request, int $id, int $suratId)
    {
        $booking = Booking::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $surat = BookingSurat::query()
            ->where('booking_id', $booking->id)
            ->findOrFail($suratId);

        return app(SuratService::class)->downloadResponse($surat);
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
            'areas' => $booking->areas->map(fn ($a) => $a->only(['id', 'code', 'name']))->all(),
            'created_at' => optional($booking->created_at)?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPayload(Booking $booking): array
    {
        $payment = $booking->payments->first();
        $window = $payment ? $this->paymentWindow->payload($booking, $payment) : null;

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
            'can_upload_bukti' => $booking->status === BookingStatus::AWAITING_PAYMENT
                && $payment
                && in_array($payment->status, ['pending', 'rejected'], true)
                && $window['is_open'],
            'jenis_sewa' => BookingJenisSewa::of($booking),
            'surat_permohonan_url' => $booking->surat_permohonan_path
                ? Storage::disk('public')->url($booking->surat_permohonan_path)
                : null,
            'surat_permohonan_name' => $booking->surat_permohonan_name,
            'surat_permohonan_submitted_at' => optional($booking->submitted_surat_permohonan_at)?->format('Y-m-d H:i'),
            'venue' => $booking->venue?->only(['id', 'code', 'name']),
            'areas' => $booking->areas->map(fn ($a) => $a->only(['id', 'code', 'name']))->all(),
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
            'dokumen_wajib' => $this->dokumenWajibNames($booking),
            'surats' => $booking->surats->map(fn (BookingSurat $s) => [
                'id' => $s->id,
                'jenis_label' => $s->jenisLabel(),
                'nomor_surat' => $s->nomor_surat,
                'perihal' => $s->perihal,
                'meeting_at' => optional($s->meeting_at)?->format('Y-m-d H:i'),
                'meeting_place' => $s->meeting_place,
                'dokumen' => $s->dokumen,
                'sent_email_at' => optional($s->sent_email_at)?->format('Y-m-d H:i'),
                'sent_whatsapp_at' => optional($s->sent_whatsapp_at)?->format('Y-m-d H:i'),
                'created_at' => optional($s->created_at)?->format('Y-m-d H:i'),
                'download_url' => route('e-booking.bookings.surat.download', ['id' => $booking->id, 'surat' => $s->id]),
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
                'expires_at' => $window['expires_at'],
                'expire_hours' => $payment->meta['expire_hours'] ?? null,
                'opens_at' => $window['opens_at'],
                'is_open' => $window['is_open'],
                'open_mode' => $window['open_mode'],
                'message' => $window['message'],
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
     * Daftar dokumen wajib (dinamis dari master jenis dokumen) yang harus
     * disiapkan penyewa — tampil setelah pengajuan disetujui / ada surat.
     *
     * @return list<string>
     */
    private function dokumenWajibNames(Booking $booking): array
    {
        if (BookingJenisSewa::isReguler($booking) && $booking->surats->isEmpty()) {
            return [];
        }

        $unlocked = in_array($booking->status, [
            BookingStatus::APPROVED,
            BookingStatus::AWAITING_PAYMENT,
            BookingStatus::PAID,
            BookingStatus::CONFIRMED,
            BookingStatus::RESCHEDULE_PENDING,
        ], true);

        if (! $unlocked && $booking->surats->isEmpty()) {
            return [];
        }

        return BookingDocumentType::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    /**
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
