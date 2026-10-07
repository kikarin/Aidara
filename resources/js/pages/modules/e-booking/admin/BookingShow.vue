<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbPitchLines from '@/components/sibola/SbPitchLines.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { bookingStatusLabel, formatRupiah } from '@/lib/bookingStatus';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faArrowRight,
    faArrowUpRightFromSquare,
    faCheck,
    faChevronDown,
    faCircleCheck,
    faCircleInfo,
    faCircleNotch,
    faCircleXmark,
    faClockRotateLeft,
    faCommentDots,
    faCopy,
    faEnvelope,
    faFileArrowDown,
    faFileLines,
    faLocationDot,
    faReceipt,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

library.add(
    faArrowLeft,
    faArrowRight,
    faArrowUpRightFromSquare,
    faCheck,
    faChevronDown,
    faCircleCheck,
    faCircleInfo,
    faCircleNotch,
    faCircleXmark,
    faClockRotateLeft,
    faCommentDots,
    faCopy,
    faEnvelope,
    faFileArrowDown,
    faFileLines,
    faLocationDot,
    faReceipt,
    faTriangleExclamation,
);

type ConflictItem = {
    id: number;
    nomor: string;
    status: string;
    priority_flag: string | null;
    priority_order: number;
    priority_rule: { id: number; code: string; name: string; priority_order: number } | null;
    relation: 'self_unggul' | 'other_unggul' | 'peer' | string;
    area_tentative: boolean;
    starts_at: string | null;
    ends_at: string | null;
};

type ConflictSummary = {
    flag: string;
    needs_clarification: boolean;
    winners: number[];
    losers: number[];
    peers: number[];
    conflicts: ConflictItem[];
    self_priority_order?: number;
    tentative_context: boolean;
    kontak_klarifikasi: string | null;
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
        admin_notes: string | null;
        starts_at: string | null;
        ends_at: string | null;
        grand_total: number;
        subtotal: number;
        addon_total: number;
        venue: { id: number; code: string; name: string } | null;
        areas: Array<{ id: number; code: string; name: string }>;
        user: { id: number; name: string; email: string } | null;
        penyewa: { nama: string; no_hp: string | null; instansi: string | null } | null;
        items: Array<{ uraian: string; satuan: string; qty: number; line_total: number }>;
        jenis_sewa?: 'reguler' | 'event';
        surat_permohonan_url: string | null;
        surat_permohonan_name: string | null;
        surat_permohonan_submitted_at: string | null;
        can_review: boolean;
        can_lanjut_meeting: boolean;
        can_final_decision: boolean;
        can_send_meeting: boolean;
        can_verify_payment: boolean;
        surats: Array<{
            id: number;
            jenis: string;
            jenis_label: string;
            nomor_surat: string | null;
            perihal: string | null;
            meeting_at: string | null;
            meeting_place: string | null;
            dokumen: string[] | null;
            sent_email_at: string | null;
            sent_whatsapp_at: string | null;
            created_at: string | null;
        }>;
    };
    payment: {
        id: number;
        status: string;
        amount: number;
        bank: string | null;
        rekening: string | null;
        atas_nama: string | null;
        bukti_url: string | null;
        notes: string | null;
        expires_at: string | null;
    } | null;
    conflict: ConflictSummary | null;
    priority_rules: Array<{ id: number; name: string; priority_order: number; code: string }>;
    status_logs: Array<{
        from_status: string | null;
        to_status: string | null;
        note: string | null;
        created_at: string | null;
    }>;
}>();

const statusLabel = (status: string) => bookingStatusLabel(status, 'admin');

const paymentStatusLabels: Record<string, string> = { pending: 'Menunggu transfer', verified: 'Lunas', rejected: 'Bukti ditolak' };

const paymentStatusLabel = (status: string) => paymentStatusLabels[status] ?? statusLabel(status);

const isImageUrl = (url: string) => /\.(png|jpe?g|webp|gif)(\?|$)/i.test(url);

const relationLabel = (relation: string) => {
    const map: Record<string, string> = {
        self_unggul: 'Pengajuan ini lebih prioritas',
        other_unggul: 'Pengajuan lain lebih prioritas',
        peer: 'Prioritas setara — perlu klarifikasi',
    };

    return map[relation] ?? relation;
};

const relationTone = (relation: string) => {
    if (relation === 'self_unggul') {
        return 'sb-tone-success';
    }
    if (relation === 'other_unggul') {
        return 'sb-tone-danger';
    }

    return 'sb-tone-warning';
};

const conflictHeadline = computed(() => {
    const c = props.conflict;
    if (!c || !c.conflicts?.length) {
        return null;
    }
    if (c.needs_clarification) {
        return {
            title: 'Ada benturan dengan prioritas setara',
            detail: 'Beberapa pengajuan bentrok di waktu mirip dengan tingkat prioritas sama. Perlu klarifikasi sebelum disetujui.',
            tone: 'amber' as const,
            icon: 'triangle-exclamation',
        };
    }
    if (c.flag === 'unggul') {
        return {
            title: 'Pengajuan ini lebih unggul',
            detail: 'Ada jadwal yang bentrok, tetapi prioritas pengajuan ini lebih tinggi.',
            tone: 'emerald' as const,
            icon: 'circle-check',
        };
    }
    if (c.flag === 'rendah') {
        return {
            title: 'Ada pengajuan lain yang lebih prioritas',
            detail: 'Pertimbangkan tolak, klarifikasi, atau setujui dengan hati-hati (paksa).',
            tone: 'red' as const,
            icon: 'triangle-exclamation',
        };
    }

    return {
        title: 'Ada jadwal yang berdekatan',
        detail: 'Periksa daftar di bawah sebelum memutuskan.',
        tone: 'sky' as const,
        icon: 'circle-info',
    };
});

const conflictBoxClass = computed(() => {
    const tone = conflictHeadline.value?.tone;
    if (tone === 'emerald') {
        return 'sb-tone-success';
    }
    if (tone === 'red') {
        return 'sb-tone-danger';
    }
    if (tone === 'sky') {
        return 'sb-tone-info';
    }

    return 'sb-tone-warning';
});

const priorityRuleOptions = computed(() => [
    { value: 'default', label: 'Pakai aturan bawaan' },
    ...props.priority_rules.map((rule) => ({
        value: rule.id,
        label: `#${rule.priority_order} ${rule.name}`,
    })),
]);

const priorityRuleSelect = computed({
    get: () => (approveForm.priority_rule_id === '' ? 'default' : approveForm.priority_rule_id),
    set: (val: string | number) => {
        approveForm.priority_rule_id = val === 'default' || val === '' ? '' : Number(val);
    },
});

const approveForm = useForm({
    force: false as boolean,
    priority_rule_id: '' as number | '',
    admin_notes: '',
    note: '',
});

const lanjutForm = useForm({
    admin_notes: '',
    surat_balasan: null as File | null,
});

const meetingForm = useForm({
    admin_notes: '',
    meeting_at: '',
    meeting_place: '',
    surat_meeting: null as File | null,
});

const rejectForm = useForm({ reason: '', surat_balasan: null as File | null });
const klarifikasiForm = useForm({ reason: '' });
const verifyForm = useForm({ notes: '' });
const rejectPayForm = useForm({ reason: '' });

const { confirm } = useConfirm();

const klarifikasiChips = [
    'Bentrok dengan agenda prioritas lain',
    'Perlu konfirmasi jumlah peserta/kapasitas',
    'Perlu kelengkapan dokumen',
    'Jadwal perlu disesuaikan dengan agenda venue',
];

const rejectChips = [
    'Dokumen persyaratan tidak lengkap',
    'Jadwal sudah dipesan pihak lain',
    'Data pemohon/instansi tidak valid',
    'Kegiatan tidak sesuai peruntukan fasilitas',
];

const activeDecision = ref<'' | 'klarifikasi' | 'tolak'>('');

const isReguler = computed(() => props.booking.jenis_sewa === 'reguler');

const isWaitingMeeting = computed(() => props.booking.can_final_decision);

const decisionTabs = computed(() => [
    {
        value: '',
        label: isWaitingMeeting.value ? 'Setujui pemakaian' : 'Surat balasan',
        icon: isWaitingMeeting.value ? 'check' : 'paper-plane',
    },
    { value: 'klarifikasi', label: 'Klarifikasi', icon: 'comment-dots' },
    { value: 'tolak', label: 'Tolak', icon: 'circle-xmark' },
]);

const rejectPayOpen = ref(false);

const butuhSuratBalasan = computed(() => !isReguler.value);
const butuhSuratPenolakan = computed(() => butuhSuratBalasan.value && !isWaitingMeeting.value);

const hasMeetingInvitation = computed(() => props.booking.surats.some((s) => s.jenis === 'undangan_meeting'));

const adaBenturan = computed(() => Boolean(props.conflict?.conflicts?.length));

const submitLanjut = () => {
    if (butuhSuratBalasan.value && !lanjutForm.surat_balasan) {
        return;
    }

    lanjutForm.post(route('e-booking.admin.bookings.lanjut', props.booking.id), { preserveScroll: true });
};

const submitApprove = async (force = false) => {
    if (force) {
        const ok = await confirm({
            title: 'Paksa setujui pengajuan ini?',
            description: 'Masih ada benturan jadwal/prioritas. Dengan memaksa, pemakaian lahan disetujui dan benturan diselesaikan secara manual.',
            confirmText: 'Ya, paksa setujui',
        });
        if (!ok) {
            return;
        }
    }

    approveForm.force = force;
    approveForm.post(route('e-booking.admin.bookings.approve', props.booking.id), { preserveScroll: true });
};

const submitReject = async () => {
    if (rejectForm.reason.trim() === '') {
        return;
    }

    if (butuhSuratPenolakan.value && !rejectForm.surat_balasan) {
        return;
    }

    const ok = await confirm({
        title: 'Tolak pengajuan ini?',
        description: `Alasan: "${rejectForm.reason.trim()}" — penyewa akan melihat alasan ini dan tanggal langsung tersedia kembali.`,
        confirmText: 'Ya, tolak',
        variant: 'destructive',
    });
    if (!ok) {
        return;
    }

    rejectForm.post(route('e-booking.admin.bookings.reject', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            activeDecision.value = '';
            rejectForm.reset();
        },
    });
};

const submitKlarifikasi = () => {
    if (klarifikasiForm.reason.trim() === '') {
        return;
    }

    klarifikasiForm.post(route('e-booking.admin.bookings.klarifikasi', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            activeDecision.value = '';
            klarifikasiForm.reset();
        },
    });
};

const submitVerify = () => {
    if (!props.payment) return;
    verifyForm.post(route('e-booking.admin.payments.verify', props.payment.id), { preserveScroll: true });
};

const submitRejectPay = () => {
    if (!props.payment) return;
    rejectPayForm.post(route('e-booking.admin.payments.reject', props.payment.id), { preserveScroll: true });
};

const emailForm = useForm({});

const kirimEmailSurat = (suratId: number) => {
    emailForm.post(route('e-booking.admin.bookings.surat.email', suratId), { preserveScroll: true });
};

const whatsappForm = useForm({});

const kirimWhatsappSurat = (suratId: number) => {
    whatsappForm.post(route('e-booking.admin.bookings.surat.whatsapp', suratId), { preserveScroll: true });
};

const onLanjutSurat = (event: Event) => {
    lanjutForm.surat_balasan = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const submitMeeting = () => {
    if (!meetingForm.surat_meeting) {
        return;
    }

    meetingForm.post(route('e-booking.admin.bookings.meeting', props.booking.id), { preserveScroll: true });
};

const onMeetingSurat = (event: Event) => {
    meetingForm.surat_meeting = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const onRejectSurat = (event: Event) => {
    rejectForm.surat_balasan = (event.target as HTMLInputElement).files?.[0] ?? null;
};

const penyewaNama = computed(() => props.booking.penyewa?.nama || props.booking.user?.name || '-');

const whatsappUrl = computed(() => {
    const digits = (props.booking.penyewa?.no_hp ?? '').replace(/\D/g, '');
    if (!digits) return null;

    return `https://wa.me/${digits.startsWith('0') ? `62${digits.slice(1)}` : digits}`;
});

const kategoriLabel = computed(() => (props.booking.kategori_tarif === 'pemerintah' ? 'Instansi pemerintah' : 'Umum / non pemerintah'));

const copied = ref(false);
let copyTimer: ReturnType<typeof setTimeout> | null = null;

const copyNomor = async () => {
    try {
        await navigator.clipboard.writeText(props.booking.nomor);
        copied.value = true;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => (copied.value = false), 2000);
    } catch {
        copied.value = false;
    }
};

onUnmounted(() => {
    if (copyTimer) clearTimeout(copyTimer);
});
</script>

<template>
    <SeoHead :title="`Admin ${booking.nomor}`" />

    <AdminLayout active="bookings">
        <Link
            :href="route('e-booking.admin.bookings.index')"
            class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-md text-sm transition-colors focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
        >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3" aria-hidden="true" />
            Pengajuan
        </Link>

        <header class="sb-card relative isolate mt-4 mb-6 overflow-hidden">
            <div class="sb-mow absolute inset-0 -z-20" aria-hidden="true"></div>
            <SbPitchLines variant="half" class="absolute inset-y-0 right-0 -z-10 h-full w-2/3 text-(--wp-accent) opacity-[0.12]" />

            <div class="flex flex-wrap items-start justify-between gap-6 p-5 sm:p-7">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-(--wp-accent-soft) px-2.5 py-1 font-mono text-sm font-semibold text-(--wp-accent-strong) tabular-nums transition hover:brightness-95 focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                            :aria-label="`Salin nomor pengajuan ${booking.nomor}`"
                            @click="copyNomor"
                        >
                            {{ booking.nomor }}
                            <FontAwesomeIcon :icon="['fas', copied ? 'check' : 'copy']" class="size-3 opacity-70" aria-hidden="true" />
                        </button>
                        <span class="sr-only" aria-live="polite">{{ copied ? 'Nomor pengajuan disalin' : '' }}</span>
                        <SbStatusBadge :status="booking.status" audience="admin" />
                        <span v-if="booking.priority_flag" class="sb-badge sb-tone-neutral">Prioritas {{ booking.priority_flag }}</span>
                        <span v-if="booking.jenis_sewa" class="sb-badge" :class="isReguler ? 'sb-tone-info' : 'sb-tone-meeting'">
                            {{ isReguler ? 'Sewa per jam · langsung booking' : 'Sewa per hari · surat + meeting' }}
                        </span>
                    </div>
                    <h1 class="text-foreground mt-3 text-2xl font-bold tracking-tight text-balance sm:text-3xl">
                        {{ booking.venue?.name ?? booking.nomor }}
                    </h1>
                    <p v-if="booking.areas?.length" class="text-muted-foreground mt-1.5 flex items-start gap-2 text-sm">
                        <FontAwesomeIcon :icon="['fas', 'location-dot']" class="mt-1 size-3 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                        {{ booking.areas.map((a) => a.name).join(', ') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Total</p>
                    <p class="text-foreground mt-0.5 text-2xl font-bold tracking-tight tabular-nums sm:text-3xl">
                        {{ formatRupiah(booking.grand_total) }}
                    </p>
                </div>
            </div>

            <dl
                class="grid border-t border-dashed border-(--wp-hairline) text-sm sm:grid-cols-2 xl:grid-cols-[minmax(0,1.3fr)_minmax(0,1.2fr)_minmax(0,1fr)]"
            >
                <div class="flex items-start gap-3 p-5 sm:px-7">
                    <span class="wp-icon size-9 shrink-0 text-sm font-bold" aria-hidden="true">{{ penyewaNama.charAt(0).toUpperCase() }}</span>
                    <div class="min-w-0 flex-1">
                        <dt class="text-muted-foreground text-xs">Penyewa</dt>
                        <dd class="text-foreground mt-0.5 truncate font-semibold">{{ penyewaNama }}</dd>
                        <dd v-if="booking.penyewa?.instansi" class="text-muted-foreground truncate text-xs">{{ booking.penyewa.instansi }}</dd>
                        <dd class="mt-2 flex flex-wrap gap-1.5">
                            <a
                                v-if="whatsappUrl"
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener"
                                class="wp-btn wp-btn-quiet bg-card px-2.5 py-1 text-xs tabular-nums"
                                :aria-label="`Chat WhatsApp ${booking.penyewa?.no_hp}`"
                            >
                                <FontAwesomeIcon :icon="['fas', 'comment-dots']" class="size-3" aria-hidden="true" />
                                {{ booking.penyewa?.no_hp }}
                            </a>
                            <a
                                v-if="booking.user?.email"
                                :href="`mailto:${booking.user.email}`"
                                class="wp-btn wp-btn-quiet bg-card max-w-full px-2.5 py-1 text-xs"
                                :aria-label="`Kirim email ke ${booking.user.email}`"
                            >
                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="size-3" aria-hidden="true" />
                                <span class="truncate">{{ booking.user.email }}</span>
                            </a>
                        </dd>
                    </div>
                </div>
                <div class="border-t border-(--wp-hairline) p-5 sm:border-t-0 sm:border-l sm:px-7">
                    <dt class="text-muted-foreground text-xs">Jadwal</dt>
                    <dd class="text-foreground mt-0.5 font-semibold tabular-nums">{{ booking.starts_at || '-' }}</dd>
                    <dd class="text-muted-foreground text-xs tabular-nums">sampai {{ booking.ends_at || '-' }}</dd>
                </div>
                <div class="border-t border-(--wp-hairline) p-5 sm:col-span-2 sm:px-7 xl:col-span-1 xl:border-t-0 xl:border-l">
                    <dt class="text-muted-foreground text-xs">Jenis pemohon</dt>
                    <dd class="text-foreground mt-0.5 font-semibold">{{ kategoriLabel }}</dd>
                    <dd v-if="booking.tujuan" class="text-muted-foreground line-clamp-2 text-xs">{{ booking.tujuan }}</dd>
                </div>
            </dl>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_23rem]">
            <div class="min-w-0 space-y-6">
                <section
                    v-if="conflictHeadline && conflict?.conflicts?.length"
                    aria-labelledby="conflict-heading"
                    class="sb-callout flex-col p-5"
                    :class="conflictBoxClass"
                >
                    <div class="flex items-start gap-3">
                        <FontAwesomeIcon :icon="['fas', conflictHeadline.icon]" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                        <div class="min-w-0">
                            <h2 id="conflict-heading" class="font-semibold">{{ conflictHeadline.title }}</h2>
                            <p class="text-foreground mt-1">{{ conflictHeadline.detail }}</p>
                            <p v-if="conflict.tentative_context" class="text-muted-foreground mt-2 text-xs">
                                Area ini bersifat tentatif (jadwal bisa berubah).
                            </p>
                            <p v-if="conflict.needs_clarification && conflict.kontak_klarifikasi" class="text-muted-foreground mt-1 text-xs">
                                Kontak klarifikasi: {{ conflict.kontak_klarifikasi }}
                            </p>
                        </div>
                    </div>

                    <ul class="bg-card divide-y divide-(--wp-hairline) overflow-hidden rounded-xl ring-1 ring-(--wp-hairline)">
                        <li
                            v-for="item in conflict.conflicts"
                            :key="item.id"
                            class="grid gap-3 p-3.5 transition-colors hover:bg-(--wp-accent-soft)/40 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                        >
                            <div class="min-w-0">
                                <Link
                                    :href="route('e-booking.admin.bookings.show', item.id)"
                                    class="wp-link-arrow text-foreground inline-flex items-center gap-1.5 rounded-sm font-mono font-semibold tabular-nums hover:text-(--wp-accent) focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                >
                                    {{ item.nomor }}
                                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                                    <span class="sr-only">Buka pengajuan ini</span>
                                </Link>
                                <p class="text-muted-foreground mt-0.5 text-xs tabular-nums">
                                    {{ item.starts_at || '-' }} — {{ item.ends_at || '-' }}
                                </p>
                                <p class="text-muted-foreground mt-1 text-xs">
                                    {{ statusLabel(item.status) }}
                                    <template v-if="item.priority_rule"> · {{ item.priority_rule.name }}</template>
                                    <template v-if="item.area_tentative"> · area tentatif</template>
                                </p>
                            </div>
                            <span class="sb-badge justify-self-start sm:justify-self-end" :class="relationTone(item.relation)">
                                {{ relationLabel(item.relation) }}
                            </span>
                        </li>
                    </ul>
                </section>

                <div v-else-if="conflict && (!conflict.conflicts || conflict.conflicts.length === 0)" class="sb-callout sb-tone-success">
                    <FontAwesomeIcon :icon="['fas', 'circle-check']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                    <div>
                        <p class="font-semibold">Tidak ada benturan jadwal</p>
                        <p class="text-foreground mt-0.5">Tidak ditemukan pengajuan lain yang bentrok di rentang waktu ini.</p>
                    </div>
                </div>

                <section aria-labelledby="detail-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="detail-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                        <FontAwesomeIcon :icon="['fas', 'receipt']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        Detail pengajuan
                    </h2>

                    <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground text-xs">Tujuan kegiatan</dt>
                            <dd class="text-foreground mt-0.5 leading-relaxed">{{ booking.tujuan || '-' }}</dd>
                        </div>
                        <div v-if="booking.keterangan" class="sm:col-span-2">
                            <dt class="text-muted-foreground text-xs">Keterangan dari penyewa</dt>
                            <dd class="text-foreground mt-0.5 leading-relaxed whitespace-pre-line">{{ booking.keterangan }}</dd>
                        </div>
                        <div v-if="booking.admin_notes" class="sm:col-span-2">
                            <dt class="text-muted-foreground text-xs">Catatan pengelola</dt>
                            <dd class="bg-muted/60 text-foreground mt-1 rounded-lg px-3 py-2 leading-relaxed">{{ booking.admin_notes }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 border-t border-dashed border-(--wp-hairline) pt-5">
                        <h3 class="text-muted-foreground text-xs font-semibold tracking-wide uppercase">Rincian biaya</h3>
                        <ul v-if="booking.items.length" class="mt-3 space-y-2.5 text-sm">
                            <li v-for="(item, i) in booking.items" :key="i" class="flex items-baseline gap-3">
                                <span class="text-foreground min-w-0">
                                    {{ item.uraian }}
                                    <span class="text-muted-foreground text-xs tabular-nums">({{ item.qty }} × {{ item.satuan }})</span>
                                </span>
                                <span class="mb-1 flex-1 self-end border-b border-dotted border-(--wp-hairline)" aria-hidden="true"></span>
                                <span class="text-foreground tabular-nums">{{ formatRupiah(item.line_total) }}</span>
                            </li>
                        </ul>
                        <dl class="mt-4 space-y-1.5 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Biaya dasar</dt>
                                <dd class="tabular-nums">{{ formatRupiah(booking.subtotal) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-muted-foreground">Tambahan layanan</dt>
                                <dd class="tabular-nums">{{ formatRupiah(booking.addon_total) }}</dd>
                            </div>
                            <div class="text-foreground flex justify-between gap-4 border-t border-(--wp-hairline) pt-2.5 text-base font-bold">
                                <dt>Total</dt>
                                <dd class="tabular-nums">{{ formatRupiah(booking.grand_total) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section v-if="!isReguler" aria-labelledby="surat-permohonan-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="surat-permohonan-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                        <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        Surat permohonan penyewa
                    </h2>
                    <div
                        v-if="booking.surat_permohonan_url"
                        class="mt-4 flex flex-col gap-3 rounded-2xl p-4 text-sm ring-1 ring-(--wp-hairline) sm:flex-row sm:items-center"
                    >
                        <span class="wp-icon size-10 shrink-0">
                            <FontAwesomeIcon :icon="['fas', 'file-pdf']" class="size-4" aria-hidden="true" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-foreground font-semibold">{{ booking.surat_permohonan_name ?? 'Surat permohonan' }}</p>
                            <p v-if="booking.surat_permohonan_submitted_at" class="text-muted-foreground text-xs tabular-nums">
                                Diunggah {{ booking.surat_permohonan_submitted_at }}
                            </p>
                        </div>
                        <a
                            :href="booking.surat_permohonan_url"
                            target="_blank"
                            rel="noopener"
                            class="wp-btn wp-btn-quiet shrink-0 px-3.5 py-2 text-xs"
                        >
                            <FontAwesomeIcon :icon="['fas', 'download']" class="size-3" aria-hidden="true" />
                            Lihat PDF
                        </a>
                    </div>
                    <p v-else class="text-muted-foreground mt-4 text-sm">
                        Penyewa belum melampirkan surat permohonan (mis. pengajuan via API mobile). Minta lewat klarifikasi jika diperlukan.
                    </p>
                </section>

                <section v-if="booking.surats.length" aria-labelledby="surat-heading" class="sb-card p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="surat-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                            <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                            Surat
                            <span class="sb-badge sb-tone-neutral tabular-nums">{{ booking.surats.length }}</span>
                        </h2>
                    </div>

                    <ul class="mt-4 space-y-3">
                        <li v-for="s in booking.surats" :key="s.id" class="rounded-2xl p-4 text-sm ring-1 ring-(--wp-hairline)">
                            <div class="flex items-start gap-3">
                                <span class="wp-icon size-10 shrink-0">
                                    <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-foreground font-semibold">{{ s.jenis_label }}</p>
                                    <p v-if="s.nomor_surat || s.perihal" class="text-muted-foreground text-xs">
                                        <span v-if="s.nomor_surat" class="font-mono tabular-nums">{{ s.nomor_surat }}</span>
                                        <template v-if="s.nomor_surat && s.perihal"> · </template>
                                        <span v-if="s.perihal">{{ s.perihal }}</span>
                                    </p>
                                    <p v-if="s.meeting_at || s.meeting_place" class="text-foreground mt-1 text-xs">
                                        Meeting: {{ s.meeting_at?.replace('T', ' ') }}{{ s.meeting_place ? ' · ' + s.meeting_place : '' }}
                                    </p>
                                    <p v-if="s.dokumen?.length" class="text-muted-foreground mt-1 text-xs">Dokumen: {{ s.dokumen.join(', ') }}</p>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span class="sb-badge" :class="s.sent_email_at ? 'sb-tone-success' : 'sb-tone-neutral'">
                                            <FontAwesomeIcon :icon="['fas', 'envelope']" class="size-2.5" aria-hidden="true" />
                                            {{ s.sent_email_at ? `Email ${s.sent_email_at}` : 'Email belum dikirim' }}
                                        </span>
                                        <span class="sb-badge" :class="s.sent_whatsapp_at ? 'sb-tone-success' : 'sb-tone-neutral'">
                                            <FontAwesomeIcon :icon="['fas', 'comment-dots']" class="size-2.5" aria-hidden="true" />
                                            {{ s.sent_whatsapp_at ? `WhatsApp ${s.sent_whatsapp_at}` : 'WhatsApp belum dikirim' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2 border-t border-(--wp-hairline) pt-3">
                                <a
                                    :href="route('e-booking.admin.bookings.surat.download', s.id)"
                                    class="wp-btn wp-btn-quiet bg-card px-3 py-1.5 text-xs"
                                >
                                    <FontAwesomeIcon :icon="['fas', 'file-arrow-down']" class="size-3" aria-hidden="true" />
                                    Unduh PDF
                                </a>
                                <button
                                    type="button"
                                    class="wp-btn wp-btn-quiet bg-card px-3 py-1.5 text-xs"
                                    :disabled="emailForm.processing"
                                    @click="kirimEmailSurat(s.id)"
                                >
                                    <FontAwesomeIcon
                                        v-if="emailForm.processing"
                                        :icon="['fas', 'circle-notch']"
                                        class="size-3 animate-spin"
                                        aria-hidden="true"
                                    />
                                    <FontAwesomeIcon v-else :icon="['fas', 'envelope']" class="size-3" aria-hidden="true" />
                                    {{ s.sent_email_at ? 'Kirim ulang email' : 'Kirim email' }}
                                </button>
                                <button
                                    type="button"
                                    class="wp-btn wp-btn-quiet bg-card px-3 py-1.5 text-xs"
                                    :disabled="whatsappForm.processing"
                                    @click="kirimWhatsappSurat(s.id)"
                                >
                                    <FontAwesomeIcon
                                        v-if="whatsappForm.processing"
                                        :icon="['fas', 'circle-notch']"
                                        class="size-3 animate-spin"
                                        aria-hidden="true"
                                    />
                                    <FontAwesomeIcon v-else :icon="['fas', 'comment-dots']" class="size-3" aria-hidden="true" />
                                    {{ s.sent_whatsapp_at ? 'Kirim ulang WhatsApp' : 'WhatsApp' }}
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>

                <section v-if="status_logs.length" aria-labelledby="history-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="history-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                        <FontAwesomeIcon :icon="['fas', 'clock-rotate-left']" class="text-muted-foreground size-3.5" aria-hidden="true" />
                        Riwayat status
                    </h2>
                    <ol class="mt-4 text-sm">
                        <li v-for="(log, i) in [...status_logs].reverse()" :key="i" class="relative flex gap-4 pb-5 last:pb-0">
                            <span
                                v-if="i < status_logs.length - 1"
                                class="absolute top-4 left-1 h-full w-px bg-(--wp-hairline)"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="relative mt-1.5 size-2 shrink-0 rounded-full"
                                :class="i === 0 ? 'bg-(--wp-accent) ring-4 ring-(--wp-accent-soft)' : 'bg-card ring-2 ring-(--wp-hairline)'"
                                aria-hidden="true"
                            ></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                    <p class="text-foreground font-medium">
                                        {{ statusLabel(log.to_status || '') }}
                                        <span v-if="log.from_status" class="text-muted-foreground font-normal">
                                            dari {{ statusLabel(log.from_status) }}</span
                                        >
                                    </p>
                                    <p v-if="log.created_at" class="text-muted-foreground text-xs tabular-nums">{{ log.created_at }}</p>
                                </div>
                                <p v-if="log.note" class="bg-muted/60 text-foreground mt-1.5 rounded-lg px-3 py-2 text-xs leading-relaxed">
                                    {{ log.note }}
                                </p>
                            </div>
                        </li>
                    </ol>
                </section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-32 lg:self-start" aria-label="Tindakan">
                <section v-if="booking.can_review" aria-labelledby="review-heading" class="sb-card overflow-hidden">
                    <div class="border-b border-(--wp-hairline) p-5 pb-4">
                        <h2 id="review-heading" class="text-foreground text-base font-semibold tracking-tight">
                            {{ isWaitingMeeting ? 'Keputusan akhir' : 'Keputusan peninjauan' }}
                        </h2>
                        <p v-if="isWaitingMeeting" class="sb-callout sb-tone-meeting mt-3 p-3 text-xs">
                            <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            <span>Surat balasan sudah dikirim. Setelah meeting selesai, putuskan pemakaian lahan di bawah ini.</span>
                        </p>
                        <p v-else class="sb-callout sb-tone-info mt-3 p-3 text-xs">
                            <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            <span>
                                Kirim surat balasan sebagai persetujuan lanjut ke tahap berikutnya. Undangan meeting (opsional) dan keputusan
                                pemakaian lahan dilakukan setelah ini.
                            </span>
                        </p>
                        <div class="bg-muted mt-3 grid grid-cols-3 gap-1 rounded-xl p-1" role="tablist" aria-label="Pilih keputusan">
                            <button
                                v-for="tab in decisionTabs"
                                :key="tab.value"
                                type="button"
                                role="tab"
                                class="flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                :class="
                                    activeDecision === tab.value
                                        ? tab.value === 'tolak'
                                            ? 'bg-card text-(--sb-danger) shadow-sm'
                                            : tab.value === 'klarifikasi'
                                              ? 'bg-card text-(--sb-warning) shadow-sm'
                                              : 'bg-card text-(--wp-accent-strong) shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                :aria-selected="activeDecision === tab.value ? 'true' : 'false'"
                                @click="activeDecision = tab.value"
                            >
                                <FontAwesomeIcon :icon="['fas', tab.icon]" class="size-3" aria-hidden="true" />
                                {{ tab.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="activeDecision === '' && isWaitingMeeting" class="space-y-4 p-5" role="tabpanel">
                        <div>
                            <p class="sb-label">Aturan prioritas (opsional)</p>
                            <SimpleSelect
                                v-model="priorityRuleSelect"
                                :options="priorityRuleOptions"
                                placeholder="Pakai aturan bawaan"
                                trigger-class="h-10 w-full rounded-xl border-(--wp-hairline) bg-background px-3 text-sm shadow-none"
                            />
                        </div>
                        <div>
                            <label class="sb-label" for="approve-admin-notes">Catatan untuk penyewa</label>
                            <textarea
                                id="approve-admin-notes"
                                v-model="approveForm.admin_notes"
                                rows="2"
                                placeholder="Tampil di halaman pesanan penyewa"
                                class="sb-input"
                            />
                        </div>

                        <div v-if="adaBenturan" class="sb-callout sb-tone-warning p-3 text-xs">
                            <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            <span
                                >Masih ada benturan jadwal/prioritas. Setujui memakai aturan bawaan, pilih aturan lain, atau paksa setujui bila sudah
                                dikoordinasikan.</span
                            >
                        </div>

                        <div class="space-y-2">
                            <button
                                type="button"
                                class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm"
                                :disabled="approveForm.processing || rejectForm.processing || klarifikasiForm.processing"
                                @click="submitApprove(false)"
                            >
                                <FontAwesomeIcon
                                    v-if="approveForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'check']" class="size-4" aria-hidden="true" />
                                Setujui pemakaian lahan
                            </button>
                            <button
                                v-if="adaBenturan"
                                type="button"
                                class="wp-btn wp-btn-quiet w-full justify-center px-4 py-2 text-xs text-(--sb-warning)"
                                :disabled="approveForm.processing"
                                @click="submitApprove(true)"
                            >
                                <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="size-3" aria-hidden="true" />
                                Paksa setujui (abaikan benturan)
                            </button>
                            <p class="text-muted-foreground text-center text-xs">Setelah disetujui, penyewa menerima petunjuk pembayaran.</p>
                        </div>
                    </div>

                    <div v-else-if="activeDecision === ''" class="space-y-4 p-5" role="tabpanel">
                        <div>
                            <label class="sb-label" for="lanjut-admin-notes">Catatan pada surat (opsional)</label>
                            <textarea
                                id="lanjut-admin-notes"
                                v-model="lanjutForm.admin_notes"
                                rows="2"
                                placeholder="Tampil di halaman pesanan penyewa"
                                class="sb-input"
                            />
                        </div>

                        <fieldset v-if="butuhSuratBalasan" class="space-y-3 rounded-xl border border-dashed border-(--wp-hairline) p-3.5">
                            <legend class="px-1 text-xs font-semibold tracking-wide text-(--wp-accent-strong) uppercase">Surat balasan</legend>
                            <p class="text-muted-foreground text-xs leading-relaxed">
                                Wajib. Lampirkan surat balasan berformat <b>PDF</b> (persetujuan lanjut ke tahap berikutnya). Surat otomatis dikirim
                                ke email penyewa.
                            </p>
                            <input
                                type="file"
                                accept="application/pdf,.pdf"
                                class="sb-input"
                                :aria-invalid="lanjutForm.errors.surat_balasan ? 'true' : undefined"
                                :aria-describedby="lanjutForm.errors.surat_balasan ? 'lanjut-surat-error' : undefined"
                                @change="onLanjutSurat"
                            />
                            <p v-if="lanjutForm.errors.surat_balasan" id="lanjut-surat-error" class="sb-error">
                                {{ lanjutForm.errors.surat_balasan }}
                            </p>
                        </fieldset>

                        <div class="space-y-2">
                            <button
                                type="button"
                                class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm"
                                :disabled="
                                    lanjutForm.processing ||
                                    rejectForm.processing ||
                                    klarifikasiForm.processing ||
                                    (butuhSuratBalasan && !lanjutForm.surat_balasan)
                                "
                                @click="submitLanjut"
                            >
                                <FontAwesomeIcon
                                    v-if="lanjutForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'paper-plane']" class="size-4" aria-hidden="true" />
                                Kirim surat balasan
                            </button>
                            <p class="text-muted-foreground text-center text-xs">
                                Status pengajuan berubah menjadi "Menunggu keputusan akhir". Undangan meeting dapat dikirim menyusul.
                            </p>
                        </div>
                    </div>

                    <div v-else-if="activeDecision === 'klarifikasi'" class="space-y-3 p-5" role="tabpanel">
                        <p class="text-muted-foreground text-xs leading-relaxed">Penyewa akan melihat alasan ini dan diminta melengkapi informasi.</p>
                        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Alasan cepat klarifikasi">
                            <button
                                v-for="chip in klarifikasiChips"
                                :key="chip"
                                type="button"
                                class="sb-chip bg-card px-2.5 py-1 text-xs"
                                :aria-pressed="klarifikasiForm.reason === chip ? 'true' : 'false'"
                                @click="klarifikasiForm.reason = chip"
                            >
                                {{ chip }}
                            </button>
                        </div>
                        <label class="sr-only" for="klarifikasi-reason">Alasan klarifikasi</label>
                        <textarea
                            id="klarifikasi-reason"
                            v-model="klarifikasiForm.reason"
                            rows="3"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="sb-input"
                            :aria-invalid="klarifikasiForm.errors.reason ? 'true' : undefined"
                        />
                        <p v-if="klarifikasiForm.errors.reason" class="sb-error">{{ klarifikasiForm.errors.reason }}</p>
                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet w-full justify-center px-4 py-2.5 text-sm text-(--sb-warning)"
                            :disabled="klarifikasiForm.processing || klarifikasiForm.reason.trim() === ''"
                            @click="submitKlarifikasi"
                        >
                            <FontAwesomeIcon
                                v-if="klarifikasiForm.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            <FontAwesomeIcon v-else :icon="['fas', 'comment-dots']" class="size-4" aria-hidden="true" />
                            Kirim tanda klarifikasi
                        </button>
                    </div>

                    <div v-else class="space-y-3 p-5" role="tabpanel">
                        <p class="text-muted-foreground text-xs leading-relaxed">Tanggal langsung tersedia kembali setelah pengajuan ditolak.</p>
                        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Alasan cepat penolakan">
                            <button
                                v-for="chip in rejectChips"
                                :key="chip"
                                type="button"
                                class="sb-chip bg-card px-2.5 py-1 text-xs"
                                :aria-pressed="rejectForm.reason === chip ? 'true' : 'false'"
                                @click="rejectForm.reason = chip"
                            >
                                {{ chip }}
                            </button>
                        </div>
                        <label class="sr-only" for="reject-reason">Alasan penolakan</label>
                        <textarea
                            id="reject-reason"
                            v-model="rejectForm.reason"
                            rows="3"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="sb-input"
                            :aria-invalid="rejectForm.errors.reason ? 'true' : undefined"
                        />
                        <p v-if="rejectForm.errors.reason" class="sb-error">{{ rejectForm.errors.reason }}</p>
                        <fieldset v-if="butuhSuratPenolakan" class="space-y-3 rounded-xl border border-dashed border-(--wp-hairline) p-3.5">
                            <legend class="px-1 text-xs font-semibold tracking-wide text-(--sb-danger) uppercase">Surat alasan penolakan</legend>
                            <p class="text-muted-foreground text-xs leading-relaxed">
                                Wajib. Lampirkan surat penolakan berformat <b>PDF</b>. Surat otomatis dikirim ke email penyewa setelah ditolak.
                            </p>
                            <input
                                type="file"
                                accept="application/pdf,.pdf"
                                class="sb-input"
                                :aria-invalid="rejectForm.errors.surat_balasan ? 'true' : undefined"
                                :aria-describedby="rejectForm.errors.surat_balasan ? 'reject-surat-error' : undefined"
                                @change="onRejectSurat"
                            />
                            <p v-if="rejectForm.errors.surat_balasan" id="reject-surat-error" class="sb-error">
                                {{ rejectForm.errors.surat_balasan }}
                            </p>
                        </fieldset>
                        <button
                            type="button"
                            class="wp-btn wp-btn-danger w-full justify-center px-4 py-2.5 text-sm"
                            :disabled="rejectForm.processing || rejectForm.reason.trim() === '' || (butuhSuratPenolakan && !rejectForm.surat_balasan)"
                            @click="submitReject"
                        >
                            <FontAwesomeIcon
                                v-if="rejectForm.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            <FontAwesomeIcon v-else :icon="['fas', 'circle-xmark']" class="size-4" aria-hidden="true" />
                            Tolak pengajuan
                        </button>
                    </div>
                </section>

                <section v-if="booking.can_send_meeting" aria-labelledby="meeting-heading" class="sb-card space-y-4 p-5 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 id="meeting-heading" class="text-foreground text-base font-semibold tracking-tight">Undangan meeting</h2>
                            <p class="text-muted-foreground mt-1 text-xs">
                                Opsional. Unggah PDF undangan meeting lalu kirim ke penyewa (email + WhatsApp).
                            </p>
                        </div>
                        <span class="sb-badge" :class="hasMeetingInvitation ? 'sb-tone-success' : 'sb-tone-neutral'">
                            {{ hasMeetingInvitation ? 'Terkirim' : 'Belum' }}
                        </span>
                    </div>

                    <fieldset class="space-y-3 rounded-xl border border-dashed border-(--wp-hairline) p-3.5">
                        <legend class="px-1 text-xs font-semibold tracking-wide text-(--wp-accent-strong) uppercase">
                            {{ hasMeetingInvitation ? 'Unggah undangan baru' : 'Undangan meeting' }}
                        </legend>
                        <div>
                            <label class="sb-label" for="meeting-surat">Berkas undangan (PDF)</label>
                            <input
                                id="meeting-surat"
                                type="file"
                                accept="application/pdf,.pdf"
                                class="sb-input"
                                :aria-invalid="meetingForm.errors.surat_meeting ? 'true' : undefined"
                                :aria-describedby="meetingForm.errors.surat_meeting ? 'meeting-surat-error' : undefined"
                                @change="onMeetingSurat"
                            />
                            <p v-if="meetingForm.errors.surat_meeting" id="meeting-surat-error" class="sb-error">
                                {{ meetingForm.errors.surat_meeting }}
                            </p>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="sb-label" for="meeting-at">Waktu (opsional)</label>
                                <input id="meeting-at" v-model="meetingForm.meeting_at" type="datetime-local" class="sb-input" />
                                <p v-if="meetingForm.errors.meeting_at" class="sb-error">{{ meetingForm.errors.meeting_at }}</p>
                            </div>
                            <div>
                                <label class="sb-label" for="meeting-place">Tempat (opsional)</label>
                                <input
                                    id="meeting-place"
                                    v-model="meetingForm.meeting_place"
                                    type="text"
                                    class="sb-input"
                                    placeholder="Contoh: Ruang Rapat UPT"
                                />
                                <p v-if="meetingForm.errors.meeting_place" class="sb-error">{{ meetingForm.errors.meeting_place }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="sb-label" for="meeting-notes">Catatan (opsional)</label>
                            <textarea
                                id="meeting-notes"
                                v-model="meetingForm.admin_notes"
                                rows="2"
                                class="sb-input"
                                placeholder="Tampil di halaman pesanan penyewa"
                            />
                        </div>
                    </fieldset>

                    <button
                        type="button"
                        class="wp-btn wp-btn-primary w-full justify-center px-4 py-2.5 text-sm"
                        :disabled="meetingForm.processing || !meetingForm.surat_meeting"
                        @click="submitMeeting"
                    >
                        <FontAwesomeIcon
                            v-if="meetingForm.processing"
                            :icon="['fas', 'circle-notch']"
                            class="size-4 animate-spin"
                            aria-hidden="true"
                        />
                        <FontAwesomeIcon v-else :icon="['fas', 'paper-plane']" class="size-4" aria-hidden="true" />
                        {{ hasMeetingInvitation ? 'Unggah & kirim undangan baru' : 'Kirim undangan meeting' }}
                    </button>
                </section>

                <section aria-labelledby="payment-heading" class="sb-card space-y-4 p-5 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <h2 id="payment-heading" class="text-foreground text-base font-semibold tracking-tight">Pembayaran</h2>
                        <span v-if="payment" class="sb-badge" :class="payment.status === 'verified' ? 'sb-tone-success' : 'sb-tone-neutral'">{{
                            paymentStatusLabel(payment.status)
                        }}</span>
                    </div>
                    <template v-if="payment">
                        <div class="bg-muted/60 rounded-2xl p-4">
                            <p class="text-muted-foreground text-xs">Jumlah tagihan</p>
                            <p class="text-foreground mt-0.5 text-2xl font-bold tracking-tight tabular-nums">{{ formatRupiah(payment.amount) }}</p>
                            <p v-if="payment.bank" class="text-muted-foreground mt-2 text-xs">
                                {{ payment.bank }} · <span class="font-mono tabular-nums">{{ payment.rekening }}</span>
                                <template v-if="payment.atas_nama"> · {{ payment.atas_nama }}</template>
                            </p>
                            <p v-if="payment.expires_at" class="text-muted-foreground mt-1 text-xs tabular-nums">
                                Batas bayar {{ payment.expires_at }}
                            </p>
                        </div>

                        <a
                            v-if="payment.bukti_url"
                            :href="payment.bukti_url"
                            target="_blank"
                            rel="noopener"
                            class="group block overflow-hidden rounded-xl ring-1 ring-(--wp-hairline) transition hover:ring-(--wp-accent)/50 focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                        >
                            <img
                                v-if="isImageUrl(payment.bukti_url)"
                                :src="payment.bukti_url"
                                alt="Bukti pembayaran"
                                loading="lazy"
                                class="bg-muted max-h-64 w-full object-contain"
                            />
                            <span
                                class="wp-link-arrow flex items-center justify-between gap-1.5 px-3 py-2.5 font-medium text-(--wp-accent) group-hover:text-(--wp-accent-strong)"
                            >
                                Buka bukti pembayaran
                                <FontAwesomeIcon :icon="['fas', 'arrow-up-right-from-square']" class="size-3" aria-hidden="true" />
                            </span>
                        </a>
                        <p v-else class="text-muted-foreground rounded-xl border border-dashed border-(--wp-hairline) p-3 text-center text-xs">
                            Penyewa belum mengirim bukti pembayaran.
                        </p>

                        <p v-if="payment.notes" class="bg-muted/60 rounded-lg px-3 py-2 text-xs leading-relaxed">{{ payment.notes }}</p>

                        <div v-if="booking.can_verify_payment" class="space-y-3 border-t border-(--wp-hairline) pt-4">
                            <div>
                                <label class="sb-label" for="verify-notes">Catatan verifikasi</label>
                                <textarea
                                    id="verify-notes"
                                    v-model="verifyForm.notes"
                                    rows="2"
                                    placeholder="Catatan verifikasi (opsional)"
                                    class="sb-input"
                                />
                            </div>
                            <button
                                type="button"
                                class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm"
                                :disabled="verifyForm.processing"
                                @click="submitVerify"
                            >
                                <FontAwesomeIcon
                                    v-if="verifyForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'check']" class="size-4" aria-hidden="true" />
                                Verifikasi bukti pembayaran
                            </button>

                            <button
                                type="button"
                                class="flex w-full items-center justify-center gap-1.5 rounded-lg py-1.5 text-xs font-medium text-(--sb-danger) transition hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                :aria-expanded="rejectPayOpen ? 'true' : 'false'"
                                aria-controls="reject-pay-panel"
                                @click="rejectPayOpen = !rejectPayOpen"
                            >
                                <FontAwesomeIcon
                                    :icon="['fas', 'chevron-down']"
                                    class="size-2.5 transition-transform"
                                    :class="rejectPayOpen ? 'rotate-180' : ''"
                                    aria-hidden="true"
                                />
                                Bukti tidak sesuai?
                            </button>
                            <div
                                v-show="rejectPayOpen || rejectPayForm.errors.reason"
                                id="reject-pay-panel"
                                class="space-y-3 rounded-xl bg-(--sb-danger)/5 p-3"
                            >
                                <div>
                                    <label class="sb-label" for="reject-pay-reason">Alasan tolak bukti</label>
                                    <textarea
                                        id="reject-pay-reason"
                                        v-model="rejectPayForm.reason"
                                        rows="2"
                                        placeholder="Contoh: nominal tidak sesuai tagihan"
                                        class="sb-input"
                                        :aria-invalid="rejectPayForm.errors.reason ? 'true' : undefined"
                                    />
                                    <p v-if="rejectPayForm.errors.reason" class="sb-error">{{ rejectPayForm.errors.reason }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="wp-btn wp-btn-danger w-full justify-center px-4 py-2 text-sm"
                                    :disabled="rejectPayForm.processing"
                                    @click="submitRejectPay"
                                >
                                    <FontAwesomeIcon
                                        v-if="rejectPayForm.processing"
                                        :icon="['fas', 'circle-notch']"
                                        class="size-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                    Tolak bukti
                                </button>
                            </div>
                        </div>
                    </template>
                    <p v-else class="text-muted-foreground">Belum ada data pembayaran. Muncul setelah pengajuan disetujui.</p>
                </section>
            </aside>
        </div>
    </AdminLayout>
</template>
