<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type BookingRow = {
    id: number;
    nomor: string;
    status: string;
    priority_flag: string | null;
    starts_at: string | null;
    ends_at: string | null;
    grand_total: number;
    venue: { id: number; code: string; name: string } | null;
    area: { id: number; code: string; name: string } | null;
    created_at: string | null;
};

const props = defineProps<{
    bookings: {
        data: BookingRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: { status: string };
    status_options: string[];
}>();

const statusFilter = ref(props.filters.status || '');

const formatRp = (n: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

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

const statusClass = (status: string) => {
    if (['confirmed', 'paid', 'completed'].includes(status)) {
        return 'bg-emerald-100 text-emerald-800';
    }
    if (['awaiting_payment', 'approved', 'menunggu_approval', 'perlu_klarifikasi'].includes(status)) {
        return 'bg-amber-100 text-amber-900';
    }
    if (['rejected', 'cancelled', 'forfeited', 'expired'].includes(status)) {
        return 'bg-red-100 text-red-800';
    }

    return 'bg-slate-100 text-slate-700';
};

const applyFilter = () => {
    router.get(
        route('e-booking.bookings.index'),
        { status: statusFilter.value || undefined },
        { preserveState: true, replace: true },
    );
};

const statusFilterOptions = computed(() => [
    { value: 'all', label: 'Semua status' },
    ...props.status_options.map((s) => ({ value: s, label: statusLabel(s) })),
]);

const statusFilterValue = computed({
    get: () => statusFilter.value || 'all',
    set: (val: string | number) => {
        statusFilter.value = val === 'all' ? '' : String(val);
        applyFilter();
    },
});

const pageLinks = computed(() =>
    props.bookings.links.filter((l) => l.label !== '&laquo; Previous' && l.label !== 'Next &raquo;'),
);
</script>

<template>
    <SeoHead title="Riwayat Booking" description="Daftar pengajuan sewa fasilitas E-Booking Anda." />

    <EBookingLayout active="history">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Pesanan saya</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Lihat perkembangan pengajuan, petunjuk pembayaran, dan bukti transfer di sini.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <label class="sr-only" for="status">Filter status</label>
                <SimpleSelect
                    id="status"
                    v-model="statusFilterValue"
                    :options="statusFilterOptions"
                    placeholder="Filter status"
                    trigger-class="h-10 w-[200px] rounded-full border-slate-200 bg-white px-4 text-sm shadow-none"
                />
            </div>
        </div>

        <div v-if="bookings.data.length === 0" class="rounded-[28px] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <p class="text-sm text-slate-500">Belum ada pesanan yang tersimpan.</p>
            <Link
                :href="route('e-booking.catalog')"
                class="mt-3 inline-block text-sm font-semibold text-[var(--brand-green,#2e7d32)] hover:underline"
            >
                Cari tempat sekarang →
            </Link>
        </div>

        <div v-else class="space-y-4">
            <Link
                v-for="item in bookings.data"
                :key="item.id"
                :href="route('e-booking.bookings.show', item.id)"
                class="block rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_40px_rgba(15,23,42,0.08)]"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold text-slate-900">{{ item.nomor }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ item.venue?.name }}
                            <template v-if="item.area"> · {{ item.area.name }}</template>
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ item.starts_at }} — {{ item.ends_at }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(item.status)">
                            {{ statusLabel(item.status) }}
                        </span>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ formatRp(item.grand_total) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Lihat detail</p>
                    </div>
                </div>
            </Link>
        </div>

        <nav
            v-if="bookings.last_page > 1"
            class="mt-8 flex flex-wrap items-center justify-center gap-2"
            aria-label="Pagination"
        >
            <template v-for="(link, idx) in pageLinks" :key="idx">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded-full border px-3 py-1.5 text-sm"
                    :class="link.active ? 'border-[var(--brand-green,#2e7d32)] bg-[var(--brand-green,#2e7d32)] text-white' : 'border-slate-200 bg-white'"
                    v-html="link.label"
                />
                <span
                    v-else
                    class="rounded-full border border-transparent px-3 py-1.5 text-sm text-slate-400"
                    v-html="link.label"
                />
            </template>
        </nav>
    </EBookingLayout>
</template>
