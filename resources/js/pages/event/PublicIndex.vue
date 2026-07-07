<script setup lang="ts">
import EventPublicHeader from '@/components/event/EventPublicHeader.vue';
import EventCard from '@/components/event/EventCard.vue';
import PublicSiteFooter from '@/components/PublicSiteFooter.vue';
import SeoHead from '@/components/SeoHead.vue';
import type { PublicEventSummary } from '@/types/event';
import { SEO_DEFAULT_KEYWORDS, SEO_EVENTS_DESCRIPTION } from '@/lib/seo';
import { buildBreadcrumbSchema, buildWebPageSchema } from '@/lib/schema';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
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
</script>

<template>
    <SeoHead
        title="Event & Kegiatan"
        :description="SEO_EVENTS_DESCRIPTION"
        :keywords="SEO_DEFAULT_KEYWORDS"
        :canonical="pageUrl"
        :schema="pageSchema"
    />

    <div class="welcome-page bg-background text-foreground flex min-h-screen flex-col">
        <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>
        <EventPublicHeader active="list" />

        <main id="konten-utama" class="mx-auto w-full max-w-6xl flex-1 px-6 py-10 lg:py-14">
            <nav class="text-muted-foreground mb-6 text-xs" aria-label="Breadcrumb">
                <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                <span class="mx-2" aria-hidden="true">→</span>
                <span class="text-foreground font-medium" aria-current="page">Event &amp; Kegiatan</span>
            </nav>

            <div class="mb-8">
                <h1 class="text-foreground text-3xl font-bold">Event &amp; Kegiatan</h1>
                <p class="text-muted-foreground mt-2 max-w-2xl text-sm">
                    Daftar lengkap kegiatan dan event olahraga Dispora Kabupaten Bogor.
                </p>
            </div>

            <div v-if="events.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <EventCard v-for="event in events" :key="event.id" :event="event" />
            </div>

            <div v-else class="content-panel p-10 text-center">
                <p class="text-muted-foreground text-sm">Belum ada event yang dipublikasikan.</p>
            </div>
        </main>

        <PublicSiteFooter />
    </div>
</template>
