<script setup lang="ts">
import { Card } from '@/components/ui/card';
import PublicSiteFooter from '@/components/PublicSiteFooter.vue';
import SeoHead from '@/components/SeoHead.vue';
import { buildArticleSchema, buildBreadcrumbSchema, buildWebPageSchema } from '@/lib/schema';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LegalSection {
    title: string;
    paragraphs: string[];
}

interface LegalPage {
    slug: string;
    title: string;
    subtitle: string;
    updated_at: string;
    sections: LegalSection[];
}

const props = defineProps<{
    page: LegalPage;
}>();

const inertiaPage = usePage();

const legalLinks = [
    { slug: 'terms', label: 'Syarat & Ketentuan' },
    { slug: 'privacy', label: 'Kebijakan Privasi' },
    { slug: 'pdp', label: 'PDP' },
];

const pageUrl = computed(() => route('legal.show', props.page.slug, true));
const siteBaseUrl = computed(() => {
    const ziggy = inertiaPage.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const pageSchema = computed(() => [
    buildWebPageSchema({
        baseUrl: siteBaseUrl.value,
        name: props.page.title,
        description: props.page.subtitle,
        url: pageUrl.value,
    }),
    buildArticleSchema({
        baseUrl: siteBaseUrl.value,
        headline: props.page.title,
        description: props.page.subtitle,
        url: pageUrl.value,
        dateModified: props.page.updated_at,
    }),
    buildBreadcrumbSchema([
        { name: 'Beranda', url: route('home', undefined, true) },
        { name: props.page.title, url: pageUrl.value },
    ]),
]);
</script>

<template>
    <SeoHead
        :title="page.title"
        :description="page.subtitle"
        :canonical="pageUrl"
        type="article"
        :schema="pageSchema"
    />

    <div class="welcome-page bg-background text-foreground flex min-h-screen flex-col">
        <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>

        <header class="border-border/60 bg-background/80 sticky top-0 z-50 border-b backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
                <Link :href="route('home')" class="text-foreground text-sm font-bold" aria-label="AIDARA — kembali ke beranda">
                    AIDARA
                </Link>
                <nav class="text-muted-foreground flex items-center gap-4 text-sm" aria-label="Navigasi utama">
                    <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                    <Link :href="route('event.public.index')" class="hover:text-foreground transition-colors">Event</Link>
                </nav>
            </div>
        </header>

        <main id="konten-utama" class="mx-auto w-full max-w-2xl flex-1 px-6 py-10">
            <nav class="text-muted-foreground mb-4 text-xs" aria-label="Breadcrumb">
                <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                <span class="mx-2" aria-hidden="true">→</span>
                <span class="text-foreground font-medium" aria-current="page">{{ page.title }}</span>
            </nav>

            <div class="mb-8 space-y-1">
                <p class="text-muted-foreground text-xs font-semibold tracking-widest uppercase">AIDARA — Dispora Kabupaten Bogor</p>
                <h1 class="text-2xl font-bold">{{ page.title }}</h1>
                <p class="text-muted-foreground text-sm">{{ page.subtitle }}</p>
            </div>

            <div class="space-y-4">
                <p class="text-muted-foreground text-xs">Diperbarui: {{ page.updated_at }}</p>

                <Card v-for="section in page.sections" :key="section.title" class="space-y-2 p-4">
                    <h2 class="text-foreground text-sm font-semibold">{{ section.title }}</h2>
                    <p
                        v-for="(paragraph, index) in section.paragraphs"
                        :key="index"
                        class="text-muted-foreground text-sm leading-relaxed"
                    >
                        {{ paragraph }}
                    </p>
                </Card>

                <nav class="border-border/60 flex flex-wrap gap-3 border-t pt-4 text-xs" aria-label="Dokumen legal terkait">
                    <span class="text-muted-foreground">Dokumen terkait:</span>
                    <Link
                        v-for="link in legalLinks"
                        :key="link.slug"
                        :href="route('legal.show', { slug: link.slug })"
                        class="text-primary hover:underline"
                        :class="{ 'font-semibold underline': link.slug === page.slug }"
                        :aria-current="link.slug === page.slug ? 'page' : undefined"
                    >
                        {{ link.label }}
                    </Link>
                </nav>
            </div>
        </main>

        <PublicSiteFooter />
    </div>
</template>
