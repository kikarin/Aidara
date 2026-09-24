<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Skeleton } from '@/components/ui/skeleton';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ClipboardList, FileSearch, ShieldCheck, Wallet } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

type Row = {
    id: number;
    nomor: string;
    status: string;
    priority_flag: string | null;
    starts_at: string | null;
    ends_at: string | null;
    grand_total: number;
    venue: string | null;
    area: string | null;
    penyewa: string | null;
    payment_status: string | null;
    needs_verify: boolean;
    action_label: string;
    action_tone: string;
};

type Counts = {
    active: number;
    review: number;
    payment: number;
    verify: number;
    all: number;
};

const props = defineProps<{
    bookings?: {
        data: Row[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        last_page: number;
    };
    counts?: Counts;
    filters: { tab: string; status: string };
    status_options: string[];
}>();

const activeTab = ref(props.filters.tab || 'active');
const statusFilter = ref(props.filters.status || '');

watch(
    () => props.filters,
    (f) => {
        activeTab.value = f.tab || 'active';
        statusFilter.value = f.status || '';
    },
);

const formatRp = (n: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'Perlu ditinjau',
        awaiting_payment: 'Menunggu bayar',
        awaiting_verification: 'Bukti perlu dicek',
        approved: 'Disetujui',
        paid: 'Sudah bayar',
        confirmed: 'Dikonfirmasi',
        perlu_klarifikasi: 'Perlu klarifikasi',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
        forfeited: 'Hangus',
        expired: 'Kedaluwarsa',
        completed: 'Selesai',
        draft: 'Draf',
    };

    return map[status] ?? status;
};

const statusTone = (status: string, needsVerify = false) => {
    if (needsVerify) {
        return 'bg-violet-100 text-violet-800';
    }
    const map: Record<string, string> = {
        menunggu_approval: 'bg-amber-100 text-amber-800',
        perlu_klarifikasi: 'bg-orange-100 text-orange-800',
        awaiting_payment: 'bg-sky-100 text-sky-800',
        approved: 'bg-emerald-100 text-emerald-800',
        confirmed: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-slate-100 text-slate-600',
        expired: 'bg-slate-100 text-slate-600',
        completed: 'bg-slate-100 text-slate-700',
    };

    return map[status] ?? 'bg-slate-100 text-slate-700';
};

const actionToneClass = (tone: string) => {
    const map: Record<string, string> = {
        amber: 'text-amber-700',
        violet: 'text-violet-700',
        sky: 'text-sky-700',
        slate: 'text-slate-600',
    };

    return map[tone] ?? 'text-sky-700';
};

const formatSchedule = (starts: string | null, ends: string | null) => {
    if (!starts) {
        return 'Jadwal belum diisi';
    }
    if (!ends) {
        return starts;
    }
    if (starts.slice(0, 10) === ends.slice(0, 10)) {
        return `${starts} – ${ends.slice(11, 16)}`;
    }

    return `${starts} → ${ends}`;
};

const tabs = computed(() => [
    { key: 'active', label: 'Antrian aktif', hint: 'Semua yang masih proses', icon: ClipboardList },
    { key: 'review', label: 'Perlu ditinjau', hint: 'Setujui / klarifikasi', icon: FileSearch },
    { key: 'payment', label: 'Menunggu bayar', hint: 'Sudah disetujui', icon: Wallet },
    { key: 'verify', label: 'Cek bukti', hint: 'Transfer masuk', icon: ShieldCheck },
    { key: 'all', label: 'Semua', hint: 'Termasuk selesai', icon: ClipboardList },
]);

const emptyCopy = computed(() => {
    switch (activeTab.value) {
        case 'review':
            return {
                title: 'Tidak ada yang perlu ditinjau',
                detail: 'Pengajuan baru akan muncul di sini.',
            };
        case 'payment':
            return {
                title: 'Tidak ada yang menunggu bayar',
                detail: 'Setelah pengajuan disetujui, statusnya masuk ke sini.',
            };
        case 'verify':
            return {
                title: 'Tidak ada bukti untuk dicek',
                detail: 'Saat penyewa upload bukti transfer, daftarnya muncul di sini.',
            };
        case 'all':
            return {
                title: 'Belum ada pengajuan',
                detail: 'Semua riwayat sewa akan tampil di sini.',
            };
        default:
            return {
                title: 'Antrian kosong',
                detail: 'Saat ini tidak ada pengajuan yang perlu diproses.',
            };
    }
});

const applyTab = (tab: string) => {
    activeTab.value = tab;
    router.get(
        route('e-booking.admin.bookings.index'),
        {
            tab,
            ...(tab === 'all' && statusFilter.value ? { status: statusFilter.value } : {}),
        },
        { preserveState: true, replace: true },
    );
};

const applyStatusFilter = () => {
    router.get(
        route('e-booking.admin.bookings.index'),
        {
            tab: 'all',
            status: statusFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const statusFilterOptions = computed(() => [
    { value: 'all', label: 'Semua status' },
    ...props.status_options.map((s) => ({ value: s, label: statusLabel(s) })),
]);

const statusFilterSelect = computed({
    get: () => statusFilter.value || 'all',
    set: (val: string | number) => {
        statusFilter.value = val === 'all' ? '' : String(val);
        applyStatusFilter();
    },
});

const displayStatus = (item: Row) => {
    if (item.needs_verify) {
        return 'Bukti perlu dicek';
    }

    return statusLabel(item.status);
};
</script>

<template>
    <SeoHead title="Pengajuan Sewa" />

    <AdminLayout active="bookings">
        <section class="mb-6">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Pengajuan sewa</h1>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">
                Pilih tab sesuai pekerjaan Anda. Kartu menampilkan apa yang perlu dilakukan berikutnya.
            </p>
        </section>

        <!-- Tabs -->
        <div class="mb-5 flex gap-2 overflow-x-auto pb-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2.5 text-sm transition"
                :class="
                    activeTab === tab.key
                        ? 'bg-slate-900 font-semibold text-white shadow-sm'
                        : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'
                "
                @click="applyTab(tab.key)"
            >
                <component :is="tab.icon" class="size-3.5 opacity-80" />
                {{ tab.label }}
                <span
                    v-if="counts"
                    class="rounded-full px-1.5 py-0.5 text-[11px] font-semibold"
                    :class="activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                >
                    {{ counts[tab.key as keyof Counts] }}
                </span>
                <Skeleton v-else class="h-4 w-5 rounded-full" />
            </button>
        </div>

        <div
            v-if="activeTab === 'all'"
            class="mb-5 flex flex-col gap-2 rounded-[20px] border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <p class="text-sm text-slate-600">Saring status spesifik (opsional)</p>
            <SimpleSelect
                v-model="statusFilterSelect"
                :options="statusFilterOptions"
                placeholder="Semua status"
                trigger-class="h-10 w-[220px] rounded-xl border-slate-200 bg-slate-50 px-3 text-sm shadow-none"
            />
        </div>

        <Deferred :data="['bookings', 'counts']">
            <template #fallback>
                <div class="space-y-3" aria-busy="true" aria-label="Memuat pengajuan">
                    <div
                        v-for="n in 5"
                        :key="n"
                        class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <Skeleton class="h-5 w-40" />
                                <Skeleton class="h-4 w-56" />
                                <Skeleton class="h-3 w-44" />
                            </div>
                            <div class="space-y-2 text-right">
                                <Skeleton class="ml-auto h-6 w-28 rounded-full" />
                                <Skeleton class="ml-auto h-4 w-24" />
                                <Skeleton class="ml-auto h-4 w-20" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="bookings">
                <div
                    v-if="bookings.data.length === 0"
                    class="flex flex-col items-center rounded-[24px] border border-dashed border-slate-200 bg-white px-6 py-14 text-center shadow-sm"
                >
                    <div class="rounded-2xl bg-slate-50 p-3 text-slate-400">
                        <ClipboardList class="size-6" />
                    </div>
                    <p class="mt-4 font-semibold text-slate-800">{{ emptyCopy.title }}</p>
                    <p class="mt-1 max-w-md text-sm text-slate-500">{{ emptyCopy.detail }}</p>
                </div>

                <div v-else class="space-y-3">
                    <Link
                        v-for="item in bookings.data"
                        :key="item.id"
                        :href="route('e-booking.admin.bookings.show', item.id)"
                        class="group block rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-200 hover:shadow-md"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-slate-900">{{ item.nomor }}</p>
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                        :class="statusTone(item.status, item.needs_verify)"
                                    >
                                        {{ displayStatus(item) }}
                                    </span>
                                </div>
                                <p class="mt-1.5 text-sm text-slate-700">
                                    <span class="font-medium">{{ item.penyewa || 'Penyewa' }}</span>
                                    <span class="text-slate-400"> · </span>
                                    {{ item.venue || 'Tempat' }}
                                    <template v-if="item.area">
                                        <span class="text-slate-400"> · </span>{{ item.area }}
                                    </template>
                                </p>
                                <p class="mt-1 text-xs text-slate-500">{{ formatSchedule(item.starts_at, item.ends_at) }}</p>
                            </div>

                            <div class="flex shrink-0 flex-col items-start gap-1 sm:items-end">
                                <p class="text-base font-bold text-slate-900">{{ formatRp(item.grand_total) }}</p>
                                <p
                                    class="inline-flex items-center gap-1 text-sm font-semibold"
                                    :class="actionToneClass(item.action_tone)"
                                >
                                    {{ item.action_label }}
                                    <ArrowRight class="size-3.5 transition group-hover:translate-x-0.5" />
                                </p>
                            </div>
                        </div>
                    </Link>
                </div>

                <nav v-if="bookings.last_page > 1" class="mt-6 flex flex-wrap justify-center gap-2">
                    <Link
                        v-for="(link, idx) in bookings.links"
                        :key="idx"
                        :href="link.url || '#'"
                        class="rounded-full border px-3 py-1.5 text-sm"
                        :class="
                            link.active
                                ? 'border-transparent bg-[var(--brand-green,#2e7d32)] text-white'
                                : 'border-slate-200 bg-white text-slate-700'
                        "
                        v-html="link.label"
                    />
                </nav>
            </div>
        </Deferred>
    </AdminLayout>
</template>
