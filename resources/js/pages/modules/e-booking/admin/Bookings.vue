<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Skeleton } from '@/components/ui/skeleton';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { bookingStatusLabel, formatRupiah } from '@/lib/bookingStatus';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowRight,
    faCalendarDay,
    faClipboardList,
    faFileArrowDown,
    faList,
    faMagnifyingGlass,
    faShieldHalved,
    faUsers,
    faWallet,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

library.add(
    faArrowRight,
    faCalendarDay,
    faClipboardList,
    faFileArrowDown,
    faList,
    faMagnifyingGlass,
    faShieldHalved,
    faUsers,
    faWallet,
);

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
    jenis_sewa?: 'reguler' | 'event';
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
        current_page: number;
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

const statusLabel = (status: string) => bookingStatusLabel(status, 'admin');

const actionToneClass = (tone: string) => {
    const map: Record<string, string> = {
        amber: 'text-(--sb-warning)',
        violet: 'text-(--sb-info)',
        meeting: 'text-(--sb-meeting)',
        sky: 'text-(--wp-accent)',
        slate: 'text-muted-foreground',
    };

    return map[tone] ?? 'text-(--wp-accent)';
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
    { key: 'active', label: 'Antrian aktif', hint: 'Semua yang masih proses', icon: 'clipboard-list' },
    { key: 'review', label: 'Perlu ditinjau', hint: 'Setujui / klarifikasi', icon: 'magnifying-glass' },
    { key: 'payment', label: 'Menunggu bayar', hint: 'Sudah disetujui', icon: 'wallet' },
    { key: 'verify', label: 'Cek bukti', hint: 'Transfer masuk', icon: 'shield-halved' },
    { key: 'all', label: 'Semua', hint: 'Termasuk selesai', icon: 'clipboard-list' },
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

const pageLinks = computed(() => (props.bookings?.links ?? []).filter((l) => l.label !== '&laquo; Previous' && l.label !== 'Next &raquo;'));

const currentPage = computed(() => props.bookings?.current_page ?? 1);

const applyPage = (page: number) => {
    router.get(
        route('e-booking.admin.bookings.index'),
        {
            page,
            tab: activeTab.value,
            ...(activeTab.value === 'all' && statusFilter.value ? { status: statusFilter.value } : {}),
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const displayStatus = (item: Row) => {
    if (item.needs_verify) {
        return 'Bukti perlu dicek';
    }

    return statusLabel(item.status);
};

const activeTabLabel = computed(() => tabs.value.find((t) => t.key === activeTab.value)?.label ?? 'Pengajuan');

const exporting = ref(false);

const currentMonth = (() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
})();

const runExport = (params: Record<string, string | undefined>) => {
    const search = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== '') {
            search.set(key, value);
        }
    });

    const query = search.toString();
    const base = route('e-booking.admin.bookings.export');

    exporting.value = true;
    window.location.href = query ? `${base}?${query}` : base;
    window.setTimeout(() => {
        exporting.value = false;
    }, 1500);
};

const exportCurrentFilter = () =>
    runExport({
        tab: activeTab.value,
        ...(activeTab.value === 'all' && statusFilter.value ? { status: statusFilter.value } : {}),
    });
</script>

<template>
    <SeoHead title="Pengajuan Sewa" />

    <AdminLayout active="bookings">
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl">Pengajuan sewa</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                    Pilih tab sesuai pekerjaan Anda. Setiap baris menampilkan apa yang perlu dilakukan berikutnya.
                </p>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <button type="button" class="wp-btn wp-btn-quiet shrink-0 px-4 py-2 text-sm" :disabled="exporting">
                        <FontAwesomeIcon :icon="['fas', 'file-arrow-down']" class="size-3.5" aria-hidden="true" />
                        {{ exporting ? 'Menyiapkan…' : 'Export Excel' }}
                    </button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end" class="w-64 rounded-xl p-1.5">
                    <DropdownMenuLabel class="text-muted-foreground px-2 py-1.5 text-xs font-medium">Export pengajuan</DropdownMenuLabel>
                    <DropdownMenuItem class="gap-2.5 rounded-lg" @click="runExport({ month: currentMonth })">
                        <FontAwesomeIcon :icon="['fas', 'calendar-day']" class="size-3.5 text-muted-foreground" aria-hidden="true" />
                        Bulan ini (semua status)
                    </DropdownMenuItem>
                    <DropdownMenuItem class="gap-2.5 rounded-lg" @click="runExport({ month: currentMonth, has_meeting: '1' })">
                        <FontAwesomeIcon :icon="['fas', 'users']" class="size-3.5 text-muted-foreground" aria-hidden="true" />
                        Bulan ini + sudah meeting
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem class="gap-2.5 rounded-lg" @click="exportCurrentFilter()">
                        <FontAwesomeIcon :icon="['fas', 'list']" class="size-3.5 text-muted-foreground" aria-hidden="true" />
                        Sesuai filter aktif
                    </DropdownMenuItem>
                    <DropdownMenuItem class="gap-2.5 rounded-lg" @click="runExport({})">
                        <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="size-3.5 text-muted-foreground" aria-hidden="true" />
                        Semua pengajuan
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>

        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="-mx-1 flex gap-2 overflow-x-auto px-1 py-1" role="group" aria-label="Saring antrian">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    class="sb-chip shrink-0"
                    :aria-pressed="activeTab === tab.key ? 'true' : 'false'"
                    :title="tab.hint"
                    @click="applyTab(tab.key)"
                >
                    <FontAwesomeIcon :icon="['fas', tab.icon]" class="size-3 opacity-80" aria-hidden="true" />
                    {{ tab.label }}
                    <span v-if="counts" class="text-xs tabular-nums opacity-75">{{ counts[tab.key as keyof Counts] }}</span>
                    <Skeleton v-else class="h-3.5 w-4 rounded" />
                </button>
            </div>

            <div v-if="activeTab === 'all'" class="flex items-center gap-2">
                <span class="text-muted-foreground shrink-0 text-sm">Status</span>
                <SimpleSelect
                    v-model="statusFilterSelect"
                    :options="statusFilterOptions"
                    placeholder="Semua status"
                    trigger-class="h-9 w-[220px] rounded-xl border-(--wp-hairline) bg-card px-3 text-sm shadow-none"
                />
            </div>
        </div>

        <Deferred :data="['bookings', 'counts']">
            <template #fallback>
                <div class="sb-card overflow-hidden" aria-busy="true" aria-label="Memuat pengajuan">
                    <div class="flex gap-6 border-b border-(--wp-hairline) px-4 py-3">
                        <Skeleton v-for="n in 5" :key="n" class="h-3 w-16" />
                    </div>
                    <div class="divide-y divide-(--wp-hairline)">
                        <div v-for="n in 6" :key="n" class="flex items-center gap-6 px-4 py-4">
                            <Skeleton class="h-4 w-28" />
                            <div class="flex-1 space-y-2">
                                <Skeleton class="h-4 w-48 max-w-full" />
                                <Skeleton class="h-3 w-32" />
                            </div>
                            <Skeleton class="hidden h-3 w-36 md:block" />
                            <Skeleton class="h-5 w-24 rounded-lg" />
                            <Skeleton class="hidden h-4 w-20 sm:block" />
                        </div>
                    </div>
                </div>
            </template>

            <div v-if="bookings">
                <div v-if="bookings.data.length === 0" class="sb-card flex flex-col items-center px-6 py-16 text-center">
                    <span class="wp-icon size-12">
                        <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="size-5" aria-hidden="true" />
                    </span>
                    <h2 class="text-foreground mt-4 text-base font-semibold tracking-tight">{{ emptyCopy.title }}</h2>
                    <p class="text-muted-foreground mt-1 max-w-md text-sm">{{ emptyCopy.detail }}</p>
                    <button v-if="activeTab !== 'all'" type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="applyTab('all')">
                        Lihat semua pengajuan
                    </button>
                </div>

                <div v-else class="sb-card overflow-x-auto">
                    <table class="sb-table">
                        <caption class="sr-only">
                            Daftar pengajuan sewa ·
                            {{
                                activeTabLabel
                            }}
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Kode</th>
                                <th scope="col">Penyewa &amp; tempat</th>
                                <th scope="col">Jadwal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-right">Total</th>
                                <th scope="col" class="text-right">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in bookings.data" :key="item.id">
                                <td class="whitespace-nowrap">
                                    <Link
                                        :href="route('e-booking.admin.bookings.show', item.id)"
                                        class="text-foreground rounded-sm font-semibold tabular-nums hover:text-(--wp-accent) focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                    >
                                        {{ item.nomor }}
                                    </Link>
                                </td>
                                <td class="min-w-56">
                                    <p class="text-foreground font-medium">{{ item.penyewa || 'Penyewa' }}</p>
                                    <p class="text-muted-foreground mt-0.5 text-xs">
                                        {{ item.venue || 'Tempat' }}<template v-if="item.area"> · {{ item.area }}</template>
                                    </p>
                                    <span
                                        v-if="item.jenis_sewa"
                                        class="sb-badge mt-1.5 px-1.5 py-0 text-[0.6875rem]"
                                        :class="item.jenis_sewa === 'reguler' ? 'sb-tone-info' : 'sb-tone-meeting'"
                                    >
                                        {{ item.jenis_sewa === 'reguler' ? 'Latihan' : 'Event' }}
                                    </span>
                                </td>
                                <td class="text-muted-foreground min-w-44 text-xs tabular-nums">
                                    {{ formatSchedule(item.starts_at, item.ends_at) }}
                                </td>
                                <td>
                                    <SbStatusBadge
                                        :status="item.status"
                                        audience="admin"
                                        :label="displayStatus(item)"
                                        :tone="item.needs_verify ? 'info' : undefined"
                                    />
                                </td>
                                <td class="text-foreground text-right font-semibold whitespace-nowrap tabular-nums">
                                    {{ formatRupiah(item.grand_total) }}
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <Link
                                        :href="route('e-booking.admin.bookings.show', item.id)"
                                        class="wp-link-arrow inline-flex items-center gap-1.5 rounded-sm text-sm font-medium hover:underline focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                        :class="actionToneClass(item.action_tone)"
                                    >
                                        {{ item.action_label }}
                                        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                                        <span class="sr-only"> · {{ item.nomor }}</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="(props.bookings?.last_page ?? 0) > 1" class="mt-5 flex flex-col items-center justify-between gap-3 sm:flex-row">
                    <p class="text-muted-foreground text-sm tabular-nums">Halaman {{ currentPage }} dari {{ props.bookings?.last_page }}</p>
                    <nav class="flex flex-wrap items-center gap-1.5" aria-label="Pagination">
                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet px-3.5 py-1.5 text-sm"
                            :disabled="currentPage <= 1"
                            @click="currentPage > 1 && applyPage(currentPage - 1)"
                        >
                            Sebelumnya
                        </button>

                        <button
                            v-for="(link, idx) in pageLinks"
                            :key="idx"
                            type="button"
                            class="sb-chip min-w-9 justify-center tabular-nums"
                            :aria-current="link.active ? 'page' : undefined"
                            @click="applyPage(Number(link.label))"
                        >
                            {{ link.label }}
                        </button>

                        <button
                            type="button"
                            class="wp-btn wp-btn-quiet px-3.5 py-1.5 text-sm"
                            :disabled="currentPage >= (props.bookings?.last_page ?? 1)"
                            @click="currentPage < (props.bookings?.last_page ?? 1) && applyPage(currentPage + 1)"
                        >
                            Berikutnya
                        </button>
                    </nav>
                </div>
            </div>
        </Deferred>
    </AdminLayout>
</template>
