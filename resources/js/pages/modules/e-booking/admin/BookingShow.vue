<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
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
    faCircleCheck,
    faCircleInfo,
    faCircleNotch,
    faCircleXmark,
    faClockRotateLeft,
    faCommentDots,
    faEnvelope,
    faFileArrowDown,
    faFileLines,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

library.add(
    faArrowLeft,
    faArrowRight,
    faArrowUpRightFromSquare,
    faCheck,
    faCircleCheck,
    faCircleInfo,
    faCircleNotch,
    faCircleXmark,
    faClockRotateLeft,
    faCommentDots,
    faEnvelope,
    faFileArrowDown,
    faFileLines,
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

const toggleDecision = (target: 'klarifikasi' | 'tolak') => {
    activeDecision.value = activeDecision.value === target ? '' : target;
};

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
</script>

<template>
    <SeoHead :title="`Admin ${booking.nomor}`" />

    <AdminLayout active="bookings">
        <header class="mb-8">
            <Link
                :href="route('e-booking.admin.bookings.index')"
                class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-md text-sm transition-colors focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
            >
                <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3" aria-hidden="true" />
                Pengajuan
            </Link>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <h1 class="text-foreground text-2xl font-bold tracking-tight tabular-nums sm:text-3xl">{{ booking.nomor }}</h1>
                <SbStatusBadge :status="booking.status" audience="admin" />
            </div>
            <p class="text-muted-foreground mt-1.5 text-sm">
                {{ booking.venue?.name }}
                <template v-if="booking.priority_flag"> · prioritas {{ booking.priority_flag }}</template>
            </p>
        </header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-6">
                <section aria-labelledby="detail-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="detail-heading" class="text-foreground text-base font-semibold tracking-tight">Detail pengajuan</h2>
                    <dl class="mt-4 divide-y divide-(--wp-hairline) text-sm">
                        <div class="grid gap-1 py-3 first:pt-0 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-muted-foreground">Penyewa</dt>
                            <dd class="text-foreground">
                                <span class="font-medium">{{ booking.penyewa?.nama || booking.user?.name }}</span>
                                <span class="text-muted-foreground block text-xs">{{ booking.user?.email }}</span>
                                <span v-if="booking.penyewa?.no_hp" class="text-muted-foreground block text-xs tabular-nums">{{
                                    booking.penyewa.no_hp
                                }}</span>
                            </dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-muted-foreground">Tempat</dt>
                            <dd class="text-foreground font-medium">{{ booking.venue?.name }}</dd>
                        </div>
                        <div v-if="booking.areas?.length" class="grid gap-1 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-muted-foreground">Area</dt>
                            <dd class="text-foreground">{{ booking.areas.map((a) => a.name).join(', ') }}</dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-muted-foreground">Jadwal</dt>
                            <dd class="text-foreground tabular-nums">{{ booking.starts_at }} — {{ booking.ends_at }}</dd>
                        </div>
                        <div class="grid gap-1 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-muted-foreground">Tujuan</dt>
                            <dd class="text-foreground">{{ booking.tujuan }}</dd>
                        </div>
                        <div class="grid gap-1 pt-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-foreground font-semibold">Total</dt>
                            <dd class="text-foreground text-base font-semibold tabular-nums">{{ formatRupiah(booking.grand_total) }}</dd>
                        </div>
                    </dl>
                </section>

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
                        <li v-for="item in conflict.conflicts" :key="item.id" class="flex flex-wrap items-start justify-between gap-3 p-3.5">
                            <div class="min-w-0">
                                <Link
                                    :href="route('e-booking.admin.bookings.show', item.id)"
                                    class="wp-link-arrow text-foreground inline-flex items-center gap-1.5 rounded-sm font-semibold tabular-nums hover:text-(--wp-accent) focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                >
                                    {{ item.nomor }}
                                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                                    <span class="sr-only">Buka pengajuan ini</span>
                                </Link>
                                <p class="text-muted-foreground mt-0.5 text-xs tabular-nums">
                                    {{ item.starts_at || '-' }} — {{ item.ends_at || '-' }}
                                </p>
                                <p class="text-muted-foreground mt-1 text-xs">
                                    Status: {{ statusLabel(item.status) }}
                                    <template v-if="item.priority_rule"> · {{ item.priority_rule.name }} </template>
                                </p>
                            </div>
                            <span class="sb-badge" :class="relationTone(item.relation)">
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

                <section v-if="status_logs.length" aria-labelledby="history-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="history-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                        <FontAwesomeIcon :icon="['fas', 'clock-rotate-left']" class="text-muted-foreground size-3.5" aria-hidden="true" />
                        Riwayat status
                    </h2>
                    <ol class="mt-4 space-y-3 border-l border-(--wp-hairline) pl-4 text-sm">
                        <li v-for="(log, i) in status_logs" :key="i" class="relative">
                            <span
                                class="bg-card absolute top-1.5 -left-[1.3rem] size-2 rounded-full ring-2 ring-(--wp-accent)"
                                aria-hidden="true"
                            ></span>
                            <span class="text-foreground font-medium">{{ statusLabel(log.to_status || '') }}</span>
                            <span v-if="log.created_at" class="text-muted-foreground tabular-nums"> · {{ log.created_at }}</span>
                        </li>
                    </ol>
                </section>

                <section aria-labelledby="surat-heading" class="sb-card p-5 sm:p-6">
                    <h2 id="surat-heading" class="text-foreground flex items-center gap-2 text-base font-semibold tracking-tight">
                        <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-3.5 text-(--wp-accent)" aria-hidden="true" />
                        Surat balasan
                    </h2>

                    <ul v-if="booking.surats.length" class="mt-4 space-y-3">
                        <li v-for="s in booking.surats" :key="s.id" class="sb-card-muted space-y-3 p-4 text-sm">
                            <div>
                                <p class="text-foreground font-semibold">{{ s.jenis_label }}</p>
                                <p class="text-muted-foreground text-xs">{{ s.nomor_surat }} · {{ s.perihal }}</p>
                                <p v-if="s.meeting_at || s.meeting_place" class="text-foreground mt-1 text-xs">
                                    Meeting: {{ s.meeting_at?.replace('T', ' ') }}{{ s.meeting_place ? ' · ' + s.meeting_place : '' }}
                                </p>
                                <p v-if="s.dokumen?.length" class="text-muted-foreground mt-1 text-xs">Dokumen: {{ s.dokumen.join(', ') }}</p>
                                <p v-if="s.sent_email_at" class="mt-1 text-xs text-(--wp-accent-strong)">Terkirim via email {{ s.sent_email_at }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
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
                                    <FontAwesomeIcon :icon="['fas', 'envelope']" class="size-3" aria-hidden="true" />
                                    Kirim email
                                </button>
                                <a
                                    :href="route('e-booking.admin.bookings.surat.whatsapp', s.id)"
                                    target="_blank"
                                    rel="noopener"
                                    class="wp-btn wp-btn-quiet bg-card px-3 py-1.5 text-xs"
                                >
                                    <FontAwesomeIcon :icon="['fas', 'arrow-up-right-from-square']" class="size-3" aria-hidden="true" />
                                    WhatsApp
                                </a>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="text-muted-foreground mt-2 text-sm">Belum ada surat. Buat di bawah lalu kirim ke penyewa.</p>

                    <div class="mt-6 space-y-4 border-t border-(--wp-hairline) pt-5">
                        <h3 class="text-foreground text-sm font-semibold">Buat surat baru</h3>

                        <div>
                            <p class="sb-label">Jenis surat</p>
                            <SimpleSelect
                                v-model="suratForm.jenis"
                                :options="jenisOptions"
                                placeholder="Pilih jenis surat"
                                trigger-class="h-10 w-full rounded-xl border-(--wp-hairline) bg-background px-3 text-sm shadow-none"
                            />
                        </div>

                        <div>
                            <label class="sb-label" for="surat-nomor">Nomor surat (manual)</label>
                            <input
                                id="surat-nomor"
                                v-model="suratForm.nomor_surat"
                                type="text"
                                placeholder="cth: 042/E-BK/IX/2026"
                                class="sb-input"
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

                        <div>
                            <label class="sb-label" for="surat-isi">Isi surat</label>
                            <textarea
                                id="surat-isi"
                                v-model="suratForm.isi"
                                rows="5"
                                class="sb-input"
                                :aria-invalid="suratForm.errors.isi ? 'true' : undefined"
                            />
                            <p v-if="suratForm.errors.isi" class="sb-error">{{ suratForm.errors.isi }}</p>
                        </div>

                        <template v-if="suratForm.jenis === 'undangan_meeting'">
                            <div class="grid gap-4 sm:grid-cols-2">
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
                        </template>

                        <fieldset v-if="document_types.length">
                            <legend class="sb-label">Dokumen yang harus disiapkan penyewa</legend>
                            <div class="sb-card-muted space-y-2 p-3">
                                <label
                                    v-for="d in document_types"
                                    :key="d.id"
                                    class="text-foreground flex cursor-pointer items-center gap-2.5 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        class="size-4 accent-(--wp-accent)"
                                        :checked="suratForm.dokumen.includes(d.id)"
                                        @change="toggleDokumen(d.id)"
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

                        <p v-if="namaDokumenTerpilih.length" class="text-muted-foreground text-xs">
                            Akan tercantum di surat: {{ namaDokumenTerpilih.join(', ') }}
                        </p>

                        <button
                            type="button"
                            class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm sm:w-auto"
                            :disabled="suratForm.processing"
                            @click="submitSurat"
                        >
                            <FontAwesomeIcon
                                v-if="suratForm.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            Buat surat (PDF)
                        </button>
                    </div>
                </section>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-32 lg:self-start" aria-label="Tindakan">
                <section v-if="booking.can_review" aria-labelledby="review-heading" class="sb-card space-y-4 p-5">
                    <h2 id="review-heading" class="text-foreground text-base font-semibold tracking-tight">Keputusan peninjauan</h2>

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
                            placeholder="Catatan untuk penyewa (tampil di halaman pesanan mereka)"
                            class="sb-input"
                        />
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

                    <div class="grid grid-cols-2 gap-2 border-t border-(--wp-hairline) pt-4">
                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet justify-center px-3 py-2 text-xs"
                            :class="activeDecision === 'klarifikasi' ? 'sb-tone-warning' : ''"
                            :aria-pressed="activeDecision === 'klarifikasi' ? 'true' : 'false'"
                            @click="toggleDecision('klarifikasi')"
                        >
                            <FontAwesomeIcon :icon="['fas', 'comment-dots']" class="size-3" aria-hidden="true" />
                            Perlu klarifikasi
                        </button>
                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet justify-center px-3 py-2 text-xs"
                            :class="activeDecision === 'tolak' ? 'sb-tone-danger' : 'text-(--sb-danger)'"
                            :aria-pressed="activeDecision === 'tolak' ? 'true' : 'false'"
                            @click="toggleDecision('tolak')"
                        >
                            <FontAwesomeIcon :icon="['fas', 'circle-xmark']" class="size-3" aria-hidden="true" />
                            Tolak
                        </button>
                    </div>

                    <div v-if="activeDecision === 'klarifikasi'" class="sb-card-muted space-y-3 p-3.5">
                        <p class="text-foreground text-sm font-semibold">Tandai perlu klarifikasi</p>
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
                            rows="2"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="sb-input"
                            :aria-invalid="klarifikasiForm.errors.reason ? 'true' : undefined"
                        />
                        <p v-if="klarifikasiForm.errors.reason" class="sb-error">{{ klarifikasiForm.errors.reason }}</p>
                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet bg-card w-full justify-center px-4 py-2 text-sm"
                            :disabled="klarifikasiForm.processing || klarifikasiForm.reason.trim() === ''"
                            @click="submitKlarifikasi"
                        >
                            <FontAwesomeIcon
                                v-if="klarifikasiForm.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            Kirim tanda klarifikasi
                        </button>
                    </div>

                    <div v-if="activeDecision === 'tolak'" class="sb-card-muted space-y-3 p-3.5">
                        <p class="text-foreground text-sm font-semibold">Tolak pengajuan</p>
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
                            rows="2"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="sb-input"
                            :aria-invalid="rejectForm.errors.reason ? 'true' : undefined"
                        />
                        <p v-if="rejectForm.errors.reason" class="sb-error">{{ rejectForm.errors.reason }}</p>
                        <button
                            type="button"
                            class="wp-btn wp-btn-danger w-full justify-center px-4 py-2 text-sm"
                            :disabled="rejectForm.processing || rejectForm.reason.trim() === ''"
                            @click="submitReject"
                        >
                            <FontAwesomeIcon
                                v-if="rejectForm.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-4 animate-spin"
                                aria-hidden="true"
                            />
                            Tolak booking
                        </button>
                    </div>
                </section>

                <section aria-labelledby="payment-heading" class="sb-card space-y-4 p-5 text-sm">
                    <h2 id="payment-heading" class="text-foreground text-base font-semibold tracking-tight">Pembayaran</h2>
                    <template v-if="payment">
                        <dl class="space-y-2">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Status</dt>
                                <dd class="text-foreground font-medium">{{ statusLabel(payment.status) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Jumlah</dt>
                                <dd class="text-foreground font-semibold tabular-nums">{{ formatRupiah(payment.amount) }}</dd>
                            </div>
                        </dl>

                        <a
                            v-if="payment.bukti_url"
                            :href="payment.bukti_url"
                            target="_blank"
                            rel="noopener"
                            class="group block rounded-xl focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                        >
                            <img
                                v-if="isImageUrl(payment.bukti_url)"
                                :src="payment.bukti_url"
                                alt="Bukti pembayaran"
                                loading="lazy"
                                class="bg-muted mb-2 max-h-56 w-full rounded-xl object-contain ring-1 ring-(--wp-hairline)"
                            />
                            <span
                                class="wp-link-arrow inline-flex items-center gap-1.5 font-medium text-(--wp-accent) group-hover:text-(--wp-accent-strong)"
                            >
                                Lihat bukti
                                <FontAwesomeIcon :icon="['fas', 'arrow-up-right-from-square']" class="size-3" aria-hidden="true" />
                            </span>
                        </a>

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
                            <div class="pt-2">
                                <label class="sb-label" for="reject-pay-reason">Alasan tolak bukti</label>
                                <textarea
                                    id="reject-pay-reason"
                                    v-model="rejectPayForm.reason"
                                    rows="2"
                                    placeholder="Alasan tolak bukti"
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
                    </template>
                    <p v-else class="text-muted-foreground">Belum ada data pembayaran. Muncul setelah pengajuan disetujui.</p>
                </section>
            </aside>
        </div>
    </AdminLayout>
</template>
