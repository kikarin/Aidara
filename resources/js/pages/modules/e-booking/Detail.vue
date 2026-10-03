<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faArrowUpRightFromSquare,
    faBuildingColumns,
    faCheck,
    faCircleNotch,
    faClock,
    faDownload,
    faFileLines,
    faHeadset,
    faMagnifyingGlass,
    faTriangleExclamation,
    faUpload,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

library.add(
    faArrowLeft,
    faArrowUpRightFromSquare,
    faBuildingColumns,
    faCheck,
    faCircleNotch,
    faClock,
    faDownload,
    faFileLines,
    faHeadset,
    faMagnifyingGlass,
    faTriangleExclamation,
    faUpload,
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
        venue: { id: number; code: string; name: string } | null;
        areas: Array<{ id: number; code: string; name: string }>;
        items: Array<{ uraian: string; satuan: string; qty: number; line_total: number }>;
        addons: Array<{ name: string; qty: number; line_total: number }>;
        dokumen_wajib: string[];
        surats: Array<{
            id: number;
            jenis_label: string;
            nomor_surat: string;
            perihal: string;
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

const form = useForm({
    bukti: null as File | null,
    notes: '',
});

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    form.bukti = input.files?.[0] ?? null;
};

const submitBukti = () => {
    form.post(route('e-booking.bookings.bukti', props.booking.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('bukti', 'notes');
        },
    });
};

const countdown = ref('');
let timer: ReturnType<typeof setInterval> | null = null;

const updateCountdown = () => {
    const raw = props.booking.payment?.expires_at;
    if (!raw) {
        countdown.value = '';

        return;
    }

    const end = new Date(raw.replace(' ', 'T')).getTime();
    const diff = end - Date.now();
    if (Number.isNaN(end) || diff <= 0) {
        countdown.value = 'Tenggat habis';

        return;
    }

    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    const s = Math.floor((diff % 60_000) / 1000);
    countdown.value = `${h}j ${m}m ${s}d`;
};

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});

const title = computed(() => {
    if (props.booking.status === 'awaiting_payment') {
        return 'Menunggu pembayaran';
    }
    if (props.booking.status === 'menunggu_approval') {
        return 'Menunggu ditinjau';
    }

    return `Pesanan ${props.booking.nomor}`;
});

const nextStepText = computed(() => {
    if (props.booking.status === 'awaiting_payment') {
        return 'Silakan transfer sesuai petunjuk di bawah, lalu kirim bukti pembayaran.';
    }
    if (props.booking.status === 'menunggu_approval') {
        return `Pengajuan Anda sudah masuk. Proses peninjauan maksimal ${props.slaHariKerja ?? 7} hari kerja — balasan berupa surat akan dikirim setelah selesai.`;
    }
    if (props.booking.status === 'perlu_klarifikasi') {
        return 'Pengelola perlu konfirmasi tambahan. Mohon cek catatan terbaru.';
    }

    return 'Lihat rincian pesanan dan pantau perkembangannya di halaman ini.';
});

const progressSteps = ['Pengajuan dikirim', 'Ditinjau pengelola', 'Pembayaran', 'Terkonfirmasi'];

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
    if (['menunggu_approval', 'perlu_klarifikasi', 'rejected', 'cancelled'].includes(s)) {
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
    if (!['rejected', 'perlu_klarifikasi'].includes(props.booking.status)) {
        return null;
    }

    return [...props.booking.status_logs].reverse().find((log) => log.to_status === props.booking.status && log.note)?.note ?? null;
});

const heading = computed(() => props.booking.venue?.name ?? title.value);
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

            <header class="space-y-5">
                <Link
                    :href="route('e-booking.bookings.index')"
                    class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-lg text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                >
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
                    Pesanan saya
                </Link>

                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0 space-y-3">
                        <p class="wp-eyebrow">
                            Kode pesanan <span class="font-mono tabular-nums">{{ booking.nomor }}</span>
                        </p>
                        <h1 class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ heading }}</h1>
                        <p v-if="booking.areas?.length" class="text-muted-foreground text-sm leading-relaxed">
                            {{ booking.areas.map((a) => a.name).join(', ') }}
                        </p>
                    </div>
                    <SbStatusBadge :status="booking.status" audience="renter" :label="statusLabel(booking.status)" />
                </div>
            </header>

            <p v-if="statusNote" class="sb-callout" :class="booking.status === 'rejected' ? 'sb-tone-danger' : 'sb-tone-warning'">
                <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span>
                    <span class="font-semibold">{{ booking.status === 'rejected' ? 'Alasan tidak disetujui' : 'Catatan pengelola' }}:</span>
                    {{ statusNote }}
                </span>
            </p>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)] lg:gap-8">
                <div class="space-y-6">
                    <section class="sb-card p-6" aria-labelledby="progres-heading">
                        <h2 id="progres-heading" class="text-lg font-semibold tracking-tight">Progres pesanan</h2>
                        <ol class="mt-5 space-y-0">
                            <li v-for="(step, i) in progressSteps" :key="step" class="relative flex gap-4 pb-6 last:pb-0">
                                <span
                                    v-if="i < progressSteps.length - 1"
                                    class="absolute top-8 left-3.75 h-[calc(100%-2rem)] w-px"
                                    :class="stepState(i) === 'done' ? 'bg-(--wp-accent)' : 'bg-(--wp-hairline)'"
                                    aria-hidden="true"
                                ></span>
                                <span
                                    class="relative flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold tabular-nums"
                                    :class="{
                                        'bg-(--wp-accent) text-(--wp-accent-contrast)': stepState(i) === 'done',
                                        'bg-(--wp-accent-soft) text-(--wp-accent-strong) ring-2 ring-(--wp-accent)': stepState(i) === 'current',
                                        'sb-tone-danger': stepState(i) === 'halted',
                                        'bg-muted text-muted-foreground': stepState(i) === 'upcoming',
                                    }"
                                    aria-hidden="true"
                                >
                                    <FontAwesomeIcon v-if="stepState(i) === 'done'" :icon="['fas', 'check']" class="size-3.5" />
                                    <template v-else>{{ i + 1 }}</template>
                                </span>
                                <div class="min-w-0 pt-1">
                                    <p class="text-sm font-medium" :class="stepState(i) === 'upcoming' ? 'text-muted-foreground' : 'text-foreground'">
                                        {{ step }}
                                    </p>
                                    <p v-if="stepState(i) === 'current'" class="text-muted-foreground mt-0.5 text-xs">Sedang berlangsung</p>
                                    <p v-else-if="stepState(i) === 'halted'" class="mt-0.5 text-xs text-(--sb-danger)">
                                        {{ statusLabel(booking.status) }}
                                    </p>
                                    <span class="sr-only">
                                        {{ stepState(i) === 'done' ? 'Selesai' : stepState(i) === 'upcoming' ? 'Belum' : '' }}
                                    </span>
                                </div>
                            </li>
                        </ol>
                    </section>

                    <section class="sb-card p-6" aria-labelledby="jadwal-heading">
                        <h2 id="jadwal-heading" class="text-lg font-semibold tracking-tight">Jadwal dan keperluan</h2>
                        <dl class="mt-4 divide-y divide-(--wp-hairline) text-sm">
                            <div class="flex justify-between gap-4 py-3 first:pt-0">
                                <dt class="text-muted-foreground">Tempat</dt>
                                <dd class="text-right font-medium">{{ booking.venue?.name }}</dd>
                            </div>
                            <div v-if="booking.areas?.length" class="flex justify-between gap-4 py-3">
                                <dt class="text-muted-foreground">Area</dt>
                                <dd class="text-right">{{ booking.areas.map((a) => a.name).join(', ') }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-muted-foreground">Jadwal</dt>
                                <dd class="text-right tabular-nums">{{ booking.starts_at }} — {{ booking.ends_at }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-3">
                                <dt class="text-muted-foreground">Keperluan</dt>
                                <dd class="text-right">{{ booking.tujuan }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-3 last:pb-0">
                                <dt class="text-muted-foreground">Jenis pemohon</dt>
                                <dd class="text-right">
                                    {{ booking.kategori_tarif === 'pemerintah' ? 'Instansi pemerintah' : 'Umum / non pemerintah' }}
                                </dd>
                            </div>
                        </dl>
                    </section>

                    <section class="sb-card p-6" aria-labelledby="biaya-heading">
                        <h2 id="biaya-heading" class="text-lg font-semibold tracking-tight">Rincian biaya</h2>
                        <ul v-if="booking.items.length" class="mt-4 space-y-2.5 text-sm">
                            <li v-for="(item, i) in booking.items" :key="i" class="flex justify-between gap-4">
                                <span class="text-muted-foreground">
                                    {{ item.uraian }}
                                    <span class="text-xs tabular-nums">({{ item.satuan }} × {{ item.qty }})</span>
                                </span>
                                <span class="tabular-nums">{{ formatRp(item.line_total) }}</span>
                            </li>
                        </ul>
                        <dl class="mt-4 space-y-2 border-t border-(--wp-hairline) pt-4 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Biaya dasar</dt>
                                <dd class="tabular-nums">{{ formatRp(booking.subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Tambahan layanan</dt>
                                <dd class="tabular-nums">{{ formatRp(booking.addon_total) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 border-t border-(--wp-hairline) pt-3 text-base font-bold">
                                <dt>Total</dt>
                                <dd class="tabular-nums">{{ formatRp(booking.grand_total) }}</dd>
                            </div>
                        </dl>
                    </section>

                    <section v-if="booking.status_logs.length" class="sb-card p-6" aria-labelledby="riwayat-heading">
                        <h2 id="riwayat-heading" class="text-lg font-semibold tracking-tight">Perjalanan pesanan</h2>
                        <ol class="mt-4 space-y-4 border-l border-(--wp-hairline) pl-5 text-sm">
                            <li v-for="(log, i) in booking.status_logs" :key="i" class="relative">
                                <span class="absolute top-1.5 -left-6.25 size-2.5 rounded-full bg-(--wp-accent)" aria-hidden="true"></span>
                                <p class="text-muted-foreground">
                                    <span class="text-foreground font-medium">{{ statusLabel(log.to_status || '-') }}</span>
                                    <template v-if="log.from_status"> dari {{ statusLabel(log.from_status) }}</template>
                                </p>
                                <p v-if="log.created_at" class="text-muted-foreground mt-0.5 text-xs tabular-nums">{{ log.created_at }}</p>
                                <p v-if="log.note" class="text-muted-foreground mt-1 text-xs leading-relaxed">{{ log.note }}</p>
                            </li>
                        </ol>
                    </section>

                    <section v-if="booking.dokumen_wajib.length" class="sb-card-muted p-6" aria-labelledby="dokumen-heading">
                        <h2 id="dokumen-heading" class="text-lg font-semibold tracking-tight">Dokumen yang perlu disiapkan</h2>
                        <p class="text-muted-foreground mt-1 text-sm leading-relaxed">Bawa dokumen berikut saat meeting dengan pengelola.</p>
                        <ul class="mt-4 space-y-2 text-sm">
                            <li v-for="(dok, i) in booking.dokumen_wajib" :key="i" class="flex gap-2.5">
                                <FontAwesomeIcon
                                    :icon="['fas', 'file-lines']"
                                    class="mt-0.5 size-3.5 shrink-0 text-(--wp-accent)"
                                    aria-hidden="true"
                                />
                                <span>{{ dok }}</span>
                            </li>
                        </ul>
                    </section>

                    <section v-if="booking.surats.length" class="sb-card p-6" aria-labelledby="surat-heading">
                        <h2 id="surat-heading" class="text-lg font-semibold tracking-tight">Surat dari pengelola</h2>
                        <ul class="mt-4 space-y-3">
                            <li v-for="s in booking.surats" :key="s.id" class="rounded-2xl border border-(--wp-hairline) p-4 text-sm">
                                <div class="flex items-start gap-3">
                                    <span class="wp-icon size-10 shrink-0">
                                        <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold">{{ s.jenis_label }}</p>
                                        <p class="text-muted-foreground mt-0.5 text-xs">
                                            <span class="font-mono tabular-nums">{{ s.nomor_surat }}</span> · {{ s.perihal }}
                                        </p>
                                        <p v-if="s.meeting_at || s.meeting_place" class="text-foreground mt-2 text-xs">
                                            Meeting: <span class="tabular-nums">{{ s.meeting_at?.replace('T', ' ') }}</span
                                            >{{ s.meeting_place ? ' · ' + s.meeting_place : '' }}
                                        </p>
                                        <p v-if="s.dokumen?.length" class="text-muted-foreground mt-1 text-xs">Dokumen: {{ s.dokumen.join(', ') }}</p>
                                        <a :href="s.download_url" class="wp-btn wp-btn-quiet mt-3 px-4 py-2 text-sm">
                                            <FontAwesomeIcon :icon="['fas', 'download']" class="size-3.5" aria-hidden="true" />
                                            Unduh surat (PDF)
                                        </a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                    <section class="sb-card p-6" aria-labelledby="langkah-heading">
                        <h2 id="langkah-heading" class="text-lg font-semibold tracking-tight">Langkah berikutnya</h2>
                        <p class="mt-1 text-sm font-medium">{{ title }}</p>
                        <p class="text-muted-foreground mt-1 text-sm leading-relaxed">{{ nextStepText }}</p>

                        <div class="mt-5 border-t border-(--wp-hairline) pt-5">
                            <h3 class="flex items-center gap-2 text-sm font-semibold">
                                <FontAwesomeIcon :icon="['fas', 'building-columns']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                                Pembayaran
                            </h3>

                            <template v-if="booking.payment">
                                <dl class="mt-3 space-y-2 text-sm">
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-muted-foreground">Status</dt>
                                        <dd class="font-medium">{{ statusLabel(booking.payment.status) }}</dd>
                                    </div>
                                    <div class="flex justify-between gap-4">
                                        <dt class="text-muted-foreground">Jumlah</dt>
                                        <dd class="font-semibold tabular-nums">{{ formatRp(booking.payment.amount) }}</dd>
                                    </div>
                                </dl>
                                <div v-if="booking.payment.bank" class="bg-muted mt-4 space-y-1 rounded-xl p-4 text-sm">
                                    <p class="font-medium">{{ booking.payment.bank }}</p>
                                    <p class="font-mono text-base tracking-wide tabular-nums">{{ booking.payment.rekening }}</p>
                                    <p class="text-muted-foreground text-xs">Atas nama {{ booking.payment.atas_nama }}</p>
                                </div>
                                <p v-if="countdown" class="sb-callout sb-tone-warning mt-4">
                                    <FontAwesomeIcon :icon="['fas', 'clock']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                                    <span>
                                        Batas waktu bayar: <span class="font-semibold tabular-nums">{{ countdown }}</span>
                                        <template v-if="booking.payment.expires_at">
                                            <span class="tabular-nums"> ({{ booking.payment.expires_at }}) </span>
                                        </template>
                                    </span>
                                </p>
                                <div v-if="booking.payment.bukti_url" class="mt-4">
                                    <p class="text-muted-foreground mb-1 text-xs">Bukti yang sudah dikirim</p>
                                    <a
                                        :href="booking.payment.bukti_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="wp-link-arrow inline-flex items-center gap-1.5 rounded text-sm font-semibold text-(--wp-accent-strong) hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                    >
                                        Lihat bukti
                                        <FontAwesomeIcon :icon="['fas', 'arrow-up-right-from-square']" class="size-3" aria-hidden="true" />
                                    </a>
                                </div>
                            </template>
                            <p v-else class="text-muted-foreground mt-2 text-sm leading-relaxed">
                                Petunjuk pembayaran akan muncul setelah pengajuan Anda disetujui pengelola.
                            </p>

                            <form
                                v-if="booking.can_upload_bukti"
                                class="mt-5 space-y-4 border-t border-(--wp-hairline) pt-5"
                                @submit.prevent="submitBukti"
                            >
                                <h3 class="text-sm font-semibold">Kirim bukti pembayaran</h3>
                                <div>
                                    <label for="bukti-file" class="sb-label">File bukti</label>
                                    <input
                                        id="bukti-file"
                                        type="file"
                                        accept=".jpg,.jpeg,.png,.pdf"
                                        required
                                        class="sb-input file:mr-3 file:rounded-lg file:border-0 file:bg-(--wp-accent-soft) file:px-3 file:py-1 file:text-sm file:font-medium file:text-(--wp-accent-strong)"
                                        :aria-invalid="form.errors.bukti ? 'true' : undefined"
                                        aria-describedby="bukti-hint"
                                        @change="onFileChange"
                                    />
                                    <p id="bukti-hint" class="sb-hint">Format JPG, PNG, atau PDF.</p>
                                    <p v-if="form.errors.bukti" class="sb-error">{{ form.errors.bukti }}</p>
                                </div>
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
                        </div>

                        <div v-if="booking.surats.length" class="mt-5 border-t border-(--wp-hairline) pt-5">
                            <a :href="booking.surats[0].download_url" class="wp-btn wp-btn-quiet w-full px-4 py-2 text-sm">
                                <FontAwesomeIcon :icon="['fas', 'download']" class="size-3.5" aria-hidden="true" />
                                Unduh surat
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
