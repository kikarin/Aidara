<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbPitchLines from '@/components/sibola/SbPitchLines.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faArrowUpRightFromSquare,
    faBuildingColumns,
    faCalendarDays,
    faCheck,
    faCircleNotch,
    faClock,
    faCloudArrowUp,
    faCopy,
    faDownload,
    faFileLines,
    faHeadset,
    faLocationDot,
    faMagnifyingGlass,
    faTriangleExclamation,
    faUpload,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

library.add(
    faArrowLeft,
    faArrowUpRightFromSquare,
    faBuildingColumns,
    faCalendarDays,
    faCheck,
    faCircleNotch,
    faClock,
    faCloudArrowUp,
    faCopy,
    faDownload,
    faFileLines,
    faHeadset,
    faLocationDot,
    faMagnifyingGlass,
    faTriangleExclamation,
    faUpload,
    faXmark,
);

type PaymentInfo = {
    id: number;
    gateway: string;
    amount: number;
    status: string;
    bank: string | null;
    rekening: string | null;
    atas_nama: string | null;
    bukti_url: string | null;
    expires_at: string | null;
    expire_hours: number | null;
    opens_at: string | null;
    is_open: boolean;
    open_mode: 'after_approval' | 'before_event';
    message: string;
    notes: string | null;
};

const props = defineProps<{
    booking: {
        id: number;
        nomor: string;
        status: string;
        priority_flag: string | null;
        kategori_tarif: string;
        tujuan: string | null;
        keterangan: string | null;
        starts_at: string | null;
        ends_at: string | null;
        grand_total: number;
        subtotal: number;
        addon_total: number;
        can_upload_bukti: boolean;
        jenis_sewa?: 'reguler' | 'event';
        venue: { id: number; code: string; name: string } | null;
        areas: Array<{ id: number; code: string; name: string }>;
        items: Array<{ uraian: string; satuan: string; qty: number; line_total: number }>;
        addons: Array<{ name: string; qty: number; line_total: number }>;
        dokumen_wajib: string[];
        surat_permohonan_url: string | null;
        surat_permohonan_name: string | null;
        surat_permohonan_submitted_at: string | null;
        surats: Array<{
            id: number;
            jenis_label: string;
            nomor_surat: string | null;
            perihal: string | null;
            meeting_at: string | null;
            meeting_place: string | null;
            dokumen: string[] | null;
            sent_email_at: string | null;
            sent_whatsapp_at: string | null;
            created_at: string | null;
            download_url: string;
        }>;
        payment: PaymentInfo | null;
        status_logs: Array<{
            from_status: string | null;
            to_status: string | null;
            note: string | null;
            created_at: string | null;
        }>;
    };
    slaHariKerja?: number;
}>();

const page = usePage();
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const flashError = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

const formatRp = (n: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'Menunggu ditinjau',
        menunggu_meeting: 'Menunggu keputusan',
        awaiting_payment: 'Menunggu pembayaran',
        approved: 'Sudah disetujui',
        paid: 'Pembayaran masuk',
        confirmed: 'Sudah dikonfirmasi',
        perlu_klarifikasi: 'Perlu konfirmasi',
        rejected: 'Tidak disetujui',
        cancelled: 'Dibatalkan',
        forfeited: 'Hangus',
        expired: 'Lewat batas waktu',
        completed: 'Selesai',
    };

    return map[status] ?? status;
};

const isReguler = computed(() => props.booking.jenis_sewa === 'reguler');

const form = useForm({
    bukti: null as File | null,
    notes: '',
});

const fileInput = ref<HTMLInputElement | null>(null);
const dragging = ref(false);

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    form.bukti = input.files?.[0] ?? null;
};

const onDrop = (e: DragEvent) => {
    dragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) {
        form.bukti = file;
    }
};

const clearFile = () => {
    form.bukti = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const fileSize = computed(() => {
    const size = form.bukti?.size ?? 0;

    return size >= 1_048_576 ? `${(size / 1_048_576).toFixed(1)} MB` : `${Math.max(1, Math.round(size / 1024))} KB`;
});

const submitBukti = () => {
    form.post(route('e-booking.bookings.bukti', props.booking.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('bukti', 'notes');
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const countdown = ref('');
const countdownParts = ref<Array<{ value: string; unit: string }>>([]);
let timer: ReturnType<typeof setInterval> | null = null;

const paymentStatusLabel = (status: string) => {
    const map: Record<string, string> = {
        pending: 'Menunggu transfer',
        awaiting_verification: 'Bukti sedang diperiksa',
        verified: 'Lunas',
        rejected: 'Bukti ditolak',
    };

    return map[status] ?? statusLabel(status);
};

const paymentNotOpen = computed(() => props.booking.status === 'awaiting_payment' && props.booking.payment?.is_open === false);

const awaitingTransfer = computed(
    () => props.booking.status === 'awaiting_payment' && props.booking.payment?.status === 'pending' && !paymentNotOpen.value,
);

const awaitingVerification = computed(() => props.booking.status === 'awaiting_payment' && props.booking.payment?.status === 'awaiting_verification');

const paymentRejected = computed(() => props.booking.status === 'awaiting_payment' && props.booking.payment?.status === 'rejected');

const updateCountdown = () => {
    const raw = props.booking.payment?.expires_at;
    if (!raw || !awaitingTransfer.value) {
        countdown.value = '';
        countdownParts.value = [];

        return;
    }

    const end = new Date(raw.replace(' ', 'T')).getTime();
    const diff = end - Date.now();
    if (Number.isNaN(end) || diff <= 0) {
        countdown.value = 'Tenggat habis';
        countdownParts.value = [];

        return;
    }

    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    const s = Math.floor((diff % 60_000) / 1000);
    countdown.value = `${h}j ${m}m ${s}d`;
    countdownParts.value = [
        { value: String(h).padStart(2, '0'), unit: 'jam' },
        { value: String(m).padStart(2, '0'), unit: 'menit' },
        { value: String(s).padStart(2, '0'), unit: 'detik' },
    ];
};

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
    if (copyTimer) {
        clearTimeout(copyTimer);
    }
});

const copied = ref<string | null>(null);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

const copyText = async (key: string, value: string | null | undefined) => {
    if (!value) return;

    try {
        await navigator.clipboard.writeText(value);
        copied.value = key;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => (copied.value = null), 2000);
    } catch {
        copied.value = null;
    }
};

const title = computed(() => {
    if (awaitingVerification.value) {
        return 'Bukti sedang diperiksa';
    }
    if (paymentRejected.value) {
        return 'Bukti ditolak';
    }
    if (paymentNotOpen.value) {
        return 'Pembayaran belum dibuka';
    }
    if (props.booking.status === 'awaiting_payment') {
        return 'Menunggu pembayaran';
    }
    if (props.booking.status === 'menunggu_approval') {
        return 'Menunggu ditinjau';
    }
    if (props.booking.status === 'menunggu_meeting') {
        return 'Menunggu keputusan pengelola';
    }

    return `Pesanan ${props.booking.nomor}`;
});

const nextStepText = computed(() => {
    if (awaitingVerification.value) {
        return 'Bukti pembayaran Anda sudah masuk dan sedang diperiksa pengelola. Anda tidak perlu transfer lagi.';
    }
    if (paymentRejected.value) {
        return 'Bukti pembayaran Anda ditolak pengelola. Silakan cek alasan penolakan, lalu unggah ulang bukti yang benar.';
    }
    if (props.booking.status === 'awaiting_payment') {
        return props.booking.payment?.message || 'Silakan transfer sesuai petunjuk di bawah, lalu kirim bukti pembayaran.';
    }
    if (props.booking.status === 'menunggu_approval' && isReguler.value) {
        return 'Pengajuan sewa per jam Anda sudah masuk dan langsung terbooking. Petunjuk pembayaran muncul otomatis di halaman ini.';
    }
    if (props.booking.status === 'menunggu_approval') {
        return `Pengajuan Anda sudah masuk. Proses peninjauan maksimal ${props.slaHariKerja ?? 7} hari kerja — balasan berupa surat akan dikirim setelah selesai.`;
    }
    if (props.booking.status === 'perlu_klarifikasi') {
        return 'Pengelola perlu konfirmasi tambahan. Mohon cek catatan terbaru.';
    }
    if (props.booking.status === 'menunggu_meeting') {
        return 'Pengelola sedang menindaklanjuti pengajuan Anda. Jika ada undangan meeting, silakan hadir dan bawa dokumen yang diminta.';
    }

    return 'Lihat rincian pesanan dan pantau perkembangannya di halaman ini.';
});

const progressSteps = isReguler.value
    ? ['Pengajuan dikirim', 'Langsung terbooking', 'Pembayaran', 'Terkonfirmasi']
    : ['Pengajuan dikirim', 'Ditinjau pengelola', 'Pembayaran', 'Terkonfirmasi'];

const haltedStatuses = ['rejected', 'cancelled', 'forfeited', 'expired'];

const isHalted = computed(() => haltedStatuses.includes(props.booking.status));

const progressIndex = computed(() => {
    const s = props.booking.status;
    if (['confirmed', 'completed'].includes(s)) {
        return progressSteps.length;
    }
    if (s === 'paid') {
        return 3;
    }
    if (['approved', 'awaiting_payment', 'awaiting_verification', 'forfeited', 'expired'].includes(s)) {
        return 2;
    }
    if (['menunggu_approval', 'perlu_klarifikasi', 'menunggu_meeting', 'rejected', 'cancelled'].includes(s)) {
        return 1;
    }

    return 0;
});

const stepState = (i: number) => {
    if (i < progressIndex.value) {
        return 'done';
    }
    if (i === progressIndex.value) {
        return isHalted.value ? 'halted' : 'current';
    }

    return 'upcoming';
};

const statusNote = computed(() => {
    if (!['rejected', 'perlu_klarifikasi', 'menunggu_meeting'].includes(props.booking.status)) {
        return null;
    }

    return [...props.booking.status_logs].reverse().find((log) => log.to_status === props.booking.status && log.note)?.note ?? null;
});

const heading = computed(() => props.booking.venue?.name ?? title.value);

const parseDate = (raw: string | null) => {
    if (!raw) return null;
    const date = new Date(raw.replace(' ', 'T'));

    return Number.isNaN(date.getTime()) ? null : date;
};

const dateFormatter = new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
const timeFormatter = new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' });

const formatDateTime = (raw: string) => {
    const date = parseDate(raw);

    return date ? `${dateFormatter.format(date)}, ${timeFormatter.format(date)}` : raw;
};

const schedule = computed(() => {
    const start = parseDate(props.booking.starts_at);
    const end = parseDate(props.booking.ends_at);
    if (!start || !end) {
        return null;
    }

    const sameDay = start.toDateString() === end.toDateString();

    return {
        startDate: dateFormatter.format(start),
        startTime: timeFormatter.format(start),
        endDate: dateFormatter.format(end),
        endTime: timeFormatter.format(end),
        sameDay,
    };
});

const kategoriLabel = computed(() => (props.booking.kategori_tarif === 'pemerintah' ? 'Instansi pemerintah' : 'Umum / non pemerintah'));

const progressPercent = computed(() => {
    const last = progressSteps.length - 1;

    return `${(Math.min(progressIndex.value, last) / last) * 100}%`;
});
</script>

<template>
    <SeoHead :title="`Booking ${booking.nomor}`" />

    <EBookingLayout active="history">
        <div class="space-y-8">
            <div v-if="flashSuccess || flashError" class="space-y-3">
                <p v-if="flashSuccess" class="sb-callout sb-tone-success" role="status">
                    <FontAwesomeIcon :icon="['fas', 'check']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flashSuccess }}</span>
                </p>
                <p v-if="flashError" class="sb-callout sb-tone-danger" role="alert">
                    <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <span>{{ flashError }}</span>
                </p>
            </div>

            <Link
                :href="route('e-booking.bookings.index')"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-lg text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
            >
                <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
                Pesanan saya
            </Link>

            <header class="sb-card relative isolate overflow-hidden">
                <div class="sb-mow absolute inset-0 -z-20" aria-hidden="true"></div>
                <SbPitchLines variant="half" class="absolute inset-y-0 right-0 -z-10 h-full w-3/4 text-(--wp-accent) opacity-[0.12]" />

                <div class="grid lg:grid-cols-[minmax(0,1fr)_auto_18rem]">
                    <div class="min-w-0 p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Kode pesanan</span>
                            <button
                                type="button"
                                class="group inline-flex items-center gap-2 rounded-lg bg-(--wp-accent-soft) px-2.5 py-1 font-mono text-sm font-semibold text-(--wp-accent-strong) tabular-nums transition hover:brightness-95 focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                :aria-label="`Salin kode pesanan ${booking.nomor}`"
                                @click="copyText('nomor', booking.nomor)"
                            >
                                {{ booking.nomor }}
                                <FontAwesomeIcon
                                    :icon="['fas', copied === 'nomor' ? 'check' : 'copy']"
                                    class="size-3 opacity-70"
                                    aria-hidden="true"
                                />
                            </button>
                            <span class="sr-only" aria-live="polite">{{ copied === 'nomor' ? 'Kode pesanan disalin' : '' }}</span>
                            <span v-if="booking.jenis_sewa" class="sb-badge" :class="isReguler ? 'sb-tone-info' : 'sb-tone-meeting'">
                                {{ isReguler ? 'Sewa per jam' : 'Sewa per hari' }}
                            </span>
                        </div>

                        <h1 class="mt-4 text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ heading }}</h1>
                        <p v-if="booking.areas?.length" class="text-muted-foreground mt-2 flex items-start gap-2 text-sm leading-relaxed">
                            <FontAwesomeIcon :icon="['fas', 'location-dot']" class="mt-1 size-3.5 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                            {{ booking.areas.map((a) => a.name).join(', ') }}
                        </p>

                        <div class="mt-8 grid gap-6 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
                            <div v-if="schedule" class="flex items-center gap-4">
                                <div>
                                    <p class="text-muted-foreground text-xs">{{ schedule.startDate }}</p>
                                    <p class="text-3xl font-bold tracking-tight tabular-nums">{{ schedule.startTime }}</p>
                                </div>
                                <span class="flex w-12 items-center gap-1 text-(--wp-accent)" aria-hidden="true">
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                    <span class="h-px flex-1 border-t border-dashed border-current"></span>
                                    <span class="size-1.5 rounded-full bg-current"></span>
                                </span>
                                <div>
                                    <p class="text-muted-foreground text-xs">{{ schedule.sameDay ? 'Hari yang sama' : schedule.endDate }}</p>
                                    <p class="text-3xl font-bold tracking-tight tabular-nums">{{ schedule.endTime }}</p>
                                </div>
                            </div>
                            <p v-else class="flex items-center gap-2 text-sm tabular-nums">
                                <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                                {{ booking.starts_at }} — {{ booking.ends_at }}
                            </p>

                            <dl class="grid grid-cols-2 gap-4 text-sm sm:border-l sm:border-(--wp-hairline) sm:pl-6">
                                <div class="min-w-0">
                                    <dt class="text-muted-foreground text-xs">Keperluan</dt>
                                    <dd class="mt-0.5 truncate font-medium" :title="booking.tujuan ?? undefined">{{ booking.tujuan || '-' }}</dd>
                                </div>
                                <div class="min-w-0">
                                    <dt class="text-muted-foreground text-xs">Jenis pemohon</dt>
                                    <dd class="mt-0.5 truncate font-medium">{{ kategoriLabel }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div class="relative hidden w-px lg:block" aria-hidden="true">
                        <span class="bg-background absolute -top-3 left-1/2 size-6 -translate-x-1/2 rounded-full"></span>
                        <span class="absolute inset-y-6 left-0 border-l-2 border-dashed border-(--wp-hairline)"></span>
                        <span class="bg-background absolute -bottom-3 left-1/2 size-6 -translate-x-1/2 rounded-full"></span>
                    </div>
                    <div class="relative h-px lg:hidden" aria-hidden="true">
                        <span class="bg-background absolute top-1/2 -left-3 size-6 -translate-y-1/2 rounded-full"></span>
                        <span class="absolute inset-x-6 top-0 border-t-2 border-dashed border-(--wp-hairline)"></span>
                        <span class="bg-background absolute top-1/2 -right-3 size-6 -translate-y-1/2 rounded-full"></span>
                    </div>

                    <div class="flex flex-col justify-between gap-6 p-6 sm:p-8">
                        <div>
                            <p class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Status</p>
                            <SbStatusBadge class="mt-2" :status="booking.status" audience="renter" :label="statusLabel(booking.status)" />
                        </div>
                        <div>
                            <p class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Total biaya</p>
                            <p class="mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ formatRp(booking.grand_total) }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-(--wp-hairline) px-6 py-6 sm:px-8">
                    <h2 class="sr-only">Progres pesanan</h2>
                    <ol class="relative grid grid-cols-4 gap-2">
                        <span class="absolute top-4 right-[12.5%] left-[12.5%] h-0.5 rounded-full bg-(--wp-hairline)" aria-hidden="true">
                            <span
                                class="block h-full rounded-full bg-(--wp-accent) transition-[width] duration-700 ease-(--wp-ease)"
                                :style="{ width: progressPercent }"
                            ></span>
                        </span>
                        <li v-for="(step, i) in progressSteps" :key="step" class="relative flex flex-col items-center text-center">
                            <span
                                class="ring-card relative grid size-8 place-items-center rounded-full text-xs font-semibold tabular-nums ring-4"
                                :class="{
                                    'bg-(--wp-accent) text-(--wp-accent-contrast)': stepState(i) === 'done',
                                    'bg-(--wp-accent-soft) text-(--wp-accent-strong) outline-2 outline-(--wp-accent)': stepState(i) === 'current',
                                    'sb-tone-danger': stepState(i) === 'halted',
                                    'bg-muted text-muted-foreground': stepState(i) === 'upcoming',
                                }"
                                aria-hidden="true"
                            >
                                <FontAwesomeIcon v-if="stepState(i) === 'done'" :icon="['fas', 'check']" class="size-3.5" />
                                <FontAwesomeIcon v-else-if="stepState(i) === 'halted'" :icon="['fas', 'xmark']" class="size-3.5" />
                                <template v-else>{{ i + 1 }}</template>
                            </span>
                            <p
                                class="mt-2 text-xs leading-tight font-medium sm:text-sm"
                                :class="stepState(i) === 'upcoming' ? 'text-muted-foreground' : 'text-foreground'"
                                :aria-current="stepState(i) === 'current' ? 'step' : undefined"
                            >
                                {{ step }}
                            </p>
                            <p v-if="stepState(i) === 'current'" class="text-muted-foreground mt-0.5 hidden text-xs sm:block">Sedang berlangsung</p>
                            <p v-else-if="stepState(i) === 'halted'" class="mt-0.5 text-xs text-(--sb-danger)">{{ statusLabel(booking.status) }}</p>
                            <span class="sr-only">
                                {{ stepState(i) === 'done' ? 'Selesai' : stepState(i) === 'upcoming' ? 'Belum' : '' }}
                            </span>
                        </li>
                    </ol>
                </div>
            </header>

            <p
                v-if="statusNote"
                class="sb-callout"
                :class="{
                    'sb-tone-danger': booking.status === 'rejected',
                    'sb-tone-attention': booking.status === 'perlu_klarifikasi',
                    'sb-tone-meeting': booking.status === 'menunggu_meeting',
                }"
            >
                <FontAwesomeIcon
                    :icon="['fas', booking.status === 'menunggu_meeting' ? 'calendar-days' : 'triangle-exclamation']"
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>
                    <span class="font-semibold"
                        >{{
                            booking.status === 'rejected'
                                ? 'Alasan tidak disetujui'
                                : booking.status === 'menunggu_meeting'
                                  ? 'Undangan meeting'
                                  : 'Catatan pengelola'
                        }}:</span
                    >
                    {{ statusNote }}
                </span>
            </p>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:gap-8">
                <div class="space-y-6">
                    <section class="sb-card p-6 sm:p-8" aria-labelledby="biaya-heading">
                        <div class="flex items-baseline justify-between gap-4">
                            <h2 id="biaya-heading" class="text-lg font-semibold tracking-tight">Rincian biaya</h2>
                            <span class="text-muted-foreground text-xs">{{ kategoriLabel }}</span>
                        </div>
                        <ul v-if="booking.items.length || booking.addons.length" class="mt-5 space-y-3 text-sm">
                            <li v-for="(item, i) in booking.items" :key="`item-${i}`" class="flex items-baseline gap-3">
                                <span class="min-w-0">
                                    {{ item.uraian }}
                                    <span class="text-muted-foreground block text-xs tabular-nums">{{ item.qty }} × {{ item.satuan }}</span>
                                </span>
                                <span class="mb-1 flex-1 self-end border-b border-dotted border-(--wp-hairline)" aria-hidden="true"></span>
                                <span class="font-medium tabular-nums">{{ formatRp(item.line_total) }}</span>
                            </li>
                            <li v-for="(addon, i) in booking.addons" :key="`addon-${i}`" class="flex items-baseline gap-3">
                                <span class="min-w-0">
                                    {{ addon.name }}
                                    <span class="text-muted-foreground block text-xs tabular-nums">Tambahan · {{ addon.qty }}×</span>
                                </span>
                                <span class="mb-1 flex-1 self-end border-b border-dotted border-(--wp-hairline)" aria-hidden="true"></span>
                                <span class="font-medium tabular-nums">{{ formatRp(addon.line_total) }}</span>
                            </li>
                        </ul>
                        <dl class="mt-6 space-y-2 border-t border-dashed border-(--wp-hairline) pt-5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Biaya dasar</dt>
                                <dd class="tabular-nums">{{ formatRp(booking.subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Tambahan layanan</dt>
                                <dd class="tabular-nums">{{ formatRp(booking.addon_total) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl bg-(--wp-accent-soft) px-4 py-3">
                            <span class="text-sm font-semibold text-(--wp-accent-strong)">Total</span>
                            <span class="text-xl font-bold text-(--wp-accent-strong) tabular-nums">{{ formatRp(booking.grand_total) }}</span>
                        </div>
                        <p v-if="booking.keterangan" class="text-muted-foreground mt-5 text-sm leading-relaxed">
                            <span class="text-foreground font-medium">Keterangan:</span> {{ booking.keterangan }}
                        </p>
                    </section>

                    <section v-if="booking.surat_permohonan_url" class="sb-card p-6 sm:p-8" aria-labelledby="surat-permohonan-heading">
                        <h2 id="surat-permohonan-heading" class="text-lg font-semibold tracking-tight">Surat permohonan Anda</h2>
                        <div class="mt-5 flex flex-col gap-4 rounded-2xl p-4 text-sm ring-1 ring-(--wp-hairline) sm:flex-row sm:items-center">
                            <span class="wp-icon size-11 shrink-0">
                                <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="size-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ booking.surat_permohonan_name ?? 'Surat permohonan' }}</p>
                                <p v-if="booking.surat_permohonan_submitted_at" class="text-muted-foreground mt-0.5 text-xs tabular-nums">
                                    Diunggah {{ booking.surat_permohonan_submitted_at }}
                                </p>
                            </div>
                            <a
                                :href="booking.surat_permohonan_url"
                                target="_blank"
                                rel="noopener"
                                class="wp-btn wp-btn-quiet shrink-0 px-4 py-2 text-sm"
                            >
                                <FontAwesomeIcon :icon="['fas', 'download']" class="size-3.5" aria-hidden="true" />
                                Lihat PDF
                            </a>
                        </div>
                    </section>

                    <section v-if="booking.surats.length" class="sb-card p-6 sm:p-8" aria-labelledby="surat-heading">
                        <h2 id="surat-heading" class="text-lg font-semibold tracking-tight">Surat dari pengelola</h2>
                        <ul class="mt-5 space-y-3">
                            <li
                                v-for="s in booking.surats"
                                :key="s.id"
                                class="group flex flex-col gap-4 rounded-2xl p-4 text-sm ring-1 ring-(--wp-hairline) transition hover:ring-(--wp-accent)/40 sm:flex-row sm:items-center"
                            >
                                <span class="wp-icon size-11 shrink-0">
                                    <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ s.jenis_label }}</p>
                                    <p v-if="s.nomor_surat || s.perihal" class="text-muted-foreground mt-0.5 text-xs">
                                        <span v-if="s.nomor_surat" class="font-mono tabular-nums">{{ s.nomor_surat }}</span>
                                        <template v-if="s.nomor_surat && s.perihal"> · </template>
                                        <span v-if="s.perihal">{{ s.perihal }}</span>
                                    </p>
                                    <p v-if="s.meeting_at || s.meeting_place" class="mt-2 flex items-center gap-1.5 text-xs">
                                        <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-3 text-(--wp-accent)" aria-hidden="true" />
                                        Meeting <span class="tabular-nums">{{ s.meeting_at?.replace('T', ' ') }}</span
                                        >{{ s.meeting_place ? ' · ' + s.meeting_place : '' }}
                                    </p>
                                    <p v-if="s.dokumen?.length" class="text-muted-foreground mt-1 text-xs">Dokumen: {{ s.dokumen.join(', ') }}</p>
                                </div>
                                <a :href="s.download_url" class="wp-btn wp-btn-quiet shrink-0 px-4 py-2 text-sm">
                                    <FontAwesomeIcon :icon="['fas', 'download']" class="size-3.5" aria-hidden="true" />
                                    Unduh PDF
                                </a>
                            </li>
                        </ul>
                    </section>

                    <section v-if="booking.dokumen_wajib.length" class="sb-card-muted p-6 sm:p-8" aria-labelledby="dokumen-heading">
                        <h2 id="dokumen-heading" class="text-lg font-semibold tracking-tight">Dokumen yang perlu disiapkan</h2>
                        <p class="text-muted-foreground mt-1 text-sm leading-relaxed">Bawa dokumen berikut saat meeting dengan pengelola.</p>
                        <ol class="mt-5 grid gap-2 text-sm sm:grid-cols-2">
                            <li v-for="(dok, i) in booking.dokumen_wajib" :key="i" class="bg-background/70 flex items-start gap-3 rounded-xl p-3">
                                <span
                                    class="grid size-6 shrink-0 place-items-center rounded-md bg-(--wp-accent-soft) text-xs font-semibold text-(--wp-accent-strong) tabular-nums"
                                    aria-hidden="true"
                                >
                                    {{ i + 1 }}
                                </span>
                                <span class="pt-0.5">{{ dok }}</span>
                            </li>
                        </ol>
                    </section>

                    <section v-if="booking.status_logs.length" class="sb-card p-6 sm:p-8" aria-labelledby="riwayat-heading">
                        <h2 id="riwayat-heading" class="text-lg font-semibold tracking-tight">Perjalanan pesanan</h2>
                        <ol class="mt-5 space-y-0 text-sm">
                            <li v-for="(log, i) in [...booking.status_logs].reverse()" :key="i" class="relative flex gap-4 pb-6 last:pb-0">
                                <span
                                    v-if="i < booking.status_logs.length - 1"
                                    class="absolute top-4 left-1.25 h-full w-px bg-(--wp-hairline)"
                                    aria-hidden="true"
                                ></span>
                                <span
                                    class="relative mt-1.5 size-2.5 shrink-0 rounded-full"
                                    :class="i === 0 ? 'bg-(--wp-accent) ring-4 ring-(--wp-accent-soft)' : 'bg-(--wp-hairline)'"
                                    aria-hidden="true"
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                                        <p class="font-medium">{{ statusLabel(log.to_status || '-') }}</p>
                                        <p v-if="log.created_at" class="text-muted-foreground text-xs tabular-nums">{{ log.created_at }}</p>
                                    </div>
                                    <p v-if="log.from_status" class="text-muted-foreground text-xs">dari {{ statusLabel(log.from_status) }}</p>
                                    <p v-if="log.note" class="bg-muted/60 mt-2 rounded-lg px-3 py-2 text-xs leading-relaxed">{{ log.note }}</p>
                                </div>
                            </li>
                        </ol>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <section class="sb-card overflow-hidden" aria-labelledby="langkah-heading">
                        <div class="border-b border-(--wp-hairline) bg-(--wp-accent-soft) px-6 py-5">
                            <h2 id="langkah-heading" class="text-xs font-semibold tracking-wide text-(--wp-accent-strong) uppercase">
                                Langkah berikutnya
                            </h2>
                            <p class="mt-1 text-lg font-semibold tracking-tight">{{ title }}</p>
                            <p class="text-muted-foreground mt-1 text-sm leading-relaxed">{{ nextStepText }}</p>
                        </div>

                        <div class="space-y-5 p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="flex items-center gap-2 text-sm font-semibold">
                                    <FontAwesomeIcon :icon="['fas', 'building-columns']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                                    Pembayaran
                                </h3>
                                <span
                                    v-if="booking.payment"
                                    class="sb-badge"
                                    :class="booking.payment.status === 'verified' ? 'sb-tone-success' : 'sb-tone-neutral'"
                                    >{{ paymentNotOpen ? 'Belum dibuka' : paymentStatusLabel(booking.payment.status) }}</span
                                >
                            </div>

                            <template v-if="booking.payment">
                                <p v-if="paymentRejected" class="sb-callout sb-tone-danger">
                                    <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span>
                                        Bukti pembayaran ditolak. Silakan unggah ulang bukti yang benar.
                                        <template v-if="booking.payment.notes"> Alasan: {{ booking.payment.notes }}</template>
                                    </span>
                                </p>

                                <div v-if="paymentNotOpen" class="rounded-2xl bg-(--wp-accent-soft) p-4 text-sm leading-relaxed">
                                    <p class="flex items-center gap-2 text-xs font-semibold text-(--wp-accent-strong)">
                                        <FontAwesomeIcon :icon="['fas', 'clock']" class="size-3.5" aria-hidden="true" />
                                        Pembayaran dibuka mulai
                                    </p>
                                    <p v-if="booking.payment.opens_at" class="mt-1 text-base font-semibold tabular-nums">
                                        {{ formatDateTime(booking.payment.opens_at) }}
                                    </p>
                                    <p class="text-muted-foreground mt-2">{{ booking.payment.message }}</p>
                                </div>

                                <div v-if="countdownParts.length" class="rounded-2xl p-4 ring-1 ring-(--sb-warning)/40" role="timer" aria-live="off">
                                    <p class="flex items-center gap-2 text-xs font-medium text-(--sb-warning)">
                                        <FontAwesomeIcon :icon="['fas', 'clock']" class="size-3.5" aria-hidden="true" />
                                        Sisa waktu bayar
                                    </p>
                                    <div class="mt-2 flex items-end gap-2">
                                        <template v-for="(part, i) in countdownParts" :key="part.unit">
                                            <span v-if="i > 0" class="text-muted-foreground pb-5 text-xl font-light" aria-hidden="true">:</span>
                                            <span class="flex flex-col items-center">
                                                <span class="text-3xl font-bold tracking-tight tabular-nums">{{ part.value }}</span>
                                                <span class="text-muted-foreground text-[11px]">{{ part.unit }}</span>
                                            </span>
                                        </template>
                                    </div>
                                    <p v-if="booking.payment.expires_at" class="text-muted-foreground mt-2 text-xs tabular-nums">
                                        Paling lambat {{ booking.payment.expires_at }}
                                    </p>
                                </div>
                                <p v-else-if="countdown" class="sb-callout sb-tone-danger">
                                    <FontAwesomeIcon :icon="['fas', 'clock']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span>{{ countdown }}</span>
                                </p>

                                <div class="flex items-baseline justify-between gap-4 text-sm">
                                    <span class="text-muted-foreground">{{
                                        booking.payment.status === 'verified' ? 'Jumlah dibayar' : 'Jumlah transfer'
                                    }}</span>
                                    <span class="text-xl font-bold tabular-nums">{{ formatRp(booking.payment.amount) }}</span>
                                </div>

                                <div v-if="booking.payment.bank && !paymentNotOpen" class="bg-muted/70 rounded-2xl p-4 text-sm">
                                    <p class="text-muted-foreground text-xs">{{ booking.payment.bank }}</p>
                                    <div class="mt-1 flex items-center justify-between gap-3">
                                        <p class="font-mono text-lg font-semibold tracking-wider tabular-nums">{{ booking.payment.rekening }}</p>
                                        <button
                                            v-if="booking.payment.rekening"
                                            type="button"
                                            class="wp-btn wp-btn-quiet shrink-0 px-3 py-1.5 text-xs"
                                            @click="copyText('rekening', booking.payment.rekening)"
                                        >
                                            <FontAwesomeIcon
                                                :icon="['fas', copied === 'rekening' ? 'check' : 'copy']"
                                                class="size-3"
                                                aria-hidden="true"
                                            />
                                            {{ copied === 'rekening' ? 'Disalin' : 'Salin' }}
                                        </button>
                                    </div>
                                    <p class="text-muted-foreground mt-1 text-xs">Atas nama {{ booking.payment.atas_nama }}</p>
                                </div>

                                <a
                                    v-if="booking.payment.bukti_url"
                                    :href="booking.payment.bukti_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex items-center justify-between gap-3 rounded-xl p-3 text-sm ring-1 ring-(--wp-hairline) transition hover:bg-(--wp-accent-soft) focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                >
                                    <span class="flex items-center gap-2">
                                        <FontAwesomeIcon :icon="['fas', 'check']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                                        Bukti sudah dikirim
                                    </span>
                                    <span class="flex items-center gap-1.5 font-semibold text-(--wp-accent-strong)">
                                        Lihat
                                        <FontAwesomeIcon :icon="['fas', 'arrow-up-right-from-square']" class="size-3" aria-hidden="true" />
                                    </span>
                                </a>
                            </template>
                            <p v-else class="text-muted-foreground text-sm leading-relaxed">
                                Petunjuk pembayaran akan muncul setelah pengajuan Anda disetujui pengelola.
                            </p>

                            <form
                                v-if="booking.can_upload_bukti"
                                class="space-y-4 border-t border-(--wp-hairline) pt-5"
                                @submit.prevent="submitBukti"
                            >
                                <h3 class="text-sm font-semibold">
                                    {{ paymentRejected ? 'Unggah ulang bukti pembayaran' : 'Kirim bukti pembayaran' }}
                                </h3>

                                <label
                                    v-if="!form.bukti"
                                    for="bukti-file"
                                    class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed px-4 py-7 text-center transition focus-within:ring-2 focus-within:ring-(--wp-accent)"
                                    :class="
                                        dragging
                                            ? 'border-(--wp-accent) bg-(--wp-accent-soft)'
                                            : 'border-(--wp-hairline) hover:border-(--wp-accent)/50'
                                    "
                                    @dragover.prevent="dragging = true"
                                    @dragleave.prevent="dragging = false"
                                    @drop.prevent="onDrop"
                                >
                                    <span class="wp-icon size-10">
                                        <FontAwesomeIcon :icon="['fas', 'cloud-arrow-up']" class="size-4" aria-hidden="true" />
                                    </span>
                                    <span class="text-sm font-medium">Pilih file atau tarik ke sini</span>
                                    <span id="bukti-hint" class="text-muted-foreground text-xs">Format JPG, PNG, atau PDF</span>
                                    <input
                                        id="bukti-file"
                                        ref="fileInput"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        class="sr-only"
                                        :aria-invalid="form.errors.bukti ? 'true' : undefined"
                                        aria-describedby="bukti-hint"
                                        @change="onFileChange"
                                    />
                                </label>
                                <div v-else class="flex items-center gap-3 rounded-2xl bg-(--wp-accent-soft) p-3">
                                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-(--wp-accent) text-(--wp-accent-contrast)">
                                        <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium">{{ form.bukti.name }}</span>
                                        <span class="text-muted-foreground block text-xs tabular-nums">{{ fileSize }}</span>
                                    </span>
                                    <button
                                        type="button"
                                        class="text-muted-foreground hover:text-foreground grid size-8 shrink-0 place-items-center rounded-lg transition focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                        aria-label="Hapus file"
                                        @click="clearFile"
                                    >
                                        <FontAwesomeIcon :icon="['fas', 'xmark']" class="size-3.5" aria-hidden="true" />
                                    </button>
                                </div>
                                <p v-if="form.errors.bukti" class="sb-error">{{ form.errors.bukti }}</p>

                                <div>
                                    <label for="bukti-catatan" class="sb-label">Catatan</label>
                                    <textarea
                                        id="bukti-catatan"
                                        v-model="form.notes"
                                        rows="2"
                                        placeholder="Catatan tambahan (boleh dikosongkan)"
                                        class="sb-input"
                                    />
                                </div>
                                <button
                                    type="submit"
                                    class="wp-btn wp-btn-primary w-full px-5 py-2.5 text-sm disabled:opacity-60"
                                    :disabled="form.processing || !form.bukti"
                                >
                                    <FontAwesomeIcon
                                        v-if="form.processing"
                                        :icon="['fas', 'circle-notch']"
                                        class="size-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                    <FontAwesomeIcon v-else :icon="['fas', 'upload']" class="size-4" aria-hidden="true" />
                                    Kirim bukti
                                </button>
                            </form>

                            <a
                                v-if="booking.surats.length"
                                :href="booking.surats[0].download_url"
                                class="wp-btn wp-btn-quiet w-full px-4 py-2 text-sm"
                            >
                                <FontAwesomeIcon :icon="['fas', 'download']" class="size-3.5" aria-hidden="true" />
                                Unduh surat terbaru
                            </a>
                        </div>
                    </section>

                    <section class="sb-card-muted p-6 text-sm" aria-labelledby="bantuan-heading">
                        <h2 id="bantuan-heading" class="flex items-center gap-2 text-base font-semibold tracking-tight">
                            <FontAwesomeIcon :icon="['fas', 'headset']" class="size-4 text-(--wp-accent)" aria-hidden="true" />
                            Butuh bantuan?
                        </h2>
                        <p class="text-muted-foreground mt-2 leading-relaxed">
                            Hubungi UPT Dispora Kabupaten Bogor dan sebutkan kode pesanan
                            <span class="text-foreground font-mono tabular-nums">{{ booking.nomor }}</span> agar pengecekan lebih cepat.
                        </p>
                    </section>

                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('e-booking.bookings.index')" class="wp-btn wp-btn-quiet px-4 py-2 text-sm">
                            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
                            Pesanan saya
                        </Link>
                        <Link :href="route('e-booking.catalog')" class="wp-btn wp-btn-quiet px-4 py-2 text-sm">
                            <FontAwesomeIcon :icon="['fas', 'magnifying-glass']" class="size-3.5" aria-hidden="true" />
                            Cari tempat lain
                        </Link>
                    </div>
                </aside>
            </div>
        </div>
    </EBookingLayout>
</template>
