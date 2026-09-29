<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import EventCard from '@/components/event/EventCard.vue';
import EventCoverPlaceholder from '@/components/event/EventCoverPlaceholder.vue';
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import PublicSiteLayout from '@/layouts/PublicSiteLayout.vue';
import { EVENT_PHASE_LABEL, eventDateRange, eventPhase, eventPhaseClass, type EventPhase } from '@/lib/publicEvent';
import { trackSpotlight, vReveal } from '@/lib/publicMotion';
import { buildBreadcrumbSchema, buildWebPageSchema } from '@/lib/schema';
import { SEO_DEFAULT_KEYWORDS, SEO_EVENTS_DESCRIPTION } from '@/lib/seo';
import type { PublicEventSummary } from '@/types/event';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRight, faCalendarDays, faLocationDot, faMagnifyingGlass, faXmark } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

library.add(faArrowRight, faCalendarDays, faLocationDot, faMagnifyingGlass, faXmark);

const props = defineProps<{
    events: PublicEventSummary[];
}>();

const page = usePage();
const pageUrl = computed(() => route('event.public.index', undefined, true));
const siteBaseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});
const pageSchema = computed(() => [
    buildWebPageSchema({
        baseUrl: siteBaseUrl.value,
        name: 'Event & Kegiatan — AIDARA',
        description: SEO_EVENTS_DESCRIPTION,
        url: pageUrl.value,
    }),
    buildBreadcrumbSchema([
        { name: 'Beranda', url: route('home', undefined, true) },
        { name: 'Event & Kegiatan', url: pageUrl.value },
    ]),
]);

type Filter = 'all' | 'active' | 'finished';

const filter = ref<Filter>('all');
const query = ref('');

const withPhase = computed(() => {
    const active: { event: PublicEventSummary; phase: EventPhase }[] = [];
    const finished: { event: PublicEventSummary; phase: EventPhase }[] = [];

    props.events.forEach((event) => {
        const phase = eventPhase(event);
        (phase === 'finished' ? finished : active).push({ event, phase });
    });

    active.sort((a, b) => (a.event.tanggal_mulai ?? '9999').localeCompare(b.event.tanggal_mulai ?? '9999'));

    return [...active, ...finished];
});

const counts = computed(() => ({
    all: withPhase.value.length,
    active: withPhase.value.filter((item) => item.phase !== 'finished').length,
    finished: withPhase.value.filter((item) => item.phase === 'finished').length,
}));

const filters: { key: Filter; label: string }[] = [
    { key: 'all', label: 'Semua' },
    { key: 'active', label: 'Akan datang & berlangsung' },
    { key: 'finished', label: 'Selesai' },
];

const normalizedQuery = computed(() => query.value.trim().toLowerCase());

const filtered = computed(() =>
    withPhase.value.filter(({ event, phase }) => {
        if (filter.value === 'active' && phase === 'finished') {
            return false;
        }

        if (filter.value === 'finished' && phase !== 'finished') {
            return false;
        }

        if (!normalizedQuery.value) {
            return true;
        }

        return [event.nama_event, event.lokasi, event.kategori_event_nama]
            .filter((field): field is string => Boolean(field))
            .some((field) => field.toLowerCase().includes(normalizedQuery.value));
    }),
);

const featured = computed(() => {
    if (filter.value === 'finished' || normalizedQuery.value) {
        return null;
    }

    const first = filtered.value[0];

    return first && first.phase !== 'finished' ? first : null;
});

const gridItems = computed(() => (featured.value ? filtered.value.slice(1) : filtered.value));

const resetFilters = () => {
    filter.value = 'all';
    query.value = '';
};
</script>

<template>
    <SeoHead
        title="Event & Kegiatan"
        :description="SEO_EVENTS_DESCRIPTION"
        :keywords="SEO_DEFAULT_KEYWORDS"
        :canonical="pageUrl"
        :schema="pageSchema"
    />

    <PublicSiteLayout current="event">
        <section class="relative isolate" aria-labelledby="event-heading">
            <div class="wp-hero-bg absolute inset-0 -z-10" aria-hidden="true"></div>

            <div class="mx-auto max-w-7xl px-6 pt-32 pb-14 lg:px-8 lg:pt-40 lg:pb-20">
                <nav class="wp-enter text-muted-foreground text-sm" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-2">
                        <li><Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link></li>
                        <li aria-hidden="true" class="text-muted-foreground/50">/</li>
                        <li class="text-foreground font-medium" aria-current="page">Event &amp; kegiatan</li>
                    </ol>
                </nav>

                <div class="mt-8 grid gap-10 lg:grid-cols-12 lg:items-end">
                    <div class="lg:col-span-7">
                        <h1
                            id="event-heading"
                            class="wp-enter text-[clamp(2.5rem,5.6vw,4.75rem)] leading-[0.98] font-bold tracking-[-0.045em] text-balance"
                            :style="{ '--enter-delay': '80ms' }"
                        >
                            Agenda olahraga <span class="text-(--wp-accent)">Kabupaten Bogor.</span>
                        </h1>
                        <p
                            class="wp-enter text-muted-foreground mt-6 max-w-[54ch] text-lg leading-relaxed text-pretty"
                            :style="{ '--enter-delay': '160ms' }"
                        >
                            Kejuaraan, festival, dan kegiatan olahraga yang diselenggarakan atau didukung Dispora Kabupaten Bogor.
                        </p>
                    </div>

                    <dl class="wp-enter grid grid-cols-3 gap-3 lg:col-span-5" :style="{ '--enter-delay': '240ms' }">
                        <div class="wp-glass rounded-2xl p-4">
                            <dt class="text-muted-foreground text-xs">Total event</dt>
                            <dd class="mt-1 text-3xl font-bold tracking-[-0.03em] tabular-nums">{{ counts.all }}</dd>
                        </div>
                        <div class="wp-glass rounded-2xl p-4">
                            <dt class="text-muted-foreground text-xs">Mendatang</dt>
                            <dd class="mt-1 text-3xl font-bold tracking-[-0.03em] text-(--wp-accent) tabular-nums">{{ counts.active }}</dd>
                        </div>
                        <div class="wp-glass rounded-2xl p-4">
                            <dt class="text-muted-foreground text-xs">Selesai</dt>
                            <dd class="mt-1 text-3xl font-bold tracking-[-0.03em] tabular-nums">{{ counts.finished }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-6 pb-24 lg:px-8 lg:pb-32" aria-label="Daftar event">
            <template v-if="events.length > 0">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Filter status event">
                        <button
                            v-for="item in filters"
                            :key="item.key"
                            type="button"
                            class="wp-btn px-4 py-2 text-sm"
                            :class="filter === item.key ? 'wp-btn-primary' : 'wp-btn-quiet font-medium'"
                            :aria-pressed="filter === item.key"
                            @click="filter = item.key"
                        >
                            {{ item.label }}
                            <span class="tabular-nums opacity-70">{{ counts[item.key] }}</span>
                        </button>
                    </div>

                    <label class="relative block w-full lg:w-80">
                        <span class="sr-only">Cari event</span>
                        <FontAwesomeIcon
                            :icon="['fas', 'magnifying-glass']"
                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-4 size-3.5 -translate-y-1/2"
                        />
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Cari nama event, lokasi, atau cabor"
                            class="bg-card placeholder:text-muted-foreground/70 w-full rounded-full py-2.5 pr-10 pl-10 text-sm shadow-[inset_0_0_0_1px_var(--wp-hairline)] transition-shadow outline-none focus:shadow-[inset_0_0_0_2px_var(--wp-accent)]"
                        />
                        <button
                            v-if="query"
                            type="button"
                            class="text-muted-foreground hover:text-foreground absolute top-1/2 right-3 flex size-6 -translate-y-1/2 items-center justify-center rounded-full"
                            aria-label="Hapus pencarian"
                            @click="query = ''"
                        >
                            <FontAwesomeIcon :icon="['fas', 'xmark']" class="size-3" />
                        </button>
                    </label>
                </div>

                <p class="text-muted-foreground mt-4 text-sm" aria-live="polite">
                    Menampilkan <span class="text-foreground font-semibold tabular-nums">{{ filtered.length }}</span> event
                    <template v-if="normalizedQuery"> untuk “{{ query.trim() }}”</template>
                </p>

                <Link
                    v-if="featured"
                    :href="route('event.public.show', { id: featured.event.id })"
                    class="wp-surface wp-tile group mt-8 grid overflow-hidden rounded-[2rem] p-2 lg:grid-cols-12"
                    @pointermove="trackSpotlight"
                >
                    <div class="relative aspect-[16/10] overflow-hidden rounded-[1.5rem] lg:col-span-7 lg:aspect-auto lg:min-h-[22rem]">
                        <AppImage
                            v-if="featured.event.foto_url"
                            :src="featured.event.foto_url"
                            :alt="`Poster ${featured.event.nama_event}`"
                            class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                        />
                        <EventCoverPlaceholder v-else class="absolute inset-0" />
                        <span class="absolute top-4 left-4 rounded-md px-2.5 py-1 text-xs font-semibold" :class="eventPhaseClass(featured.phase)">
                            {{ EVENT_PHASE_LABEL[featured.phase] }}
                        </span>
                    </div>
                    <div class="flex flex-col p-6 lg:col-span-5 lg:p-10">
                        <p class="wp-eyebrow">Event terdekat</p>
                        <p class="text-muted-foreground mt-6 text-sm font-medium">{{ featured.event.kategori_event_nama }}</p>
                        <h2 class="mt-2 text-3xl leading-[1.08] font-bold tracking-[-0.03em] text-balance lg:text-4xl">
                            {{ featured.event.nama_event }}
                        </h2>
                        <p v-if="featured.event.deskripsi_singkat" class="text-muted-foreground mt-4 line-clamp-3 leading-relaxed">
                            {{ featured.event.deskripsi_singkat }}
                        </p>
                        <ul class="mt-6 space-y-2 text-sm">
                            <li class="flex items-center gap-3">
                                <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-4 shrink-0 text-(--wp-accent)" />
                                <span class="tabular-nums">{{ eventDateRange(featured.event, 'long') }}</span>
                            </li>
                            <li v-if="featured.event.lokasi" class="flex items-center gap-3">
                                <FontAwesomeIcon :icon="['fas', 'location-dot']" class="size-4 shrink-0 text-(--wp-accent)" />
                                <span>{{ featured.event.lokasi }}</span>
                            </li>
                        </ul>
                        <span class="wp-link-arrow mt-auto inline-flex items-center gap-2 pt-8 font-semibold">
                            Lihat detail event
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5 text-(--wp-accent)" />
                        </span>
                    </div>
                </Link>

                <div v-if="gridItems.length > 0" class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(item, index) in gridItems" :key="item.event.id" v-reveal="(index % 3) * 80">
                        <EventCard :event="item.event" />
                    </div>
                </div>

                <div v-else-if="!featured" class="wp-surface mt-8 flex flex-col items-center rounded-3xl px-6 py-16 text-center">
                    <FontAwesomeIcon :icon="['fas', 'magnifying-glass']" class="size-6 text-(--wp-accent)" />
                    <p class="mt-4 text-lg font-semibold">Tidak ada event yang cocok.</p>
                    <p class="text-muted-foreground mt-1 max-w-[44ch] text-sm">Coba kata kunci lain atau tampilkan semua status event.</p>
                    <button type="button" class="wp-btn wp-btn-quiet mt-6 px-5 py-2 text-sm" @click="resetFilters">Reset filter</button>
                </div>
            </template>

            <div v-else class="wp-surface grid overflow-hidden rounded-[2rem] p-2 lg:grid-cols-12">
                <div class="aspect-[16/10] overflow-hidden rounded-[1.5rem] lg:col-span-6 lg:aspect-auto lg:min-h-[18rem]">
                    <EventCoverPlaceholder />
                </div>
                <div class="flex flex-col justify-center p-8 lg:col-span-6 lg:p-12">
                    <h2 class="text-2xl font-bold tracking-[-0.025em]">Belum ada event yang dipublikasikan.</h2>
                    <p class="text-muted-foreground mt-3 max-w-[46ch] leading-relaxed">
                        Agenda baru akan muncul di sini setelah dipublikasikan Dispora. Pantau juga kanal media sosial Dispora Kabupaten Bogor.
                    </p>
                    <Link :href="route('home')" class="wp-link-arrow mt-6 inline-flex items-center gap-2 text-sm font-semibold">
                        Kembali ke beranda
                        <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3 text-(--wp-accent)" />
                    </Link>
                </div>
            </div>

            <div
                v-reveal
                class="mt-16 flex flex-col gap-6 rounded-[2rem] bg-(--wp-accent-soft) p-8 ring-1 ring-(--wp-hairline) sm:flex-row sm:items-center lg:mt-24 lg:p-10"
            >
                <SiBolaMark class="size-14" />
                <div class="flex-1">
                    <p class="text-xl font-bold tracking-[-0.02em]">Mau adakan turnamen atau kegiatan sendiri?</p>
                    <p class="text-muted-foreground mt-1 max-w-[56ch]">
                        Booking lapangan dan fasilitas milik UPT Dispora Kabupaten Bogor lewat Si Bola — cek jadwal kosong dan tarif resmi secara
                        online.
                    </p>
                </div>
                <Link :href="route('e-booking.catalog')" class="wp-btn wp-btn-primary shrink-0 px-6 py-3 text-sm">
                    Cari venue
                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                </Link>
            </div>
        </section>
    </PublicSiteLayout>
</template>
