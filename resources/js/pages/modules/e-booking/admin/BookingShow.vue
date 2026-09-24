<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { AlertTriangle, ArrowRight, CheckCircle2, Info, LoaderCircle } from 'lucide-vue-next';
import { computed } from 'vue';

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
        area: { id: number; code: string; name: string } | null;
        user: { id: number; name: string; email: string } | null;
        penyewa: { nama: string; no_hp: string | null; instansi: string | null } | null;
        items: Array<{ uraian: string; satuan: string; qty: number; line_total: number }>;
        can_review: boolean;
        can_verify_payment: boolean;
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

const formatRp = (n: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

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

const submitApprove = (force = false) => {
    approveForm.force = force;
    approveForm.post(route('e-booking.admin.bookings.approve', props.booking.id), { preserveScroll: true });
};

const submitReject = () => {
    rejectForm.post(route('e-booking.admin.bookings.reject', props.booking.id), { preserveScroll: true });
};

const submitKlarifikasi = () => {
    klarifikasiForm.post(route('e-booking.admin.bookings.klarifikasi', props.booking.id), { preserveScroll: true });
};

const submitVerify = () => {
    if (!props.payment) return;
    verifyForm.post(route('e-booking.admin.payments.verify', props.payment.id), { preserveScroll: true });
};

const submitRejectPay = () => {
    if (!props.payment) return;
    rejectPayForm.post(route('e-booking.admin.payments.reject', props.payment.id), { preserveScroll: true });
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
                    <div v-if="booking.area" class="flex justify-between gap-3">
                        <span class="text-muted-foreground">Area</span>
                        <span class="text-right">{{ booking.area.name }}</span>
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
                                <li
                                    v-for="item in conflict.conflicts"
                                    :key="item.id"
                                    class="rounded-xl border border-white/70 bg-white/80 p-3"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ item.nomor }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ item.starts_at || '-' }} — {{ item.ends_at || '-' }}
                                            </p>
                                            <p class="mt-1 text-xs text-slate-600">
                                                Status: {{ statusLabel(item.status) }}
                                                <template v-if="item.priority_rule">
                                                    · {{ item.priority_rule.name }}
                                                </template>
                                            </p>
                                        </div>
                                        <span
                                            class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                            :class="relationTone(item.relation)"
                                        >
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
            </div>

            <aside class="space-y-4">
                <div v-if="booking.can_review" class="border-border/60 space-y-3 rounded-xl border p-5">
                    <h2 class="font-semibold">Tinjau pengajuan</h2>
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
                        placeholder="Catatan pengelola (boleh dikosongkan)"
                        class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                    />
                    <button
                        type="button"
                        class="bg-[var(--brand-green,#2e7d32)] inline-flex w-full items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="approveForm.processing"
                        @click="submitApprove(false)"
                    >
                        <LoaderCircle v-if="approveForm.processing" class="size-4 animate-spin" />
                        Setujui pengajuan
                    </button>
                    <button
                        type="button"
                        class="border-border inline-flex w-full items-center justify-center rounded-lg border px-4 py-2 text-sm font-semibold disabled:opacity-60"
                        :disabled="approveForm.processing"
                        @click="submitApprove(true)"
                    >
                        Setujui dan paksa lanjut
                    </button>

                    <div class="border-border space-y-2 border-t pt-3">
                        <textarea
                            v-model="klarifikasiForm.reason"
                            rows="2"
                            required
                            placeholder="Alasan klarifikasi"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                        <InputError :message="klarifikasiForm.errors.reason" />
                        <button
                            type="button"
                            class="border-border w-full rounded-lg border px-4 py-2 text-sm font-semibold"
                            :disabled="klarifikasiForm.processing"
                            @click="submitKlarifikasi"
                        >
                            Tandai perlu klarifikasi
                        </button>
                    </div>

                    <div class="border-border space-y-2 border-t pt-3">
                        <textarea
                            v-model="rejectForm.reason"
                            rows="2"
                            required
                            placeholder="Alasan tolak"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        />
                        <InputError :message="rejectForm.errors.reason" />
                        <button
                            type="button"
                            class="w-full rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 dark:border-red-800 dark:text-red-300"
                            :disabled="rejectForm.processing"
                            @click="submitReject"
                        >
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
                        <p>Jumlah: <span class="font-semibold">{{ formatRp(payment.amount) }}</span></p>
                        <a
                            v-if="payment.bukti_url"
                            :href="payment.bukti_url"
                            target="_blank"
                            rel="noopener"
                            class="text-[var(--brand-green,#2e7d32)] font-semibold hover:underline"
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
                                class="bg-[var(--brand-green,#2e7d32)] w-full rounded-lg px-4 py-2.5 text-sm font-semibold text-white"
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
