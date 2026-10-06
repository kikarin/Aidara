<script setup lang="ts">
import PublicSiteHeader from '@/components/public/PublicSiteHeader.vue';
import PublicSiteFooter from '@/components/PublicSiteFooter.vue';
import { useSmoothScroll } from '@/composables/useSmoothScroll';
import type { PublicNavSection, PublicPageKey } from '@/types/public';

withDefaults(
    defineProps<{
        current?: PublicPageKey;
        sections?: PublicNavSection[];
        activeSection?: string | null;
        mainId?: string;
    }>(),
    {
        current: 'home',
        sections: undefined,
        activeSection: null,
        mainId: 'konten-utama',
    },
);

useSmoothScroll();
</script>

<template>
    <div class="welcome-page bg-background text-foreground relative flex min-h-dvh flex-col overflow-x-clip">
        <a :href="`#${mainId}`" class="skip-link">Lompat ke konten utama</a>
        <div class="wp-grain" aria-hidden="true"></div>

        <PublicSiteHeader :current="current" :sections="sections" :active-section="activeSection" />

        <main :id="mainId" class="flex-1">
            <slot />
        </main>

        <PublicSiteFooter :on-home="current === 'home'" />
    </div>
</template>
