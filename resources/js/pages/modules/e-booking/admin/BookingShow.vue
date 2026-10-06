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
    faPlus,
    faReceipt,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';

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
    faPlus,
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
        can_review: boolean;
        can_verify_payment: boolean;
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
    document_types: Array<{ id: number; name: string; is_required: boolean }>;
    surat_kop: Record<string, string>;
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
    meeting_at: '',
    meeting_place: '',
});

const rejectForm = useForm({ reason: '' });
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

const decisionTabs = [
    { value: '', label: 'Setujui', icon: 'check' },
    { value: 'klarifikasi', label: 'Klarifikasi', icon: 'comment-dots' },
    { value: 'tolak', label: 'Tolak', icon: 'circle-xmark' },
] as const;

const rejectPayOpen = ref(false);

const isReguler = computed(() => props.booking.jenis_sewa === 'reguler');
const showMeetingFields = ref(!isReguler.value);

const adaBenturan = computed(() => Boolean(props.conflict?.conflicts?.length));

const submitApprove = async (force = false) => {
    if (force) {
        const ok = await confirm({
            title: 'Paksa lanjut pengajuan ini?',
            description:
                'Masih ada benturan jadwal/prioritas. Dengan memaksa lanjut, pengajuan ini disetujui dan benturan diselesaikan secara manual.',
            confirmText: 'Ya, paksa lanjut',
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

const jenisOptions = [
    { value: 'undangan_meeting', label: 'Undangan Meeting' },
    { value: 'balasan_persetujuan', label: 'Balasan Persetujuan' },
    { value: 'balasan_penolakan', label: 'Balasan Penolakan' },
];

const suratTemplate = (jenis: string) => {
    const venue = props.booking.venue?.name ?? 'venue';
    const jadwal = `${props.booking.starts_at ?? '-'} s.d. ${props.booking.ends_at ?? '-'}`;

    if (jenis === 'balasan_persetujuan') {
        return {
            perihal: 'Persetujuan Pengajuan Sewa Fasilitas',
            isi: `Menindaklanjuti pengajuan sewa ${venue} (${jadwal}), dengan ini kami sampaikan bahwa pengajuan Anda telah disetujui.\n\nSilakan melakukan pembayaran sesuai petunjuk pada halaman detail pesanan Anda sebelum batas waktu berakhir.`,
        };
    }
    if (jenis === 'balasan_penolakan') {
        return {
            perihal: 'Balasan Pengajuan Sewa Fasilitas',
            isi: `Menindaklanjuti pengajuan sewa ${venue} (${jadwal}), dengan ini kami sampaikan bahwa pengajuan Anda belum dapat kami setujui.\n\nApabila terdapat keperluan lain, silakan ajukan kembali melalui sistem E-Booking.`,
        };
    }

    return {
        perihal: 'Undangan Meeting Penyewaan Fasilitas',
        isi: `Menindaklanjuti pengajuan sewa ${venue} (${jadwal}), kami mengundang Anda untuk hadir pada meeting pembahasan pengajuan sesuai waktu dan tempat tercantum di bawah.\n\nMohon membawa dokumen yang tercantum pada surat ini serta siap dengan informasi kegiatan yang akan dilaksanakan.`,
    };
};

const defaultPenandatangan = () => ({
    nama: props.surat_kop.penandatangan_nama ?? '',
    jabatan: props.surat_kop.penandatangan_jabatan ?? '',
});

const suratForm = useForm({
    jenis: 'undangan_meeting' as string,
    nomor_surat: '',
    perihal: '',
    isi: '',
    meeting_at: '',
    meeting_place: '',
    dokumen: props.document_types.filter((d) => d.is_required).map((d) => d.id) as number[],
    penandatangan_nama: defaultPenandatangan().nama,
    penandatangan_jabatan: defaultPenandatangan().jabatan,
});

const jenisSebelumnya = ref('');
watch(
    () => suratForm.jenis,
    (jenis) => {
        if (!jenis || jenis === jenisSebelumnya.value) return;
        jenisSebelumnya.value = jenis;
        if (suratForm.isi.trim() === '') {
            const tpl = suratTemplate(jenis);
            suratForm.perihal = tpl.perihal;
            suratForm.isi = tpl.isi;
        }
        if (jenis !== 'undangan_meeting') {
            suratForm.meeting_at = '';
            suratForm.meeting_place = '';
        }
    },
);

const toggleDokumen = (id: number) => {
    suratForm.dokumen = suratForm.dokumen.includes(id) ? suratForm.dokumen.filter((d) => d !== id) : [...suratForm.dokumen, id];
};

const fillTemplate = () => {
    const tpl = suratTemplate(suratForm.jenis);
    suratForm.perihal = tpl.perihal;
    suratForm.isi = tpl.isi;
};

const namaDokumenTerpilih = computed(() => {
    const map = new Map(props.document_types.map((d) => [d.id, d.name]));

    return suratForm.dokumen.map((id) => map.get(id) ?? '').filter((n) => n !== '');
});

const submitSurat = () => {
    suratForm.post(route('e-booking.admin.bookings.surat.store', props.booking.id), {
        preserveScroll: true,
        onSuccess: () => {
            suratForm.reset();
            suratForm.dokumen = props.document_types.filter((d) => d.is_required).map((d) => d.id);
            suratForm.penandatangan_nama = defaultPenandatangan().nama;
            suratForm.penandatangan_jabatan = defaultPenandatangan().jabatan;
        },
    });
};

const emailForm = useForm({});

const kirimEmailSurat = (suratId: number) => {
    emailForm.post(route('e-booking.admin.bookings.surat.email', suratId), { preserveScroll: true });
};

const whatsappForm = useForm({});

const kirimWhatsappSurat = (suratId: number) => {
    whatsappForm.post(route('e-booking.admin.bookings.surat.whatsapp', suratId), { preserveScroll: true });
};

const showSuratForm = ref(props.booking.surats.length === 0);

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
                            {{ isReguler ? 'Sewa latihan · tanpa meeting' : 'Sewa event · perlu meeting' }}
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

                <section aria-labelledby="surat-heading" class="sb-card p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="surat-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                            <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                            Surat balasan
                            <span v-if="booking.surats.length" class="sb-badge sb-tone-neutral tabular-nums">{{ booking.surats.length }}</span>
                        </h2>
                        <button
                            type="button"
                            class="wp-btn px-3.5 py-2 text-xs"
                            :class="showSuratForm ? 'wp-btn-quiet' : 'wp-btn-primary'"
                            :aria-expanded="showSuratForm ? 'true' : 'false'"
                            aria-controls="surat-form"
                            @click="showSuratForm = !showSuratForm"
                        >
                            <FontAwesomeIcon
                                :icon="['fas', showSuratForm ? 'chevron-down' : 'plus']"
                                class="size-3 transition-transform"
                                :class="showSuratForm ? 'rotate-180' : ''"
                                aria-hidden="true"
                            />
                            {{ showSuratForm ? 'Tutup formulir' : 'Buat surat baru' }}
                        </button>
                    </div>

                    <ul v-if="booking.surats.length" class="mt-4 space-y-3">
                        <li v-for="s in booking.surats" :key="s.id" class="rounded-2xl p-4 text-sm ring-1 ring-(--wp-hairline)">
                            <div class="flex items-start gap-3">
                                <span class="wp-icon size-10 shrink-0">
                                    <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-foreground font-semibold">{{ s.jenis_label }}</p>
                                    <p class="text-muted-foreground text-xs">
                                        <span class="font-mono tabular-nums">{{ s.nomor_surat }}</span> · {{ s.perihal }}
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
                    <p v-else-if="!showSuratForm" class="text-muted-foreground mt-2 text-sm">Belum ada surat untuk pengajuan ini.</p>

                    <div v-show="showSuratForm" id="surat-form" class="mt-6 space-y-5 border-t border-(--wp-hairline) pt-5">
                        <fieldset>
                            <legend class="sb-label">Jenis surat</legend>
                            <div class="grid gap-2 sm:grid-cols-3">
                                <label
                                    v-for="opt in jenisOptions"
                                    :key="opt.value"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm transition focus-within:ring-2 focus-within:ring-(--wp-accent)"
                                    :class="
                                        suratForm.jenis === opt.value
                                            ? 'bg-(--wp-accent-soft) font-semibold text-(--wp-accent-strong) ring-2 ring-(--wp-accent)'
                                            : 'text-foreground hover:bg-muted/60 ring-1 ring-(--wp-hairline)'
                                    "
                                >
                                    <input v-model="suratForm.jenis" type="radio" name="surat-jenis" :value="opt.value" class="sr-only" />
                                    <span
                                        class="grid size-4 shrink-0 place-items-center rounded-full"
                                        :class="suratForm.jenis === opt.value ? 'bg-(--wp-accent)' : 'ring-1 ring-(--wp-hairline)'"
                                        aria-hidden="true"
                                    >
                                        <span v-if="suratForm.jenis === opt.value" class="size-1.5 rounded-full bg-(--wp-accent-contrast)"></span>
                                    </span>
                                    {{ opt.label }}
                                </label>
                            </div>
                            <p v-if="suratForm.errors.jenis" class="sb-error">{{ suratForm.errors.jenis }}</p>
                        </fieldset>

                        <div class="grid gap-4 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                            <div>
                                <label class="sb-label" for="surat-nomor">Nomor surat (manual)</label>
                                <input
                                    id="surat-nomor"
                                    v-model="suratForm.nomor_surat"
                                    type="text"
                                    placeholder="cth: 042/E-BK/IX/2026"
                                    class="sb-input font-mono"
                                    :aria-invalid="suratForm.errors.nomor_surat ? 'true' : undefined"
                                />
                                <p v-if="suratForm.errors.nomor_surat" class="sb-error">{{ suratForm.errors.nomor_surat }}</p>
                            </div>
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <label class="text-foreground text-sm font-medium" for="surat-perihal">Perihal</label>
                                    <button
                                        type="button"
                                        class="rounded-sm text-xs font-medium text-(--wp-accent) hover:text-(--wp-accent-strong) hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                        @click="fillTemplate"
                                    >
                                        Isi otomatis dari template
                                    </button>
                                </div>
                                <input
                                    id="surat-perihal"
                                    v-model="suratForm.perihal"
                                    type="text"
                                    class="sb-input"
                                    :aria-invalid="suratForm.errors.perihal ? 'true' : undefined"
                                />
                                <p v-if="suratForm.errors.perihal" class="sb-error">{{ suratForm.errors.perihal }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="sb-label" for="surat-isi">Isi surat</label>
                            <textarea
                                id="surat-isi"
                                v-model="suratForm.isi"
                                rows="6"
                                class="sb-input leading-relaxed"
                                :aria-invalid="suratForm.errors.isi ? 'true' : undefined"
                            />
                            <p v-if="suratForm.errors.isi" class="sb-error">{{ suratForm.errors.isi }}</p>
                        </div>

                        <div v-if="suratForm.jenis === 'undangan_meeting'" class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="sb-label" for="surat-meeting-at">Waktu meeting</label>
                                <input
                                    id="surat-meeting-at"
                                    v-model="suratForm.meeting_at"
                                    type="datetime-local"
                                    class="sb-input"
                                    :aria-invalid="suratForm.errors.meeting_at ? 'true' : undefined"
                                />
                                <p v-if="suratForm.errors.meeting_at" class="sb-error">{{ suratForm.errors.meeting_at }}</p>
                            </div>
                            <div>
                                <label class="sb-label" for="surat-meeting-place">Tempat meeting</label>
                                <input
                                    id="surat-meeting-place"
                                    v-model="suratForm.meeting_place"
                                    type="text"
                                    placeholder="cth: Ruang rapat UPT"
                                    class="sb-input"
                                    :aria-invalid="suratForm.errors.meeting_place ? 'true' : undefined"
                                />
                                <p v-if="suratForm.errors.meeting_place" class="sb-error">{{ suratForm.errors.meeting_place }}</p>
                            </div>
                        </div>

                        <fieldset v-if="document_types.length">
                            <legend class="sb-label">Dokumen yang harus disiapkan penyewa</legend>
                            <div class="flex flex-wrap gap-2">
                                <label
                                    v-for="d in document_types"
                                    :key="d.id"
                                    class="flex cursor-pointer items-center gap-2 rounded-full px-3 py-1.5 text-sm transition focus-within:ring-2 focus-within:ring-(--wp-accent)"
                                    :class="
                                        suratForm.dokumen.includes(d.id)
                                            ? 'bg-(--wp-accent-soft) text-(--wp-accent-strong) ring-1 ring-(--wp-accent)'
                                            : 'text-foreground hover:bg-muted/60 ring-1 ring-(--wp-hairline)'
                                    "
                                >
                                    <input
                                        type="checkbox"
                                        class="sr-only"
                                        :checked="suratForm.dokumen.includes(d.id)"
                                        @change="toggleDokumen(d.id)"
                                    />
                                    <FontAwesomeIcon
                                        :icon="['fas', suratForm.dokumen.includes(d.id) ? 'check' : 'plus']"
                                        class="size-3"
                                        aria-hidden="true"
                                    />
                                    <span>{{ d.name }}</span>
                                    <span v-if="d.is_required" class="sb-badge sb-tone-warning px-1.5 py-0 text-[0.6875rem]">wajib</span>
                                </label>
                            </div>
                            <p class="sb-hint">Diatur dinamis di menu Jenis Dokumen.</p>
                        </fieldset>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="sb-label" for="surat-ttd-nama">Nama penandatangan</label>
                                <input id="surat-ttd-nama" v-model="suratForm.penandatangan_nama" type="text" class="sb-input" />
                            </div>
                            <div>
                                <label class="sb-label" for="surat-ttd-jabatan">Jabatan</label>
                                <input id="surat-ttd-jabatan" v-model="suratForm.penandatangan_jabatan" type="text" class="sb-input" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-(--wp-hairline) pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-muted-foreground text-xs">
                                <template v-if="namaDokumenTerpilih.length">Akan tercantum di surat: {{ namaDokumenTerpilih.join(', ') }}</template>
                                <template v-else>Belum ada dokumen yang dipilih.</template>
                            </p>
                            <button
                                type="button"
                                class="wp-btn wp-btn-primary shrink-0 justify-center px-5 py-2.5 text-sm"
                                :disabled="suratForm.processing"
                                @click="submitSurat"
                            >
                                <FontAwesomeIcon
                                    v-if="suratForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'file-lines']" class="size-4" aria-hidden="true" />
                                Buat surat (PDF)
                            </button>
                        </div>
                    </div>
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
                        <h2 id="review-heading" class="text-foreground text-base font-semibold tracking-tight">Keputusan peninjauan</h2>
                        <p v-if="booking.status === 'menunggu_meeting'" class="sb-callout sb-tone-meeting mt-3 p-3 text-xs">
                            <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            <span>Undangan meeting sudah terkirim. Putuskan setelah meeting dengan penyewa selesai.</span>
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

                    <div v-if="activeDecision === ''" class="space-y-4 p-5" role="tabpanel">
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

                        <div v-if="!showMeetingFields" class="sb-callout sb-tone-info p-3 text-xs">
                            <FontAwesomeIcon :icon="['fas', 'circle-info']" class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                            <span>
                                Sewa latihan tidak perlu meeting. Setelah disetujui, penyewa langsung menerima petunjuk pembayaran lewat email.
                                <button
                                    type="button"
                                    class="mt-1 block rounded-sm font-semibold underline-offset-2 hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                    @click="showMeetingFields = true"
                                >
                                    Tetap undang meeting
                                </button>
                            </span>
                        </div>

                        <fieldset v-else class="space-y-3 rounded-xl border border-dashed border-(--wp-hairline) p-3.5">
                            <legend class="px-1 text-xs font-semibold tracking-wide text-(--wp-accent-strong) uppercase">Undangan meeting</legend>
                            <p class="text-muted-foreground text-xs leading-relaxed">
                                Opsional. Jika diisi, sistem otomatis membuat dan mengirim undangan meeting lewat <b>WhatsApp</b> dan
                                <b>email</b> saat pengajuan disetujui.
                            </p>
                            <div>
                                <label class="sb-label" for="approve-meeting-at">Waktu meeting</label>
                                <input
                                    id="approve-meeting-at"
                                    v-model="approveForm.meeting_at"
                                    type="datetime-local"
                                    class="sb-input"
                                    :aria-invalid="approveForm.errors.meeting_at ? 'true' : undefined"
                                    :aria-describedby="approveForm.errors.meeting_at ? 'approve-meeting-at-error' : undefined"
                                />
                                <p v-if="approveForm.errors.meeting_at" id="approve-meeting-at-error" class="sb-error">
                                    {{ approveForm.errors.meeting_at }}
                                </p>
                            </div>
                            <div>
                                <label class="sb-label" for="approve-meeting-place">Tempat meeting</label>
                                <input
                                    id="approve-meeting-place"
                                    v-model="approveForm.meeting_place"
                                    type="text"
                                    class="sb-input"
                                    placeholder="Contoh: Ruang Rapat UPT Dispora"
                                    :aria-invalid="approveForm.errors.meeting_place ? 'true' : undefined"
                                    :aria-describedby="approveForm.errors.meeting_place ? 'approve-meeting-place-error' : undefined"
                                />
                                <p v-if="approveForm.errors.meeting_place" id="approve-meeting-place-error" class="sb-error">
                                    {{ approveForm.errors.meeting_place }}
                                </p>
                            </div>
                        </fieldset>

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
                                Setujui pengajuan
                            </button>
                            <button
                                v-if="adaBenturan"
                                type="button"
                                class="wp-btn wp-btn-quiet w-full justify-center px-4 py-2 text-xs text-(--sb-warning)"
                                :disabled="approveForm.processing"
                                @click="submitApprove(true)"
                            >
                                <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="size-3" aria-hidden="true" />
                                Paksa lanjut (abaikan benturan)
                            </button>
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
                        <button
                            type="button"
                            class="wp-btn wp-btn-danger w-full justify-center px-4 py-2.5 text-sm"
                            :disabled="rejectForm.processing || rejectForm.reason.trim() === ''"
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
