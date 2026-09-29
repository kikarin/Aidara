<?php

namespace App\Http\Controllers\Booking\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\Admin\ApproveBookingRequest;
use App\Http\Requests\Booking\Admin\RejectBookingRequest;
use App\Http\Requests\Booking\Admin\RejectPaymentRequest;
use App\Http\Requests\Booking\Admin\VerifyPaymentRequest;
use App\Mail\Booking\BookingApprovedMail;
use App\Models\Booking\Booking;
use App\Models\Booking\BookingDocumentType;
use App\Models\Booking\BookingPayment;
use App\Models\Booking\BookingPriorityRule;
use App\Models\Booking\BookingSetting;
use App\Services\Booking\AdminApprovalService;
use App\Services\Booking\BookingPaymentService;
use App\Support\Booking\BookingStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class BookingController extends Controller
{
    public function __construct(
        private readonly AdminApprovalService $approval,
        private readonly BookingPaymentService $payments,
    ) {}

    public function index(Request $request): Response
    {
        $tab = is_string($request->query('tab')) ? $request->query('tab') : 'active';
        $allowedTabs = ['active', 'review', 'payment', 'verify', 'all'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'active';
        }

        $status = $request->query('status');
        if ($tab === 'all' && is_string($status) && $status !== '' && in_array($status, BookingStatus::all(), true)) {
            // keep status filter
        } else {
            $status = '';
        }

        $base = fn () => Booking::query()->with([
            'venue:id,code,name',
            'areas:id,code,name',
            'user:id,name,email',
            'penyewaProfile:id,nama,no_hp,instansi',
            'payments' => fn ($q) => $q->latest('id'),
        ]);

        $applyTab = function ($query) use ($tab, $status) {
            return match ($tab) {
                'review' => $query->whereIn('status', [
                    BookingStatus::MENUNGGU_APPROVAL,
                    BookingStatus::PERLU_KLARIFIKASI,
                ]),
                'payment' => $query->where('status', BookingStatus::AWAITING_PAYMENT),
                'verify' => $query
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->whereHas('payments', fn ($q) => $q->where('status', 'awaiting_verification')),
                'all' => is_string($status) && $status !== ''
                    ? $query->where('status', $status)
                    : $query,
                default => $query->whereIn('status', [
                    BookingStatus::MENUNGGU_APPROVAL,
                    BookingStatus::PERLU_KLARIFIKASI,
                    BookingStatus::AWAITING_PAYMENT,
                    BookingStatus::APPROVED,
                ]),
            };
        };

        return Inertia::render('modules/e-booking/admin/Bookings', [
            'bookings' => Inertia::defer(function () use ($base, $applyTab) {
                $paginator = $applyTab($base()->latest('id'))->paginate(20)->withQueryString();

                return $paginator->through(function (Booking $b) {
                    $paymentStatus = $b->payments->first()?->status;
                    $needsVerify = $b->status === BookingStatus::AWAITING_PAYMENT
                        && $paymentStatus === 'awaiting_verification';

                    $action = match (true) {
                        in_array($b->status, [BookingStatus::MENUNGGU_APPROVAL, BookingStatus::PERLU_KLARIFIKASI], true) => [
                            'label' => 'Tinjau sekarang',
                            'tone' => 'amber',
                        ],
                        $needsVerify => [
                            'label' => 'Cek bukti bayar',
                            'tone' => 'violet',
                        ],
                        $b->status === BookingStatus::AWAITING_PAYMENT => [
                            'label' => 'Menunggu penyewa bayar',
                            'tone' => 'sky',
                        ],
                        default => [
                            'label' => 'Lihat detail',
                            'tone' => 'slate',
                        ],
                    };

                    return [
                        'id' => $b->id,
                        'nomor' => $b->nomor,
                        'status' => $b->status,
                        'priority_flag' => $b->priority_flag,
                        'starts_at' => optional($b->starts_at)?->format('Y-m-d H:i'),
                        'ends_at' => optional($b->ends_at)?->format('Y-m-d H:i'),
                        'grand_total' => (int) $b->grand_total,
                        'venue' => $b->venue?->name,
                        'area' => $b->areas->pluck('name')->implode(', ') ?: null,
                        'penyewa' => $b->penyewaProfile?->nama ?? $b->user?->name,
                        'payment_status' => $paymentStatus,
                        'needs_verify' => $needsVerify,
                        'action_label' => $action['label'],
                        'action_tone' => $action['tone'],
                    ];
                });
            }),
            'counts' => Inertia::defer(fn () => [
                'active' => Booking::query()->whereIn('status', [
                    BookingStatus::MENUNGGU_APPROVAL,
                    BookingStatus::PERLU_KLARIFIKASI,
                    BookingStatus::AWAITING_PAYMENT,
                    BookingStatus::APPROVED,
                ])->count(),
                'review' => Booking::query()->whereIn('status', [
                    BookingStatus::MENUNGGU_APPROVAL,
                    BookingStatus::PERLU_KLARIFIKASI,
                ])->count(),
                'payment' => Booking::query()->where('status', BookingStatus::AWAITING_PAYMENT)->count(),
                'verify' => Booking::query()
                    ->where('status', BookingStatus::AWAITING_PAYMENT)
                    ->whereHas('payments', fn ($q) => $q->where('status', 'awaiting_verification'))
                    ->count(),
                'all' => Booking::query()->count(),
            ]),
            'filters' => [
                'tab' => $tab,
                'status' => is_string($status) ? $status : '',
            ],
            'status_options' => [
                BookingStatus::MENUNGGU_APPROVAL,
                BookingStatus::PERLU_KLARIFIKASI,
                BookingStatus::AWAITING_PAYMENT,
                BookingStatus::APPROVED,
                BookingStatus::CONFIRMED,
                BookingStatus::COMPLETED,
                BookingStatus::REJECTED,
                BookingStatus::CANCELLED,
                BookingStatus::EXPIRED,
            ],
        ]);
    }

    public function show(int $id): Response
    {
        $booking = Booking::query()
            ->with([
                'venue',
                'areas',
                'user:id,name,email',
                'penyewaProfile',
                'items',
                'addonSelected',
                'payments' => fn ($q) => $q->latest('id'),
                'statusLogs' => fn ($q) => $q->latest('id')->limit(30),
                'priorityRule',
                'surats' => fn ($q) => $q->latest('id'),
            ])
            ->findOrFail($id);

        $payment = $booking->payments->first();
        $conflict = $this->approval->analyze($booking);

        return Inertia::render('modules/e-booking/admin/BookingShow', [
            'booking' => [
                'id' => $booking->id,
                'nomor' => $booking->nomor,
                'status' => $booking->status,
                'priority_flag' => $booking->priority_flag,
                'kategori_tarif' => $booking->kategori_tarif,
                'tujuan' => $booking->tujuan,
                'keterangan' => $booking->keterangan,
                'admin_notes' => $booking->admin_notes,
                'starts_at' => optional($booking->starts_at)?->format('Y-m-d H:i:s'),
                'ends_at' => optional($booking->ends_at)?->format('Y-m-d H:i:s'),
                'grand_total' => (int) $booking->grand_total,
                'subtotal' => (int) $booking->subtotal,
                'addon_total' => (int) $booking->addon_total,
                'venue' => $booking->venue?->only(['id', 'code', 'name']),
                'areas' => $booking->areas->map(fn ($a) => $a->only(['id', 'code', 'name']))->all(),
                'user' => $booking->user?->only(['id', 'name', 'email']),
                'penyewa' => $booking->penyewaProfile ? [
                    'nama' => $booking->penyewaProfile->nama,
                    'no_hp' => $booking->penyewaProfile->no_hp,
                    'instansi' => $booking->penyewaProfile->instansi,
                ] : null,
                'items' => $booking->items->map(fn ($i) => [
                    'uraian' => $i->uraian,
                    'satuan' => $i->satuan,
                    'qty' => $i->qty,
                    'line_total' => (int) $i->line_total,
                ]),
                'can_review' => in_array($booking->status, [
                    BookingStatus::MENUNGGU_APPROVAL,
                    BookingStatus::PERLU_KLARIFIKASI,
                ], true),
                'can_verify_payment' => $booking->status === BookingStatus::AWAITING_PAYMENT
                    && $payment
                    && $payment->status === 'awaiting_verification'
                    && $payment->bukti_path,
                'surats' => $booking->surats->map(fn ($s) => [
                    'id' => $s->id,
                    'jenis_label' => $s->jenisLabel(),
                    'nomor_surat' => $s->nomor_surat,
                    'perihal' => $s->perihal,
                    'meeting_at' => optional($s->meeting_at)?->format('Y-m-d\TH:i'),
                    'meeting_place' => $s->meeting_place,
                    'dokumen' => $s->dokumen,
                    'sent_email_at' => optional($s->sent_email_at)?->format('Y-m-d H:i'),
                    'created_at' => optional($s->created_at)?->format('Y-m-d H:i'),
                ]),
            ],
            'payment' => $payment ? [
                'id' => $payment->id,
                'status' => $payment->status,
                'amount' => (int) $payment->amount,
                'bank' => $payment->bank,
                'rekening' => $payment->rekening,
                'atas_nama' => $payment->atas_nama,
                'bukti_url' => $payment->bukti_path
                    ? Storage::disk('public')->url($payment->bukti_path)
                    : null,
                'notes' => $payment->notes,
                'expires_at' => $payment->meta['expires_at'] ?? null,
            ] : null,
            'conflict' => $conflict,
            'document_types' => BookingDocumentType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'name', 'is_required'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'is_required' => (bool) $d->is_required])
                ->all(),
            'surat_kop' => is_array(BookingSetting::getValue('surat_kop')) ? BookingSetting::getValue('surat_kop') : [],
            'priority_rules' => BookingPriorityRule::query()
                ->where('is_active', true)
                ->orderBy('priority_order')
                ->get(['id', 'name', 'priority_order', 'code']),
            'status_logs' => $booking->statusLogs->map(fn ($log) => [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'note' => $log->note,
                'created_at' => optional($log->created_at)?->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function approve(ApproveBookingRequest $request, int $id): RedirectResponse
    {
        try {
            $result = $this->approval->approve(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated()
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $booking = $result['booking'];

        $message = $booking->status === BookingStatus::PERLU_KLARIFIKASI
            ? 'Pengajuan perlu dikonfirmasi lebih lanjut karena benturan prioritas masih sama.'
            : 'Pengajuan disetujui. Menunggu pembayaran dari penyewa.';

        $dokumenWajib = in_array($booking->status, [BookingStatus::APPROVED, BookingStatus::AWAITING_PAYMENT], true)
            ? $this->dokumenWajibNames()
            : [];

        if ($booking->user?->email) {
            try {
                Mail::to($booking->user->email)->send(
                    new BookingApprovedMail($booking->fresh(['penyewaProfile', 'user']), $dokumenWajib)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $id)
            ->with('success', $message);
    }

    public function reject(RejectBookingRequest $request, int $id): RedirectResponse
    {
        try {
            $this->approval->reject(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $id)
            ->with('success', 'Booking ditolak.');
    }

    public function klarifikasi(RejectBookingRequest $request, int $id): RedirectResponse
    {
        try {
            $this->approval->markKlarifikasi(
                Booking::query()->findOrFail($id),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $id)
            ->with('success', 'Booking ditandai perlu klarifikasi.');
    }

    public function verifyPayment(VerifyPaymentRequest $request, int $paymentId): RedirectResponse
    {
        try {
            $result = $this->payments->verify(
                BookingPayment::query()->findOrFail($paymentId),
                $request->user(),
                $request->validated('notes')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $result['booking']->id)
            ->with('success', 'Pembayaran sudah dicek dan pesanan dinyatakan sah.');
    }

    /**
     * @return list<string>
     */
    private function dokumenWajibNames(): array
    {
        return BookingDocumentType::query()
            ->where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('name')
            ->all();
    }

    public function rejectPayment(RejectPaymentRequest $request, int $paymentId): RedirectResponse
    {
        try {
            $payment = $this->payments->rejectBukti(
                BookingPayment::query()->findOrFail($paymentId),
                $request->user(),
                $request->validated('reason')
            );
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('e-booking.admin.bookings.show', $payment->booking_id)
            ->with('success', 'Bukti pembayaran ditolak. Penyewa bisa mengirim ulang.');
    }
}
