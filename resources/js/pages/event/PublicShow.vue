<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import EventCoverPlaceholder from '@/components/event/EventCoverPlaceholder.vue';
import SeoHead from '@/components/SeoHead.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import PublicSiteLayout from '@/layouts/PublicSiteLayout.vue';
import { EVENT_PHASE_LABEL, eventDateRange, eventPhase, eventPhaseClass } from '@/lib/publicEvent';
import { buildBreadcrumbSchema, buildEventSchema } from '@/lib/schema';
import { SEO_DEFAULT_KEYWORDS, truncateDescription } from '@/lib/seo';
import type { PublicEventSummary } from '@/types/event';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faArrowRight,
    faCalendarDays,
    faCheck,
    faLayerGroup,
    faLink,
    faLocationDot,
    faMapLocationDot,
    faMedal,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';

library.add(faArrowLeft, faArrowRight, faCalendarDays, faCheck, faLayerGroup, faLink, faLocationDot, faMapLocationDot, faMedal);

const props = defineProps<{
    event: PublicEventSummary;
}>();

const phase = computed(() => eventPhase(props.event));
const dateRange = computed(() => eventDateRange(props.event, 'long'));

const eventDescription = computed(() => {
    const source = props.event.deskripsi_singkat || props.event.deskripsi;

    if (!source) {
        return `Informasi event ${props.event.nama_event} dari Dispora Kabupaten Bogor di platform AIDARA.`;
    }

    return truncateDescription(source);
});

const mapsUrl = computed(() =>
    props.event.lokasi ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(props.event.lokasi)}` : null,
);

const page = usePage();
const pageUrl = computed(() => route('event.public.show', props.event.id, true));
const siteBaseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const pageSchema = computed(() => [
    buildEventSchema({
        baseUrl: siteBaseUrl.value,
        name: props.event.nama_event,
        description: eventDescription.value,
        url: pageUrl.value,
        image: props.event.foto_url,
        startDate: props.event.tanggal_mulai,
        endDate: props.event.tanggal_selesai,
        location: props.event.lokasi,
        status: props.event.status,
    }),
    buildBreadcrumbSchema([
        { name: 'Beranda', url: route('home', undefined, true) },
        { name: 'Event & Kegiatan', url: route('event.public.index', undefined, true) },
        { name: props.event.nama_event, url: pageUrl.value },
    ]),
]);

const copyState = ref<'idle' | 'copied' | 'failed'>('idle');
let copyTimer: ReturnType<typeof setTimeout> | null = null;

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(pageUrl.value);
        copyState.value = 'copied';
    } catch {
        copyState.value = 'failed';
    }

    if (copyTimer) {
        clearTimeout(copyTimer);
    }
    copyTimer = setTimeout(() => (copyState.value = 'idle'), 2500);
};

onUnmounted(() => {
    if (copyTimer) {
        clearTimeout(copyTimer);
    }
});
</script>

<template>
    <SeoHead
        :title="`${event.nama_event} — Event`"
        :description="eventDescription"
        :keywords="SEO_DEFAULT_KEYWORDS"
        :canonical="pageUrl"
        :image="event.foto_url"
        type="article"
        :schema="pageSchema"
    />

    <PublicSiteLayout current="event">
        <article>
            <header class="relative isolate">
                <div class="wp-hero-bg absolute inset-0 -z-10" aria-hidden="true"></div>

                <div class="mx-auto max-w-7xl px-6 pt-32 pb-10 lg:px-8 lg:pt-40 lg:pb-14">
                    <nav class="wp-enter text-muted-foreground text-sm" aria-label="Breadcrumb">
                        <ol class="flex flex-wrap items-center gap-2">
                            <li><Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link></li>
                            <li aria-hidden="true" class="text-muted-foreground/50">/</li>
                            <li>
                                <Link :href="route('event.public.index')" class="hover:text-foreground transition-colors">Event</Link>
                            </li>
                            <li aria-hidden="true" class="text-muted-foreground/50">/</li>
                            <li class="text-foreground line-clamp-1 max-w-[40ch] font-medium" aria-current="page">{{ event.nama_event }}</li>
                        </ol>
                    </nav>

                    <div class="wp-enter mt-8 flex flex-wrap items-center gap-3" :style="{ '--enter-delay': '60ms' }">
                        <span class="rounded-md px-2.5 py-1 text-xs font-semibold" :class="eventPhaseClass(phase)">
                            {{ EVENT_PHASE_LABEL[phase] }}
                        </span>
                        <span class="text-muted-foreground text-sm font-medium">{{ event.kategori_event_nama }}</span>
                    </div>

                    <h1
                        class="wp-enter mt-5 max-w-[22ch] text-[clamp(2.25rem,5vw,4.25rem)] leading-[1] font-bold tracking-[-0.04em] text-balance"
                        :style="{ '--enter-delay': '120ms' }"
                    >
                        {{ event.nama_event }}
                    </h1>

                    <ul class="wp-enter text-muted-foreground mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm" :style="{ '--enter-delay': '180ms' }">
                        <li class="flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-3.5 text-(--wp-accent)" />
                            <span class="tabular-nums">{{ dateRange }}</span>
                        </li>
                        <li v-if="event.lokasi" class="flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'location-dot']" class="size-3.5 text-(--wp-accent)" />
                            {{ event.lokasi }}
                        </li>
                    </ul>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div
                    class="wp-enter wp-surface overflow-hidden rounded-[2rem] p-2"
                    :class="event.foto_url ? 'aspect-[16/9] lg:aspect-[21/9]' : 'aspect-[21/9] lg:aspect-[32/9]'"
                    :style="{ '--enter-delay': '220ms' }"
                >
                    <AppImage
                        v-if="event.foto_url"
                        :src="event.foto_url"
                        :alt="`Poster ${event.nama_event}`"
                        class="size-full rounded-[1.5rem] object-cover"
                        :lazy="false"
                    />
                    <div v-else class="size-full overflow-hidden rounded-[1.5rem]">
                        <EventCoverPlaceholder />
                    </div>
                </div>

                <div class="grid gap-12 pt-14 pb-24 lg:grid-cols-12 lg:gap-10 lg:pt-20 lg:pb-32">
                    <section class="lg:col-span-7" aria-labelledby="tentang-heading">
                        <p class="wp-eyebrow">Tentang event</p>
                        <h2 id="tentang-heading" class="sr-only">Deskripsi event</h2>
                        <div
                            v-if="event.deskripsi"
                            class="text-foreground/85 mt-6 max-w-[68ch] text-[1.05rem] leading-[1.8] text-pretty whitespace-pre-line"
                        >
                            {{ event.deskripsi }}
                        </div>
                        <p v-else class="text-muted-foreground mt-6 max-w-[60ch] leading-relaxed">
                            Penyelenggara belum menambahkan deskripsi lengkap. Untuk informasi lebih lanjut, hubungi Dispora Kabupaten Bogor di
                            <a href="tel:+622517503524" class="text-foreground font-medium tabular-nums underline underline-offset-4">
                                (0251) 7503524</a
                            >.
                        </p>

                        <Link :href="route('event.public.index')" class="wp-btn wp-btn-quiet mt-12 px-5 py-2.5 text-sm">
                            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3" />
                            Semua event
                        </Link>
                    </section>

                    <aside class="space-y-4 lg:sticky lg:top-28 lg:col-span-4 lg:col-start-9 lg:self-start" aria-label="Informasi event">
                        <div class="wp-surface rounded-3xl p-6">
                            <h2 class="text-sm font-semibold">Informasi event</h2>
                            <dl class="mt-4 divide-y divide-(--wp-hairline) text-sm">
                                <div class="flex items-start gap-3 py-3.5">
                                    <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" />
                                    <div>
                                        <dt class="text-muted-foreground text-xs">Tanggal</dt>
                                        <dd class="mt-0.5 font-medium tabular-nums">{{ dateRange }}</dd>
                                    </div>
                                </div>
                                <div v-if="event.lokasi" class="flex items-start gap-3 py-3.5">
                                    <FontAwesomeIcon :icon="['fas', 'location-dot']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" />
                                    <div>
                                        <dt class="text-muted-foreground text-xs">Lokasi</dt>
                                        <dd class="mt-0.5 font-medium">{{ event.lokasi }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 py-3.5">
                                    <FontAwesomeIcon :icon="['fas', 'medal']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" />
                                    <div>
                                        <dt class="text-muted-foreground text-xs">Tingkat</dt>
                                        <dd class="mt-0.5 font-medium">{{ event.tingkat_event_nama }}</dd>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 py-3.5">
                                    <FontAwesomeIcon :icon="['fas', 'layer-group']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" />
                                    <div>
                                        <dt class="text-muted-foreground text-xs">Kategori</dt>
                                        <dd class="mt-0.5 font-medium">{{ event.kategori_event_nama }}</dd>
                                    </div>
                                </div>
                            </dl>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <button type="button" class="wp-btn wp-btn-quiet px-4 py-2 text-sm" @click="copyLink">
                                    <FontAwesomeIcon
                                        :icon="['fas', copyState === 'copied' ? 'check' : 'link']"
                                        class="size-3.5"
                                        :class="{ 'text-(--wp-accent)': copyState === 'copied' }"
                                    />
                                    {{ copyState === 'copied' ? 'Tautan disalin' : 'Salin tautan' }}
                                </button>
                                <a
                                    v-if="mapsUrl"
                                    :href="mapsUrl"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="wp-btn wp-btn-quiet px-4 py-2 text-sm"
                                >
                                    <FontAwesomeIcon :icon="['fas', 'map-location-dot']" class="size-3.5" />
                                    Buka di Maps
                                </a>
                            </div>
                            <p v-if="copyState === 'failed'" class="text-destructive mt-3 text-xs" role="status">
                                Gagal menyalin. Salin alamat dari address bar browser.
                            </p>
                            <p v-else class="sr-only" role="status">{{ copyState === 'copied' ? 'Tautan event disalin' : '' }}</p>
                        </div>

                        <Link
                            :href="route('e-booking.catalog')"
                            class="group flex items-center gap-4 rounded-3xl bg-(--wp-accent-soft) p-5 ring-1 ring-(--wp-hairline) transition-transform duration-300 hover:-translate-y-0.5"
                        >
                            <SiBolaMark class="size-11" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">Butuh venue untuk kegiatanmu?</span>
                                <span class="text-muted-foreground block text-xs leading-relaxed"> Booking lapangan UPT Dispora lewat Si Bola. </span>
                            </span>
                            <span class="wp-link-arrow">
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5 text-(--wp-accent)" />
                            </span>
                        </Link>
                    </aside>
                </div>
            </div>
        </article>
    </PublicSiteLayout>
</template>
