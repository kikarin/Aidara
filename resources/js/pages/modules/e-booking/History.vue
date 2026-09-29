<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { bookingStatusLabel, formatRupiah } from '@/lib/bookingStatus';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRight, faCalendarDays, faClipboardList } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

library.add(faArrowRight, faCalendarDays, faClipboardList);

type BookingRow = {
    id: number;
    nomor: string;
    status: string;
    priority_flag: string | null;
    starts_at: string | null;
    ends_at: string | null;
    grand_total: number;
    venue: { id: number; code: string; name: string } | null;
    areas: Array<{ id: number; code: string; name: string }>;
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

const applyFilter = () => {
    router.get(route('e-booking.bookings.index'), { status: statusFilter.value || undefined }, { preserveState: true, replace: true });
};

const statusFilterOptions = computed(() => [
    { value: 'all', label: 'Semua status' },
    ...props.status_options.map((s) => ({ value: s, label: bookingStatusLabel(s, 'renter') })),
]);

const statusFilterValue = computed({
    get: () => statusFilter.value || 'all',
    set: (val: string | number) => {
        statusFilter.value = val === 'all' ? '' : String(val);
        applyFilter();
    },
});

const pageLinks = computed(() => props.bookings.links.filter((l) => l.label !== '&laquo; Previous' && l.label !== 'Next &raquo;'));
</script>

<template>
    <SeoHead title="Riwayat Booking" description="Daftar pengajuan sewa venue Si Bola milik Anda." />

    <EBookingLayout active="history">
        <div class="mb-8 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <p class="wp-eyebrow">Riwayat</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">Pesanan saya</h1>
                <p class="text-muted-foreground mt-2 text-sm leading-relaxed">
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
                    trigger-class="h-10 w-[200px] rounded-xl px-4 text-sm shadow-none"
                />
            </div>
        </div>

        <div v-if="bookings.data.length === 0" class="sb-card flex flex-col items-center px-6 py-14 text-center">
            <span class="wp-icon size-12">
                <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-4 text-lg font-semibold tracking-tight">Belum ada pesanan</h2>
            <p class="text-muted-foreground mt-1 max-w-sm text-sm leading-relaxed">Pengajuan sewa yang Anda kirim akan tercatat di halaman ini.</p>
            <Link :href="route('e-booking.catalog')" class="wp-btn wp-btn-primary wp-link-arrow mt-6 px-5 py-2.5 text-sm">
                Cari venue
                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
            </Link>
        </div>

        <section v-else aria-labelledby="history-list-title">
            <h2 id="history-list-title" class="sr-only">Daftar pesanan</h2>
            <ul class="space-y-3">
                <li v-for="item in bookings.data" :key="item.id">
                    <Link
                        :href="route('e-booking.bookings.show', item.id)"
                        class="group sb-card wp-tile grid gap-4 p-5 focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:gap-6"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold tracking-tight tabular-nums">{{ item.nomor }}</p>
                                <SbStatusBadge :status="item.status" audience="renter" />
                            </div>
                            <p class="text-foreground mt-1.5 truncate text-sm">
                                {{ item.venue?.name }}
                                <template v-if="item.areas?.length">
                                    <span class="text-muted-foreground"> · {{ item.areas.map((a) => a.name).join(', ') }}</span>
                                </template>
                            </p>
                            <p class="text-muted-foreground mt-1 inline-flex items-center gap-1.5 text-xs tabular-nums">
                                <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-3" aria-hidden="true" />
                                {{ item.starts_at }} — {{ item.ends_at }}
                            </p>
                        </div>
                        <div
                            class="flex items-center justify-between gap-4 border-t border-(--wp-hairline) pt-3 sm:flex-col sm:items-end sm:border-0 sm:pt-0"
                        >
                            <p class="text-base font-semibold tabular-nums">{{ formatRupiah(item.grand_total) }}</p>
                            <span class="wp-link-arrow inline-flex items-center gap-1.5 text-xs font-semibold text-(--wp-accent-strong)">
                                Lihat detail
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                            </span>
                        </div>
                    </Link>
                </li>
            </ul>
        </section>

        <nav v-if="bookings.last_page > 1" class="mt-8 flex flex-wrap items-center justify-center gap-2" aria-label="Halaman pesanan">
            <template v-for="(link, idx) in pageLinks" :key="idx">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="sb-chip min-w-9 justify-center tabular-nums focus-visible:outline-none"
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <span v-html="link.label" />
                </Link>
                <span v-else class="text-muted-foreground px-3 py-1.5 text-sm" v-html="link.label" />
            </template>
        </nav>
    </EBookingLayout>
</template>
