<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatPageTitle, resolveShareImage, SEO_OG_LOCALE, SEO_SITE_NAME } from '@/lib/seo';
import type { SchemaObject } from '@/lib/schema';
import { serializeSchema } from '@/lib/schema';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        keywords?: string;
        canonical?: string;
        image?: string | null;
        type?: string;
        robots?: string;
        schema?: SchemaObject | SchemaObject[];
    }>(),
    {
        type: 'website',
        robots: 'index, follow',
    },
);

const page = usePage();

const pageTitle = computed(() => formatPageTitle(props.title));

const baseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const canonicalUrl = computed(() => {
    if (props.canonical) {
        return props.canonical;
    }

    const path = page.url.split('?')[0] || '/';

    if (!baseUrl.value) {
        return path;
    }

    return `${baseUrl.value}${path}`;
});

const shareImage = computed(() => resolveShareImage(props.image, baseUrl.value));

const shareDescription = computed(() => props.description?.trim() ?? '');

const schemaJson = computed(() => {
    if (!props.schema) {
        return '';
    }

    const payload = Array.isArray(props.schema) ? props.schema : [props.schema];

    return serializeSchema(payload.length === 1 ? payload[0] : payload);
});
</script>

<template>
    <Head :title="pageTitle">
        <meta v-if="description" head-key="description" name="description" :content="description" />
        <meta v-if="keywords" head-key="keywords" name="keywords" :content="keywords" />
        <meta head-key="robots" name="robots" :content="robots" />
        <link head-key="canonical" rel="canonical" :href="canonicalUrl" />

        <meta head-key="og:title" property="og:title" :content="pageTitle" />
        <meta v-if="shareDescription" head-key="og:description" property="og:description" :content="shareDescription" />
        <meta head-key="og:image" property="og:image" :content="shareImage" />
        <meta head-key="og:url" property="og:url" :content="canonicalUrl" />
        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:site_name" property="og:site_name" :content="SEO_SITE_NAME" />
        <meta head-key="og:locale" property="og:locale" :content="SEO_OG_LOCALE" />

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="pageTitle" />
        <meta v-if="shareDescription" head-key="twitter:description" name="twitter:description" :content="shareDescription" />
        <meta head-key="twitter:image" name="twitter:image" :content="shareImage" />

        <component
            :is="'script'"
            v-if="schemaJson"
            head-key="structured-data"
            type="application/ld+json"
            v-html="schemaJson"
        />
    </Head>
</template>
