<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { useConfirm } from '@/composables/useConfirm';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, Check, CheckCircle2, FileText, Info, LoaderCircle, Mail, MessageSquareWarning, XCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

const formatRp = (n: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'Perlu ditinjau',
        awaiting_payment: 'Menunggu bayar',
        approved: 'Disetujui',
        paid: 'Sudah bayar',
        confirmed: 'Dikonfirmasi',
        perlu_klarifikasi: 'Perlu klarifikasi',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
        forfeited: 'Hangus',
        expired: 'Kedaluwarsa',
        completed: 'Selesai',
        awaiting_verification: 'Bukti perlu dicek',
    };

    return map[status] ?? status;
};

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
        return 'bg-emerald-100 text-emerald-800';
    }
    if (relation === 'other_unggul') {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-amber-100 text-amber-800';
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
            icon: AlertTriangle,
        };
    }
    if (c.flag === 'unggul') {
        return {
            title: 'Pengajuan ini lebih unggul',
            detail: 'Ada jadwal yang bentrok, tetapi prioritas pengajuan ini lebih tinggi.',
            tone: 'emerald' as const,
            icon: CheckCircle2,
        };
    }
    if (c.flag === 'rendah') {
        return {
            title: 'Ada pengajuan lain yang lebih prioritas',
            detail: 'Pertimbangkan tolak, klarifikasi, atau setujui dengan hati-hati (paksa).',
            tone: 'red' as const,
            icon: AlertTriangle,
        };
    }

    return {
        title: 'Ada jadwal yang berdekatan',
        detail: 'Periksa daftar di bawah sebelum memutuskan.',
        tone: 'sky' as const,
        icon: Info,
    };
});

const conflictBoxClass = computed(() => {
    const tone = conflictHeadline.value?.tone;
    if (tone === 'emerald') {
        return 'border-emerald-200 bg-emerald-50';
    }
    if (tone === 'red') {
        return 'border-red-200 bg-red-50';
    }
    if (tone === 'sky') {
        return 'border-sky-200 bg-sky-50';
    }

    return 'border-amber-200 bg-amber-50';
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
        <nav class="text-muted-foreground mb-4 text-xs">
            <Link :href="route('e-booking.admin.bookings.index')" class="hover:text-foreground">Pengajuan</Link>
            <span class="mx-2">→</span>
            <span class="text-foreground font-medium">{{ booking.nomor }}</span>
        </nav>

        <div class="mb-6">
            <h1 class="text-foreground text-2xl font-bold">{{ booking.nomor }}</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                Status <span class="text-foreground font-semibold">{{ statusLabel(booking.status) }}</span>
                <template v-if="booking.priority_flag"> · prioritas {{ booking.priority_flag }}</template>
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
            <div class="space-y-4">
                <div class="border-border/60 space-y-2 rounded-xl border p-5 text-sm">
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Penyewa</span>
                        <span class="text-right">
                            {{ booking.penyewa?.nama || booking.user?.name }}
                            <span class="text-muted-foreground block text-xs">{{ booking.user?.email }}</span>
                            <span v-if="booking.penyewa?.no_hp" class="text-muted-foreground block text-xs">{{ booking.penyewa.no_hp }}</span>
                        </span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Tempat</span>
                        <span class="text-right font-medium">{{ booking.venue?.name }}</span>
                    </div>
                    <div v-if="booking.areas?.length" class="flex justify-between gap-3">
                        <span class="text-slate-500">Area</span>
                        <span class="text-right">{{ booking.areas.map((a) => a.name).join(', ') }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Jadwal</span>
                        <span class="text-right">{{ booking.starts_at }} — {{ booking.ends_at }}</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Tujuan</span>
                        <span class="text-right">{{ booking.tujuan }}</span>
                    </div>
                    <div class="border-border flex justify-between gap-3 border-t pt-3 font-bold">
                        <span>Total</span>
                        <span>{{ formatRp(booking.grand_total) }}</span>
                    </div>
                </div>

                <div v-if="conflictHeadline && conflict?.conflicts?.length" class="rounded-xl border p-4 text-sm" :class="conflictBoxClass">
                    <div class="flex items-start gap-3">
                        <component :is="conflictHeadline.icon" class="mt-0.5 size-5 shrink-0 opacity-80" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ conflictHeadline.title }}</p>
                            <p class="mt-1 text-slate-700">{{ conflictHeadline.detail }}</p>
                            <p v-if="conflict.tentative_context" class="mt-2 text-xs text-slate-600">
                                Area ini bersifat tentatif (jadwal bisa berubah).
                            </p>
                            <p v-if="conflict.needs_clarification && conflict.kontak_klarifikasi" class="mt-1 text-xs text-slate-600">
                                Kontak klarifikasi: {{ conflict.kontak_klarifikasi }}
                            </p>

                            <ul class="mt-4 space-y-2">
                                <li v-for="item in conflict.conflicts" :key="item.id" class="rounded-xl border border-white/70 bg-white/80 p-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ item.nomor }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ item.starts_at || '-' }} — {{ item.ends_at || '-' }}</p>
                                            <p class="mt-1 text-xs text-slate-600">
                                                Status: {{ statusLabel(item.status) }}
                                                <template v-if="item.priority_rule"> · {{ item.priority_rule.name }} </template>
                                            </p>
                                        </div>
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :class="relationTone(item.relation)">
                                            {{ relationLabel(item.relation) }}
                                        </span>
                                    </div>
                                    <Link
                                        :href="route('e-booking.admin.bookings.show', item.id)"
                                        class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-sky-700 hover:underline"
                                    >
                                        Buka pengajuan ini
                                        <ArrowRight class="size-3" />
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="conflict && (!conflict.conflicts || conflict.conflicts.length === 0)"
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm"
                >
                    <div class="flex items-start gap-3">
                        <CheckCircle2 class="mt-0.5 size-5 text-emerald-700" />
                        <div>
                            <p class="font-semibold text-emerald-950">Tidak ada benturan jadwal</p>
                            <p class="mt-1 text-emerald-800/80">Tidak ditemukan pengajuan lain yang bentrok di rentang waktu ini.</p>
                        </div>
                    </div>
                </div>

                <div v-if="status_logs.length" class="border-border/60 rounded-xl border p-5 text-sm">
                    <h2 class="mb-2 font-semibold">Riwayat status</h2>
                    <ul class="text-muted-foreground space-y-1">
                        <li v-for="(log, i) in status_logs" :key="i">
                            <span class="text-foreground font-medium">{{ statusLabel(log.to_status || '') }}</span>
                            <template v-if="log.created_at"> · {{ log.created_at }}</template>
                        </li>
                    </ul>
                </div>

                <div class="border-border/60 space-y-3 rounded-xl border p-5 text-sm">
                    <div class="flex items-center gap-2">
                        <FileText class="size-4 text-[var(--brand-green,#2e7d32)]" />
                        <h2 class="font-semibold">Surat balasan</h2>
                    </div>

                    <div v-if="booking.surats.length" class="space-y-2">
                        <div v-for="s in booking.surats" :key="s.id" class="border-border/60 space-y-2 rounded-lg border p-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold">{{ s.jenis_label }}</p>
                                    <p class="text-muted-foreground text-xs">{{ s.nomor_surat }} · {{ s.perihal }}</p>
                                    <p v-if="s.meeting_at || s.meeting_place" class="mt-1 text-xs">
                                        Meeting: {{ s.meeting_at?.replace('T', ' ') }}{{ s.meeting_place ? ' · ' + s.meeting_place : '' }}
                                    </p>
                                    <p v-if="s.dokumen?.length" class="text-muted-foreground mt-1 text-xs">Dokumen: {{ s.dokumen.join(', ') }}</p>
                                    <p v-if="s.sent_email_at" class="mt-1 text-xs text-emerald-700">Terkirim via email {{ s.sent_email_at }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a
                                    :href="route('e-booking.admin.bookings.surat.download', s.id)"
                                    class="border-border inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-slate-50"
                                >
                                    <FileText class="size-3.5" />
                                    Unduh PDF
                                </a>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-sky-700 disabled:opacity-60"
                                    :disabled="emailForm.processing"
                                    @click="kirimEmailSurat(s.id)"
                                >
                                    <LoaderCircle v-if="emailForm.processing" class="size-3.5 animate-spin" />
                                    <Mail class="size-3.5" />
                                    Kirim email
                                </button>
                                <a
                                    :href="route('e-booking.admin.bookings.surat.whatsapp', s.id)"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700"
                                >
                                    WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-muted-foreground text-xs">Belum ada surat. Buat di bawah lalu kirim ke penyewa.</p>

                    <div class="border-border space-y-3 border-t pt-3">
                        <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Buat surat baru</p>

                        <div>
                            <label class="mb-1 block text-xs font-medium">Jenis surat</label>
                            <SimpleSelect
                                v-model="suratForm.jenis"
                                :options="jenisOptions"
                                placeholder="Pilih jenis surat"
                                trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium">Nomor surat (manual)</label>
                            <input
                                v-model="suratForm.nomor_surat"
                                type="text"
                                placeholder="cth: 042/E-BK/IX/2026"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            />
                            <InputError :message="suratForm.errors.nomor_surat" />
                        </div>

                        <div>
                            <div class="mb-1 flex items-center justify-between">
                                <label class="text-xs font-medium">Perihal</label>
                                <button type="button" class="text-xs font-semibold text-sky-700 hover:underline" @click="fillTemplate">
                                    Isi otomatis dari template
                                </button>
                            </div>
                            <input
                                v-model="suratForm.perihal"
                                type="text"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            />
                            <InputError :message="suratForm.errors.perihal" />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium">Isi surat</label>
                            <textarea
                                v-model="suratForm.isi"
                                rows="5"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            />
                            <InputError :message="suratForm.errors.isi" />
                        </div>

                        <template v-if="suratForm.jenis === 'undangan_meeting'">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Waktu meeting</label>
                                    <input
                                        v-model="suratForm.meeting_at"
                                        type="datetime-local"
                                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                    />
                                    <InputError :message="suratForm.errors.meeting_at" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Tempat meeting</label>
                                    <input
                                        v-model="suratForm.meeting_place"
                                        type="text"
                                        placeholder="cth: Ruang rapat UPT"
                                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                    />
                                    <InputError :message="suratForm.errors.meeting_place" />
                                </div>
                            </div>
                        </template>

                        <div v-if="document_types.length">
                            <label class="mb-1 block text-xs font-medium">Dokumen yang harus disiapkan penyewa</label>
                            <div class="border-border/60 space-y-1.5 rounded-lg border p-3">
                                <label v-for="d in document_types" :key="d.id" class="flex cursor-pointer items-center gap-2 text-xs">
                                    <input
                                        type="checkbox"
                                        class="accent-[var(--brand-green,#2e7d32)]"
                                        :checked="suratForm.dokumen.includes(d.id)"
                                        @change="toggleDokumen(d.id)"
                                    />
                                    <span>{{ d.name }}</span>
                                    <span v-if="d.is_required" class="text-[10px] font-semibold text-amber-700">wajib</span>
                                </label>
                            </div>
                            <p class="text-muted-foreground mt-1 text-[11px]">Diatur dinamis di menu Jenis Dokumen.</p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium">Nama penandatangan</label>
                                <input
                                    v-model="suratForm.penandatangan_nama"
                                    type="text"
                                    class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium">Jabatan</label>
                                <input
                                    v-model="suratForm.penandatangan_jabatan"
                                    type="text"
                                    class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                />
                            </div>
                        </div>

                        <p v-if="namaDokumenTerpilih.length" class="text-muted-foreground text-[11px]">
                            Akan tercantum di surat: {{ namaDokumenTerpilih.join(', ') }}
                        </p>

                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[var(--brand-green,#2e7d32)] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                            :disabled="suratForm.processing"
                            @click="submitSurat"
                        >
                            <LoaderCircle v-if="suratForm.processing" class="size-4 animate-spin" />
                            Buat surat (PDF)
                        </button>
                    </div>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-36 lg:self-start">
                <div v-if="booking.can_review" class="border-border/60 space-y-3 rounded-xl border p-5">
                    <h2 class="font-semibold">Keputusan peninjauan</h2>

                    <div>
                        <label class="mb-1 block text-xs font-medium">Aturan prioritas (opsional)</label>
                        <SimpleSelect
                            v-model="priorityRuleSelect"
                            :options="priorityRuleOptions"
                            placeholder="Pakai aturan bawaan"
                            trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                        />
                    </div>
                    <textarea
                        v-model="approveForm.admin_notes"
                        rows="2"
                        placeholder="Catatan untuk penyewa (tampil di halaman pesanan mereka)"
                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                    />

                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[var(--brand-green,#2e7d32)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-95 disabled:opacity-60"
                        :disabled="approveForm.processing || rejectForm.processing || klarifikasiForm.processing"
                        @click="submitApprove(false)"
                    >
                        <LoaderCircle v-if="approveForm.processing" class="size-4 animate-spin" />
                        <Check v-else class="size-4" />
                        Setujui pengajuan
                    </button>
                    <button
                        v-if="adaBenturan"
                        type="button"
                        class="inline-flex w-full items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-800 transition hover:bg-amber-100 disabled:opacity-60"
                        :disabled="approveForm.processing"
                        @click="submitApprove(true)"
                    >
                        Paksa lanjut (abaikan benturan)
                    </button>

                    <div class="border-border grid grid-cols-2 gap-2 border-t pt-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                            :class="
                                activeDecision === 'klarifikasi'
                                    ? 'border-amber-400 bg-amber-100 text-amber-900'
                                    : 'border-amber-300 text-amber-800 hover:bg-amber-50'
                            "
                            @click="toggleDecision('klarifikasi')"
                        >
                            <MessageSquareWarning class="size-3.5" />
                            Perlu klarifikasi
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                            :class="
                                activeDecision === 'tolak' ? 'border-red-400 bg-red-100 text-red-900' : 'border-red-300 text-red-700 hover:bg-red-50'
                            "
                            @click="toggleDecision('tolak')"
                        >
                            <XCircle class="size-3.5" />
                            Tolak
                        </button>
                    </div>

                    <div v-if="activeDecision === 'klarifikasi'" class="border-border space-y-2 rounded-lg border bg-amber-50/60 p-3">
                        <p class="text-xs font-semibold text-amber-900">Tandai perlu klarifikasi</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="chip in klarifikasiChips"
                                :key="chip"
                                type="button"
                                class="rounded-full border border-amber-300 bg-white px-2.5 py-1 text-[11px] font-medium text-amber-800 transition hover:bg-amber-100"
                                @click="klarifikasiForm.reason = chip"
                            >
                                {{ chip }}
                            </button>
                        </div>
                        <textarea
                            v-model="klarifikasiForm.reason"
                            rows="2"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                        <InputError :message="klarifikasiForm.errors.reason" />
                        <button
                            type="button"
                            class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:opacity-60"
                            :disabled="klarifikasiForm.processing || klarifikasiForm.reason.trim() === ''"
                            @click="submitKlarifikasi"
                        >
                            <LoaderCircle v-if="klarifikasiForm.processing" class="mr-1 inline size-4 animate-spin" />
                            Kirim tanda klarifikasi
                        </button>
                    </div>

                    <div v-if="activeDecision === 'tolak'" class="space-y-2 rounded-lg border border-red-200 bg-red-50/60 p-3">
                        <p class="text-xs font-semibold text-red-900">Tolak pengajuan</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="chip in rejectChips"
                                :key="chip"
                                type="button"
                                class="rounded-full border border-red-300 bg-white px-2.5 py-1 text-[11px] font-medium text-red-700 transition hover:bg-red-100"
                                @click="rejectForm.reason = chip"
                            >
                                {{ chip }}
                            </button>
                        </div>
                        <textarea
                            v-model="rejectForm.reason"
                            rows="2"
                            required
                            placeholder="Pilih alasan cepat di atas atau tulis sendiri"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                        <InputError :message="rejectForm.errors.reason" />
                        <button
                            type="button"
                            class="w-full rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:opacity-60"
                            :disabled="rejectForm.processing || rejectForm.reason.trim() === ''"
                            @click="submitReject"
                        >
                            <LoaderCircle v-if="rejectForm.processing" class="mr-1 inline size-4 animate-spin" />
                            Tolak booking
                        </button>
                    </div>
                </div>

                <div class="border-border/60 space-y-3 rounded-xl border p-5 text-sm">
                    <h2 class="font-semibold">Pembayaran</h2>
                    <template v-if="payment">
                        <p>
                            Status:
                            <span class="font-medium">{{ statusLabel(payment.status) }}</span>
                        </p>
                        <p>
                            Jumlah: <span class="font-semibold">{{ formatRp(payment.amount) }}</span>
                        </p>
                        <a
                            v-if="payment.bukti_url"
                            :href="payment.bukti_url"
                            target="_blank"
                            rel="noopener"
                            class="font-semibold text-[var(--brand-green,#2e7d32)] hover:underline"
                        >
                            Lihat bukti →
                        </a>
                        <template v-if="booking.can_verify_payment">
                            <textarea
                                v-model="verifyForm.notes"
                                rows="2"
                                placeholder="Catatan verifikasi (opsional)"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            />
                            <button
                                type="button"
                                class="w-full rounded-lg bg-[var(--brand-green,#2e7d32)] px-4 py-2.5 text-sm font-semibold text-white"
                                :disabled="verifyForm.processing"
                                @click="submitVerify"
                            >
                                Verifikasi bukti pembayaran
                            </button>
                            <textarea
                                v-model="rejectPayForm.reason"
                                rows="2"
                                placeholder="Alasan tolak bukti"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            />
                            <InputError :message="rejectPayForm.errors.reason" />
                            <button
                                type="button"
                                class="border-border w-full rounded-lg border px-4 py-2 text-sm font-semibold"
                                :disabled="rejectPayForm.processing"
                                @click="submitRejectPay"
                            >
                                Tolak bukti
                            </button>
                        </template>
                    </template>
                    <p v-else class="text-muted-foreground">Belum ada data pembayaran. Muncul setelah pengajuan disetujui.</p>
                </div>
            </aside>
        </div>
    </AdminLayout>
</template>
