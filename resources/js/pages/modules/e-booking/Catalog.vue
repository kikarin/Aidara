<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import { Skeleton } from '@/components/ui/skeleton';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { vReveal } from '@/lib/publicMotion';
import { buildWebPageSchema } from '@/lib/schema';
import { SEO_DEFAULT_KEYWORDS } from '@/lib/seo';
import { VENUE_KIND, venueKind, type VenueKind } from '@/lib/venueKind';
import type { BookingVenueSummary } from '@/types/booking';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowRight,
    faBasketball,
    faBuildingColumns,
    faCircleCheck,
    faFileSignature,
    faFlagCheckered,
    faFutbol,
    faLayerGroup,
    faMagnifyingGlass,
    faPersonSwimming,
    faTableTennisPaddleBall,
    faWallet,
    faXmark,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

library.add(
    faArrowRight,
    faBasketball,
    faBuildingColumns,
    faCircleCheck,
    faFileSignature,
    faFlagCheckered,
    faFutbol,
    faLayerGroup,
    faMagnifyingGlass,
    faPersonSwimming,
    faTableTennisPaddleBall,
    faWallet,
    faXmark,
);

const props = defineProps<{
    venues?: BookingVenueSummary[];
    branding: string;
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string } | null);
const pageUrl = computed(() => route('e-booking.catalog', undefined, true));
const siteBaseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const description =
    'Sewa stadion, gedung olahraga, lapangan tenis, kolam renang, dan sirkuit milik Pemkab Bogor yang dikelola UPT Dispora. Cek jadwal kosong, hitung biaya, lalu ajukan sewa secara daring.';

const pageSchema = computed(() => [
    buildWebPageSchema({
        baseUrl: siteBaseUrl.value,
        name: `${props.branding} — AIDARA`,
        description,
        url: pageUrl.value,
    }),
]);

type CatalogVenue = BookingVenueSummary & { kind: VenueKind; areaNames: string[] };

const allVenues = computed<CatalogVenue[]>(() =>
    (props.venues ?? []).map((venue) => ({ ...venue, kind: venueKind(venue.name), areaNames: venue.area_names ?? [] })),
);

const stats = computed(() => ({
    venues: allVenues.value.length,
    areas: allVenues.value.reduce((sum, v) => sum + v.areas_count, 0),
    tarifs: allVenues.value.reduce((sum, v) => sum + (v.tarifs_count ?? 0), 0),
}));

const statItems = computed(() => [
    { label: 'venue aktif', value: stats.value.venues },
    { label: 'area bisa disewa', value: stats.value.areas },
    { label: 'pilihan tarif resmi', value: stats.value.tarifs },
]);

const kindCounts = computed(() => {
    const counts = new Map<VenueKind, number>();
    allVenues.value.forEach((v) => counts.set(v.kind, (counts.get(v.kind) ?? 0) + 1));

    return (Object.keys(VENUE_KIND) as VenueKind[]).filter((kind) => counts.has(kind)).map((kind) => ({ kind, count: counts.get(kind)! }));
});

const query = ref('');
const activeKind = ref<VenueKind | null>(null);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return allVenues.value.filter((venue) => {
        if (activeKind.value && venue.kind !== activeKind.value) return false;
        if (!q) return true;

        return [venue.name, ...venue.areaNames].some((text) => text.toLowerCase().includes(q));
    });
});

const isFiltering = computed(() => query.value.trim() !== '' || activeKind.value !== null);

const resetFilters = () => {
    query.value = '';
    activeKind.value = null;
};

const scrollToList = () => document.getElementById('daftar-venue')?.scrollIntoView({ behavior: 'smooth', block: 'start' });

const pickKind = (kind: VenueKind | null) => {
    activeKind.value = activeKind.value === kind ? null : kind;
    scrollToList();
};

const activeId = ref<number | null>(null);
const preview = computed(() => filtered.value.find((v) => v.id === activeId.value) ?? filtered.value[0] ?? null);

const extraAreas = (venue: CatalogVenue, shown: number) => Math.max(venue.areas_count - shown, 0);
const indexLabel = (index: number) => String(index + 1).padStart(2, '0');

/** Player spots on the tactics board, in its 1000×400 viewBox. */
const BOARD_SPOTS = [
    { x: 125, y: 290 },
    { x: 375, y: 110 },
    { x: 625, y: 290 },
    { x: 875, y: 110 },
];

const passRoute = ['M10 350 C60 350 80 290 125 290', 'S300 110 375 110', 'S550 290 625 290', 'S800 110 875 110', 'S960 40 995 40'].join(' ');

const notes = [
    {
        icon: 'building-columns',
        title: 'Tarif instansi dan umum berbeda',
        body: 'Biaya dihitung otomatis dari jadwal, area, dan jenis penyewa yang Anda pilih. Tidak ada biaya tersembunyi di luar rincian.',
    },
    {
        icon: 'circle-check',
        title: 'Ditinjau petugas UPT Dispora',
        body: 'Setiap pengajuan diperiksa sebelum disetujui. Perkembangannya bisa dipantau dari menu Pesanan saya.',
    },
    {
        icon: 'wallet',
        title: 'Bayar setelah disetujui',
        body: 'Tagihan dan batas waktu pembayaran muncul setelah pengajuan disetujui. Bukti transfer diunggah dari akun Anda.',
    },
    {
        icon: 'file-signature',
        title: 'Surat izin dalam format PDF',
        body: 'Setelah pembayaran dikonfirmasi, surat izin pemakaian bisa diunduh langsung dari halaman pesanan.',
    },
] as const;
</script>

<template>
    <SeoHead :title="branding" :description="description" :keywords="SEO_DEFAULT_KEYWORDS" :canonical="pageUrl" :schema="pageSchema" />

    <EBookingLayout active="catalog">
        <!-- Hero -->
        <section aria-labelledby="catalog-hero-title" class="sb-card relative isolate overflow-hidden px-6 py-10 sm:px-10 lg:px-12 lg:py-14">
            <div class="sb-mow absolute inset-0 -z-20" aria-hidden="true"></div>
            <svg
                class="sb-draw pointer-events-none absolute inset-0 -z-10 size-full text-(--wp-accent)"
                viewBox="0 0 1200 640"
                preserveAspectRatio="xMidYMid slice"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                aria-hidden="true"
            >
                <rect x="40" y="40" width="1120" height="560" rx="4" pathLength="1" />
                <path d="M600 40v560" pathLength="1" />
                <circle cx="600" cy="320" r="96" pathLength="1" />
                <path d="M40 170h180v300H40M40 245h66v150H40" pathLength="1" />
                <path d="M220 262a96 96 0 0 1 0 116" pathLength="1" />
                <path d="M1160 170H980v300h180M1160 245h-66v150h66" pathLength="1" />
                <path d="M980 262a96 96 0 0 0 0 116" pathLength="1" />
                <path d="M40 58a18 18 0 0 0 18-18M1142 40a18 18 0 0 0 18 18M58 600a18 18 0 0 0-18-18M1160 582a18 18 0 0 0-18 18" pathLength="1" />
                <g class="sb-draw-dots" fill="currentColor" stroke="none">
                    <circle cx="600" cy="320" r="5" />
                    <circle cx="150" cy="320" r="4" />
                    <circle cx="1050" cy="320" r="4" />
                </g>
            </svg>

            <div class="grid gap-10 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)] lg:items-center lg:gap-14">
                <div>
                    <div class="flex items-center gap-3">
                        <SiBolaMark class="size-10" />
                        <p class="text-muted-foreground text-sm leading-tight">
                            <span class="text-foreground block font-semibold">{{ branding }}</span>
                            Booking venue UPT Dispora Kabupaten Bogor
                        </p>
                    </div>

                    <h1 id="catalog-hero-title" class="mt-8 text-4xl leading-[1.05] font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                        Stadion, gedung, kolam, sampai sirkuit.
                        <span class="text-(--wp-accent)">Ajukan sewanya dari sini.</span>
                    </h1>
                    <p class="text-muted-foreground mt-6 max-w-xl text-base leading-relaxed">
                        Semua venue di halaman ini milik Pemerintah Kabupaten Bogor dan dikelola UPT Dispora. Cek jadwal kosong, lihat rincian biaya,
                        lalu kirim pengajuan tanpa perlu datang ke kantor.
                    </p>

                    <form class="mt-8 max-w-xl" role="search" @submit.prevent="scrollToList">
                        <label for="venue-search" class="sr-only">Cari venue atau area</label>
                        <div class="relative">
                            <FontAwesomeIcon
                                :icon="['fas', 'magnifying-glass']"
                                class="text-muted-foreground pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2"
                                aria-hidden="true"
                            />
                            <input
                                id="venue-search"
                                v-model="query"
                                type="search"
                                class="sb-input h-13 rounded-2xl pr-28 pl-11 text-base"
                                placeholder="Cari: kolam, lintasan atletik, paddock…"
                                autocomplete="off"
                            />
                            <button type="submit" class="wp-btn wp-btn-primary absolute top-1/2 right-1.5 -translate-y-1/2 px-4 py-2 text-sm">
                                Cari
                            </button>
                        </div>
                    </form>

                    <dl class="mt-8 flex flex-wrap gap-x-10 gap-y-4">
                        <div v-for="item in statItems" :key="item.label" class="flex flex-col-reverse">
                            <dt class="text-muted-foreground text-sm">{{ item.label }}</dt>
                            <dd class="text-3xl font-bold tracking-tight tabular-nums">
                                <template v-if="venues">{{ item.value }}</template>
                                <Skeleton v-else class="h-9 w-12 rounded-lg" />
                            </dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <h2 class="text-muted-foreground mb-3 text-sm font-medium">Jelajahi menurut jenis</h2>
                    <div v-if="venues" class="grid grid-cols-2 gap-3">
                        <button
                            type="button"
                            class="group col-span-2 flex items-center justify-between gap-4 rounded-2xl px-5 py-4 text-left transition duration-300"
                            :class="
                                activeKind === null
                                    ? 'bg-(--wp-accent) text-(--wp-accent-contrast) shadow-[0_14px_30px_-16px_var(--wp-shadow)]'
                                    : 'wp-glass hover:-translate-y-0.5'
                            "
                            :aria-pressed="activeKind === null"
                            @click="pickKind(null)"
                        >
                            <span>
                                <span class="block text-base font-semibold">Semua venue</span>
                                <span class="block text-sm opacity-75">{{ stats.venues }} venue · {{ stats.areas }} area</span>
                            </span>
                            <FontAwesomeIcon
                                :icon="['fas', 'arrow-right']"
                                class="size-4 transition-transform duration-300 group-hover:translate-x-1"
                                aria-hidden="true"
                            />
                        </button>
                        <button
                            v-for="item in kindCounts"
                            :key="item.kind"
                            type="button"
                            class="group flex flex-col items-start gap-4 rounded-2xl p-4 text-left transition duration-300"
                            :class="
                                activeKind === item.kind
                                    ? 'bg-(--wp-accent) text-(--wp-accent-contrast) shadow-[0_14px_30px_-16px_var(--wp-shadow)]'
                                    : 'wp-glass hover:-translate-y-0.5'
                            "
                            :aria-pressed="activeKind === item.kind"
                            @click="pickKind(item.kind)"
                        >
                            <span
                                class="grid size-10 place-items-center rounded-xl transition-transform duration-300 group-hover:-rotate-6"
                                :class="activeKind === item.kind ? 'bg-(--wp-accent-contrast)/15' : 'bg-(--wp-accent-soft) text-(--wp-accent)'"
                            >
                                <FontAwesomeIcon :icon="['fas', VENUE_KIND[item.kind].icon]" class="size-4.5" aria-hidden="true" />
                            </span>
                            <span>
                                <span class="block text-sm leading-snug font-semibold">{{ VENUE_KIND[item.kind].label }}</span>
                                <span class="block text-xs tabular-nums opacity-70">{{ item.count }} venue</span>
                            </span>
                        </button>
                    </div>
                    <div v-else class="grid grid-cols-2 gap-3" aria-hidden="true">
                        <Skeleton class="col-span-2 h-19 rounded-2xl" />
                        <Skeleton v-for="n in 4" :key="n" class="h-32 rounded-2xl" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Venue index -->
        <section id="daftar-venue" aria-labelledby="catalog-list-title" class="mt-16 scroll-mt-24 lg:mt-20">
            <div class="flex flex-col gap-3 border-b border-(--wp-hairline) pb-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="wp-eyebrow">Daftar venue</p>
                    <h2 id="catalog-list-title" class="mt-2 text-2xl font-bold tracking-tight sm:text-3xl">
                        {{ activeKind ? VENUE_KIND[activeKind].label : 'Semua venue' }}
                    </h2>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <p v-if="venues" class="text-muted-foreground tabular-nums" aria-live="polite">
                        {{ filtered.length }} dari {{ stats.venues }} venue
                    </p>
                    <button v-if="isFiltering" type="button" class="sb-chip" @click="resetFilters">
                        <FontAwesomeIcon :icon="['fas', 'xmark']" class="size-3" aria-hidden="true" />
                        Hapus filter
                    </button>
                </div>
            </div>

            <Deferred data="venues">
                <template #fallback>
                    <div class="grid gap-10 pt-2 lg:grid-cols-[minmax(0,1fr)_24rem]" aria-busy="true" aria-label="Memuat daftar venue">
                        <div class="divide-y divide-(--wp-hairline)">
                            <div v-for="n in 5" :key="n" class="flex items-center gap-5 py-6">
                                <Skeleton class="hidden h-6 w-8 md:block" />
                                <Skeleton class="h-16 w-20 shrink-0 rounded-xl" />
                                <div class="flex-1 space-y-2.5">
                                    <Skeleton class="h-3.5 w-28" />
                                    <Skeleton class="h-6 w-2/3" />
                                    <Skeleton class="h-4 w-1/2" />
                                </div>
                            </div>
                        </div>
                        <Skeleton class="hidden h-[30rem] rounded-3xl lg:block" />
                    </div>
                </template>

                <div v-if="filtered.length > 0" class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_24rem]">
                    <ol class="divide-y divide-(--wp-hairline)">
                        <li v-for="(venue, index) in filtered" :key="venue.id" v-reveal="Math.min(index, 5) * 50">
                            <Link
                                :href="route('e-booking.venues.show', venue.id)"
                                class="group relative grid grid-cols-[4.5rem_minmax(0,1fr)_auto] items-center gap-4 rounded-2xl py-6 transition-colors duration-300 focus-visible:outline-none sm:grid-cols-[5.5rem_minmax(0,1fr)_auto] md:grid-cols-[2.5rem_6rem_minmax(0,1fr)_auto] md:gap-6"
                                :class="preview?.id === venue.id ? 'lg:bg-(--wp-accent-soft)' : ''"
                                @pointerenter="activeId = venue.id"
                                @focus="activeId = venue.id"
                            >
                                <span
                                    class="hidden pl-3 font-mono text-sm tabular-nums transition-colors md:block"
                                    :class="preview?.id === venue.id ? 'text-(--wp-accent)' : 'text-muted-foreground'"
                                    aria-hidden="true"
                                >
                                    {{ indexLabel(index) }}
                                </span>

                                <span class="bg-muted relative block aspect-[4/3] overflow-hidden rounded-xl">
                                    <AppImage
                                        v-if="venue.cover_url"
                                        :src="venue.cover_url"
                                        alt=""
                                        class="size-full object-cover transition duration-500 group-hover:scale-105"
                                    />
                                    <span v-else class="grid size-full place-items-center text-(--wp-accent)">
                                        <FontAwesomeIcon :icon="['fas', VENUE_KIND[venue.kind].icon]" class="size-5" aria-hidden="true" />
                                    </span>
                                </span>

                                <span class="min-w-0">
                                    <span class="text-muted-foreground inline-flex items-center gap-1.5 text-xs font-medium">
                                        <FontAwesomeIcon :icon="['fas', VENUE_KIND[venue.kind].icon]" class="size-3" aria-hidden="true" />
                                        {{ VENUE_KIND[venue.kind].label }}
                                    </span>
                                    <span class="mt-1 block text-lg leading-snug font-semibold tracking-tight text-balance sm:text-xl lg:text-2xl">
                                        {{ venue.name }}
                                    </span>
                                    <span v-if="venue.areaNames.length" class="mt-2.5 hidden flex-wrap gap-1.5 sm:flex">
                                        <span
                                            v-for="area in venue.areaNames.slice(0, 3)"
                                            :key="area"
                                            class="bg-muted text-muted-foreground max-w-[14rem] truncate rounded-md px-2 py-0.5 text-xs"
                                        >
                                            {{ area }}
                                        </span>
                                        <span v-if="extraAreas(venue, 3) > 0" class="text-muted-foreground px-1 py-0.5 text-xs tabular-nums">
                                            +{{ extraAreas(venue, 3) }} area
                                        </span>
                                    </span>
                                    <span v-else class="text-muted-foreground mt-1 block text-sm tabular-nums">{{ venue.areas_count }} area</span>
                                </span>

                                <span
                                    class="mr-3 grid size-10 place-items-center rounded-full ring-1 ring-(--wp-hairline) transition duration-300 group-hover:bg-(--wp-accent) group-hover:text-(--wp-accent-contrast) group-hover:ring-transparent group-focus-visible:bg-(--wp-accent) group-focus-visible:text-(--wp-accent-contrast)"
                                    aria-hidden="true"
                                >
                                    <FontAwesomeIcon
                                        :icon="['fas', 'arrow-right']"
                                        class="size-3.5 transition-transform duration-300 group-hover:translate-x-0.5"
                                    />
                                </span>
                            </Link>
                        </li>
                    </ol>

                    <aside v-if="preview" class="hidden lg:block" aria-label="Pratinjau venue">
                        <div class="sb-card sticky top-24 p-3">
                            <div class="bg-muted relative aspect-[4/3] overflow-hidden rounded-2xl">
                                <Transition
                                    mode="out-in"
                                    enter-active-class="transition duration-300 ease-out"
                                    enter-from-class="opacity-0 scale-[1.02]"
                                    leave-active-class="transition duration-150 ease-in"
                                    leave-to-class="opacity-0"
                                >
                                    <AppImage
                                        v-if="preview.cover_url"
                                        :key="`img-${preview.id}`"
                                        :src="preview.cover_url"
                                        :alt="preview.name"
                                        class="size-full object-cover"
                                    />
                                    <div
                                        v-else
                                        :key="`ph-${preview.id}`"
                                        class="grid size-full place-items-center bg-(--wp-accent-soft) text-(--wp-accent)"
                                    >
                                        <FontAwesomeIcon :icon="['fas', VENUE_KIND[preview.kind].icon]" class="size-10" aria-hidden="true" />
                                    </div>
                                </Transition>
                                <span
                                    class="wp-glass absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                                >
                                    <FontAwesomeIcon
                                        :icon="['fas', VENUE_KIND[preview.kind].icon]"
                                        class="size-3 text-(--wp-accent)"
                                        aria-hidden="true"
                                    />
                                    {{ VENUE_KIND[preview.kind].label }}
                                </span>
                            </div>

                            <div class="px-3 pt-5 pb-3">
                                <h3 class="text-xl font-bold tracking-tight text-balance">{{ preview.name }}</h3>
                                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div class="sb-card-muted px-3.5 py-3">
                                        <dt class="text-muted-foreground text-xs">Area</dt>
                                        <dd class="mt-0.5 text-lg font-bold tabular-nums">{{ preview.areas_count }}</dd>
                                    </div>
                                    <div class="sb-card-muted px-3.5 py-3">
                                        <dt class="text-muted-foreground text-xs">Pilihan tarif</dt>
                                        <dd class="mt-0.5 text-lg font-bold tabular-nums">{{ preview.tarifs_count ?? '—' }}</dd>
                                    </div>
                                </dl>
                                <ul v-if="preview.areaNames.length" class="mt-4 space-y-1.5 text-sm">
                                    <li v-for="area in preview.areaNames.slice(0, 5)" :key="area" class="flex items-start gap-2">
                                        <span class="mt-2 size-1.5 shrink-0 rounded-full bg-(--wp-accent)" aria-hidden="true"></span>
                                        <span class="line-clamp-1">{{ area }}</span>
                                    </li>
                                    <li v-if="extraAreas(preview, 5) > 0" class="text-muted-foreground pl-3.5 text-xs tabular-nums">
                                        dan {{ extraAreas(preview, 5) }} area lainnya
                                    </li>
                                </ul>
                                <Link
                                    :href="route('e-booking.venues.show', preview.id)"
                                    class="wp-btn wp-btn-primary wp-link-arrow mt-6 w-full justify-center px-5 py-3 text-sm"
                                >
                                    Cek jadwal dan tarif
                                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                                </Link>
                            </div>
                        </div>
                    </aside>
                </div>

                <div v-else-if="venues && venues.length > 0" class="flex flex-col items-center px-6 py-16 text-center">
                    <span class="wp-icon size-12">
                        <FontAwesomeIcon :icon="['fas', 'magnifying-glass']" class="size-5" aria-hidden="true" />
                    </span>
                    <h3 class="mt-4 text-lg font-semibold tracking-tight">Tidak ada venue yang cocok</h3>
                    <p class="text-muted-foreground mt-1 max-w-sm text-sm leading-relaxed">
                        Coba kata kunci lain, misalnya nama area seperti "kolam" atau "lintasan", atau tampilkan semua venue.
                    </p>
                    <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="resetFilters">Tampilkan semua venue</button>
                </div>

                <div v-else-if="venues" class="flex flex-col items-center px-6 py-16 text-center">
                    <span class="wp-icon size-12">
                        <FontAwesomeIcon :icon="['fas', 'layer-group']" class="size-5" aria-hidden="true" />
                    </span>
                    <h3 class="mt-4 text-lg font-semibold tracking-tight">Belum ada venue yang dibuka</h3>
                    <p class="text-muted-foreground mt-1 max-w-sm text-sm leading-relaxed">
                        Venue akan tampil di sini setelah dibuka untuk umum oleh UPT Dispora.
                    </p>
                </div>
            </Deferred>
        </section>

        <!-- Before you apply: tactics board -->
        <section aria-labelledby="catalog-notes-title" class="sb-card relative isolate mt-20 overflow-hidden px-6 py-10 sm:px-10 lg:px-12 lg:py-14">
            <div class="sb-mow absolute inset-0 -z-20" aria-hidden="true"></div>
            <svg
                class="pointer-events-none absolute inset-0 -z-10 size-full text-(--wp-accent) opacity-60"
                viewBox="0 0 1200 640"
                preserveAspectRatio="xMidYMid slice"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-opacity="0.22"
                aria-hidden="true"
            >
                <path d="M0 40h1160v560H0" />
                <path d="M1160 170H980v300h180M1160 245h-66v150h66M980 262a96 96 0 0 0 0 116" />
                <path d="M0 224a96 96 0 0 1 0 192" />
            </svg>

            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-xl">
                    <p class="wp-eyebrow">Sebelum mengajukan</p>
                    <h2 id="catalog-notes-title" class="mt-3 text-3xl font-bold tracking-tight text-balance sm:text-4xl">
                        Empat operan dari pengajuan sampai surat izin.
                    </h2>
                    <p class="text-muted-foreground mt-4 text-sm leading-relaxed sm:text-base">
                        Jadwal dan biaya bisa dicek tanpa akun. Akun penyewa baru dibutuhkan saat Anda mengirim pengajuan.
                    </p>
                </div>
                <div v-if="!bookingAuth" class="flex flex-wrap gap-3">
                    <Link :href="route('e-booking.register')" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm">Buat akun penyewa</Link>
                    <Link :href="route('e-booking.login')" class="wp-btn wp-btn-quiet bg-card px-5 py-2.5 text-sm">Sudah punya akun</Link>
                </div>
                <Link v-else :href="route('e-booking.bookings.index')" class="wp-btn wp-btn-quiet wp-link-arrow bg-card px-5 py-2.5 text-sm">
                    Lihat pesanan saya
                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                </Link>
            </div>

            <div v-reveal class="sb-board relative mt-12 lg:mt-6 lg:aspect-[1000/400]">
                <svg class="absolute inset-0 hidden size-full lg:block" viewBox="0 0 1000 400" fill="none" aria-hidden="true">
                    <path
                        id="sb-pass-route"
                        class="sb-pass text-(--wp-accent)"
                        :d="passRoute"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                    <g class="sb-run-ball">
                        <circle r="9" class="fill-card" stroke="currentColor" stroke-width="1.5" />
                        <path d="M0-4.5l4.3 3.1-1.6 5h-5.4l-1.6-5z" fill="currentColor" />
                        <animateMotion dur="7s" repeatCount="indefinite" rotate="auto" keyPoints="0;1" keyTimes="0;1" calcMode="linear">
                            <mpath href="#sb-pass-route" />
                        </animateMotion>
                    </g>
                </svg>

                <ol class="relative space-y-8 border-l-2 border-dashed border-(--wp-accent)/40 pl-8 lg:static lg:space-y-0 lg:border-0 lg:pl-0">
                    <li
                        v-for="(note, index) in notes"
                        :key="note.title"
                        class="relative lg:absolute lg:top-(--spot-y) lg:left-(--spot-x) lg:w-52 lg:-translate-x-1/2 xl:w-60"
                        :class="index % 2 === 0 ? 'lg:-translate-y-[calc(100%-1.75rem)]' : 'lg:-translate-y-7'"
                        :style="{ '--spot-x': `${BOARD_SPOTS[index].x / 10}%`, '--spot-y': `${BOARD_SPOTS[index].y / 4}%` }"
                    >
                        <div class="flex flex-col gap-3" :class="index % 2 === 0 ? 'lg:flex-col-reverse' : ''">
                            <span
                                class="absolute top-0 -left-[3.25rem] grid size-10 place-items-center rounded-full bg-(--wp-accent) text-sm font-bold text-(--wp-accent-contrast) tabular-nums ring-4 ring-(--card) lg:static lg:mx-auto lg:size-14 lg:text-lg lg:shadow-[0_12px_28px_-10px_var(--wp-shadow)]"
                                aria-hidden="true"
                            >
                                {{ index + 1 }}
                            </span>
                            <div class="wp-glass rounded-2xl p-4 lg:text-center">
                                <p class="inline-flex items-center gap-1.5 text-xs font-semibold text-(--wp-accent-strong)">
                                    <FontAwesomeIcon :icon="['fas', note.icon]" class="size-3" aria-hidden="true" />
                                    Operan {{ index + 1 }}
                                </p>
                                <h3 class="mt-1 font-semibold tracking-tight">{{ note.title }}</h3>
                                <p class="text-muted-foreground mt-1.5 text-sm leading-relaxed">{{ note.body }}</p>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </section>
    </EBookingLayout>
</template>
