<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import SbStatusBadge from '@/components/sibola/SbStatusBadge.vue';
import { Skeleton } from '@/components/ui/skeleton';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { formatRupiah } from '@/lib/bookingStatus';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowRight,
    faCalendarXmark,
    faCircleCheck,
    faCircleExclamation,
    faGear,
    faInbox,
    faMagnifyingGlass,
    faShieldHalved,
    faWallet,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

library.add(faArrowRight, faCalendarXmark, faCircleCheck, faCircleExclamation, faGear, faInbox, faMagnifyingGlass, faShieldHalved, faWallet);

type DashboardStats = {
    pending_approval: number;
    awaiting_payment: number;
    awaiting_verification: number;
    confirmed_today: number;
    needs_attention: number;
};

type RecentItem = {
    id: number;
    nomor: string;
    status: string;
    venue: string | null;
    penyewa: string | null;
    starts_at: string | null;
    ends_at: string | null;
    grand_total: number;
};

defineProps<{
    stats?: DashboardStats;
    recent?: RecentItem[];
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) {
        return 'Selamat pagi';
    }
    if (hour < 15) {
        return 'Selamat siang';
    }
    if (hour < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
});

const todayLabel = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());

const formatSchedule = (starts: string | null, ends: string | null) => {
    if (!starts) {
        return 'Jadwal belum diisi';
    }
    if (!ends) {
        return starts;
    }
    const sameDay = starts.slice(0, 10) === ends.slice(0, 10);
    if (sameDay) {
        return `${starts} – ${ends.slice(11, 16)}`;
    }

    return `${starts} – ${ends}`;
};

const statCards = (stats: DashboardStats) => [
    {
        key: 'pending',
        label: 'Perlu ditinjau',
        value: stats.pending_approval,
        hint: 'Pengajuan baru / klarifikasi',
        href: route('e-booking.admin.bookings.index', { tab: 'review' }),
        icon: 'magnifying-glass',
        flagged: stats.pending_approval > 0,
    },
    {
        key: 'pay',
        label: 'Menunggu bayar',
        value: stats.awaiting_payment,
        hint: 'Sudah disetujui, belum lunas',
        href: route('e-booking.admin.bookings.index', { tab: 'payment' }),
        icon: 'wallet',
        flagged: false,
    },
    {
        key: 'verify',
        label: 'Bukti perlu dicek',
        value: stats.awaiting_verification,
        hint: 'Transfer menunggu verifikasi',
        href: route('e-booking.admin.bookings.index', { tab: 'verify' }),
        icon: 'shield-halved',
        flagged: stats.awaiting_verification > 0,
    },
    {
        key: 'today',
        label: 'Dikonfirmasi hari ini',
        value: stats.confirmed_today,
        hint: 'Pesanan yang sudah beres',
        href: route('e-booking.admin.bookings.index', { tab: 'all', status: 'confirmed' }),
        icon: 'circle-check',
        flagged: false,
    },
];

const focusRing = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-(--wp-accent)';
</script>

<template>
    <SeoHead title="Ringkasan Pengelola" />

    <AdminLayout active="dashboard">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-muted-foreground text-sm">{{ greeting }}, {{ bookingAuth?.name || 'Pengelola' }}</p>
                <h1 class="text-foreground mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Ringkasan kerja hari ini</h1>
                <p class="text-muted-foreground mt-1.5 text-sm">
                    <time>{{ todayLabel }}</time>
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link :href="route('e-booking.admin.closures.index')" class="wp-btn wp-btn-quiet px-4 py-2 text-sm">
                    <FontAwesomeIcon :icon="['fas', 'calendar-xmark']" class="size-3.5" aria-hidden="true" />
                    Blok jadwal
                </Link>
                <Link :href="route('e-booking.admin.bookings.index')" class="wp-btn wp-btn-primary wp-link-arrow px-4 py-2 text-sm">
                    Semua pengajuan
                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                </Link>
            </div>
        </header>

        <Deferred :data="['stats', 'recent']">
            <template #fallback>
                <div class="mt-8 space-y-6" aria-busy="true" aria-label="Memuat ringkasan">
                    <div class="sb-card grid divide-y divide-(--wp-hairline) overflow-hidden sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                        <div v-for="n in 4" :key="n" class="space-y-3 p-5">
                            <Skeleton class="h-3.5 w-28" />
                            <Skeleton class="h-8 w-12" />
                            <Skeleton class="h-3 w-36 max-w-full" />
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <div class="sb-card overflow-hidden">
                            <div class="flex items-center justify-between p-5">
                                <div class="space-y-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-3 w-52" />
                                </div>
                                <Skeleton class="h-4 w-16" />
                            </div>
                            <div class="divide-y divide-(--wp-hairline) border-t border-(--wp-hairline)">
                                <div v-for="n in 5" :key="n" class="flex items-center justify-between gap-4 px-5 py-4">
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-2">
                                            <Skeleton class="h-4 w-28" />
                                            <Skeleton class="h-5 w-24 rounded-lg" />
                                        </div>
                                        <Skeleton class="h-3 w-56 max-w-full" />
                                        <Skeleton class="h-3 w-40" />
                                    </div>
                                    <Skeleton class="h-4 w-20" />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="sb-card space-y-3 p-5">
                                <Skeleton class="h-4 w-24" />
                                <Skeleton v-for="n in 3" :key="n" class="h-11 w-full rounded-xl" />
                            </div>
                            <div class="sb-card space-y-2 p-5">
                                <Skeleton class="h-4 w-28" />
                                <Skeleton class="h-3 w-full" />
                                <Skeleton class="h-3 w-4/5" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="stats && recent">
                <div class="mt-8 space-y-6">
                    <section aria-labelledby="kpi-heading" class="sb-card overflow-hidden">
                        <h2 id="kpi-heading" class="sr-only">Angka utama</h2>
                        <div class="grid divide-y divide-(--wp-hairline) sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                            <Link
                                v-for="card in statCards(stats)"
                                :key="card.key"
                                :href="card.href"
                                class="group hover:bg-muted/50 block p-5 transition-colors"
                                :class="focusRing"
                            >
                                <p class="text-muted-foreground flex items-center gap-2 text-sm">
                                    <FontAwesomeIcon :icon="['fas', card.icon]" class="size-3.5" aria-hidden="true" />
                                    {{ card.label }}
                                    <span v-if="card.flagged" class="size-1.5 rounded-full bg-(--sb-warning)" aria-hidden="true"></span>
                                </p>
                                <p class="text-foreground mt-2 text-3xl font-semibold tracking-tight tabular-nums">{{ card.value }}</p>
                                <p class="text-muted-foreground mt-1 text-xs">{{ card.hint }}</p>
                            </Link>
                        </div>
                    </section>

                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <section aria-labelledby="queue-heading" class="sb-card overflow-hidden">
                            <div class="flex items-start justify-between gap-3 p-5">
                                <div>
                                    <h2 id="queue-heading" class="text-foreground text-base font-semibold tracking-tight">Perlu tindakan</h2>
                                    <p class="text-muted-foreground mt-0.5 text-sm">Pengajuan yang masih perlu diproses</p>
                                </div>
                                <Link
                                    :href="route('e-booking.admin.bookings.index')"
                                    class="wp-link-arrow inline-flex items-center gap-1.5 rounded-md text-sm font-medium text-(--wp-accent) hover:text-(--wp-accent-strong) focus-visible:ring-2 focus-visible:ring-(--wp-accent) focus-visible:outline-none"
                                >
                                    Semua
                                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                                </Link>
                            </div>

                            <div class="px-5 pb-4">
                                <div v-if="stats.needs_attention > 0" class="sb-callout sb-tone-warning flex-wrap items-center justify-between">
                                    <p class="flex items-center gap-2.5">
                                        <FontAwesomeIcon :icon="['fas', 'circle-exclamation']" class="size-4 shrink-0" aria-hidden="true" />
                                        <span>
                                            <span class="font-semibold tabular-nums">{{ stats.needs_attention }} item perlu perhatian Anda.</span>
                                            <span class="hidden sm:inline">
                                                Ada pengajuan untuk ditinjau atau bukti transfer yang menunggu dicek.</span
                                            >
                                        </span>
                                    </p>
                                    <Link
                                        :href="route('e-booking.admin.bookings.index', { tab: 'review' })"
                                        class="wp-link-arrow inline-flex shrink-0 items-center gap-1.5 rounded-md font-semibold underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-current focus-visible:outline-none"
                                    >
                                        Kerjakan sekarang
                                        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3" aria-hidden="true" />
                                    </Link>
                                </div>
                                <div v-else class="sb-callout sb-tone-success items-center">
                                    <FontAwesomeIcon :icon="['fas', 'circle-check']" class="size-4 shrink-0" aria-hidden="true" />
                                    <p>
                                        <span class="font-semibold">Antrian kritis kosong.</span>
                                        Tidak ada pengajuan mendesak. Anda bisa cek jadwal blokir atau pengaturan.
                                    </p>
                                </div>
                            </div>

                            <div v-if="recent.length === 0" class="flex flex-col items-center border-t border-(--wp-hairline) px-6 py-14 text-center">
                                <span class="wp-icon size-12">
                                    <FontAwesomeIcon :icon="['fas', 'inbox']" class="size-5" aria-hidden="true" />
                                </span>
                                <h3 class="text-foreground mt-4 text-base font-semibold tracking-tight">Belum ada antrian</h3>
                                <p class="text-muted-foreground mt-1 max-w-sm text-sm">
                                    Saat penyewa mengirim pengajuan baru, daftarnya akan muncul di sini.
                                </p>
                                <Link :href="route('e-booking.admin.bookings.index')" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm">
                                    Buka semua pengajuan
                                </Link>
                            </div>

                            <ul v-else class="divide-y divide-(--wp-hairline) border-t border-(--wp-hairline)">
                                <li v-for="item in recent" :key="item.id">
                                    <Link
                                        :href="route('e-booking.admin.bookings.show', item.id)"
                                        class="group hover:bg-muted/50 flex items-center gap-4 px-5 py-4 transition-colors"
                                        :class="focusRing"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-foreground font-semibold tabular-nums">{{ item.nomor }}</span>
                                                <SbStatusBadge :status="item.status" audience="admin" />
                                            </div>
                                            <p class="text-muted-foreground mt-1 truncate text-sm">
                                                {{ item.penyewa || 'Penyewa' }} · {{ item.venue || 'Tempat' }}
                                            </p>
                                            <p class="text-muted-foreground mt-0.5 text-xs tabular-nums">
                                                {{ formatSchedule(item.starts_at, item.ends_at) }}
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <span class="text-foreground text-sm font-semibold tabular-nums">{{
                                                formatRupiah(item.grand_total)
                                            }}</span>
                                            <span class="wp-link-arrow text-muted-foreground group-hover:text-(--wp-accent)">
                                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                                                <span class="sr-only">Buka detail</span>
                                            </span>
                                        </div>
                                    </Link>
                                </li>
                            </ul>
                        </section>

                        <aside class="space-y-6">
                            <section aria-labelledby="shortcut-heading" class="sb-card p-5">
                                <h2 id="shortcut-heading" class="text-foreground text-base font-semibold tracking-tight">Aksi cepat</h2>
                                <p class="text-muted-foreground mt-0.5 text-sm">Jalan pintas yang sering dipakai</p>

                                <nav class="-mx-2 mt-4 space-y-0.5" aria-label="Aksi cepat">
                                    <Link
                                        :href="route('e-booking.admin.bookings.index')"
                                        class="group hover:bg-muted/60 flex items-center gap-3 rounded-xl px-2 py-2 text-sm transition-colors"
                                        :class="focusRing"
                                    >
                                        <span class="wp-icon size-8 rounded-lg">
                                            <FontAwesomeIcon :icon="['fas', 'magnifying-glass']" class="size-3.5" aria-hidden="true" />
                                        </span>
                                        <span class="text-foreground flex-1 font-medium">Tinjau pengajuan</span>
                                        <FontAwesomeIcon
                                            :icon="['fas', 'arrow-right']"
                                            class="text-muted-foreground size-3 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                    <Link
                                        :href="route('e-booking.admin.closures.index')"
                                        class="group hover:bg-muted/60 flex items-center gap-3 rounded-xl px-2 py-2 text-sm transition-colors"
                                        :class="focusRing"
                                    >
                                        <span class="wp-icon size-8 rounded-lg">
                                            <FontAwesomeIcon :icon="['fas', 'calendar-xmark']" class="size-3.5" aria-hidden="true" />
                                        </span>
                                        <span class="text-foreground flex-1 font-medium">Blok / buka jadwal</span>
                                        <FontAwesomeIcon
                                            :icon="['fas', 'arrow-right']"
                                            class="text-muted-foreground size-3 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                    <Link
                                        :href="route('e-booking.admin.settings')"
                                        class="group hover:bg-muted/60 flex items-center gap-3 rounded-xl px-2 py-2 text-sm transition-colors"
                                        :class="focusRing"
                                    >
                                        <span class="wp-icon size-8 rounded-lg">
                                            <FontAwesomeIcon :icon="['fas', 'gear']" class="size-3.5" aria-hidden="true" />
                                        </span>
                                        <span class="text-foreground flex-1 font-medium">Pengaturan & rekening</span>
                                        <FontAwesomeIcon
                                            :icon="['fas', 'arrow-right']"
                                            class="text-muted-foreground size-3 transition-transform group-hover:translate-x-0.5"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </nav>
                            </section>

                            <section aria-labelledby="tips-heading" class="sb-card-muted p-5">
                                <h2 id="tips-heading" class="text-foreground text-base font-semibold tracking-tight">Tips kerja cepat</h2>
                                <ul class="text-muted-foreground mt-3 space-y-2.5 text-sm leading-6">
                                    <li class="flex gap-2.5">
                                        <span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-(--wp-accent)" aria-hidden="true"></span>
                                        <span>
                                            Prioritaskan kartu <strong class="text-foreground font-semibold">Perlu ditinjau</strong> dan
                                            <strong class="text-foreground font-semibold">Bukti perlu dicek</strong>.
                                        </span>
                                    </li>
                                    <li class="flex gap-2.5">
                                        <span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-(--wp-accent)" aria-hidden="true"></span>
                                        <span>Blok jadwal lebih dulu jika ada maintenance atau event internal.</span>
                                    </li>
                                    <li class="flex gap-2.5">
                                        <span class="mt-2.5 size-1.5 shrink-0 rounded-full bg-(--wp-accent)" aria-hidden="true"></span>
                                        <span>Setelah bukti valid, verifikasi agar penyewa mendapat konfirmasi.</span>
                                    </li>
                                </ul>
                            </section>
                        </aside>
                    </div>
                </div>
            </template>
        </Deferred>
    </AdminLayout>
</template>
