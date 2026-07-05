<script setup lang="ts">
import { Card } from '@/components/ui/card';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

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

defineProps<{
    page: LegalPage;
}>();

const legalLinks = [
    { slug: 'terms', label: 'Syarat & Ketentuan' },
    { slug: 'privacy', label: 'Kebijakan Privasi' },
    { slug: 'pdp', label: 'PDP' },
];
</script>

<template>
    <AuthBase :title="page.title" :description="page.subtitle" wide>
        <Head :title="page.title" />

        <div class="space-y-4">
            <p class="text-xs text-muted-foreground">Diperbarui: {{ page.updated_at }}</p>

            <Card v-for="section in page.sections" :key="section.title" class="space-y-2 p-4">
                <h2 class="text-sm font-semibold text-foreground">{{ section.title }}</h2>
                <p
                    v-for="(paragraph, index) in section.paragraphs"
                    :key="index"
                    class="text-sm leading-relaxed text-muted-foreground"
                >
                    {{ paragraph }}
                </p>
            </Card>

            <div class="border-border/60 flex flex-wrap gap-3 border-t pt-4 text-xs">
                <span class="text-muted-foreground">Dokumen terkait:</span>
                <Link
                    v-for="link in legalLinks"
                    :key="link.slug"
                    :href="route('legal.show', { slug: link.slug })"
                    class="text-primary hover:underline"
                    :class="{ 'font-semibold underline': link.slug === page.slug }"
                >
                    {{ link.label }}
                </Link>
            </div>
        </div>
    </AuthBase>
</template>
