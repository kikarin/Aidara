<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginatorLink = { url: string | null; label: string; active: boolean };

withDefaults(
    defineProps<{
        links: PaginatorLink[];
        from?: number | null;
        to?: number | null;
        total?: number | null;
        label?: string;
    }>(),
    {
        from: null,
        to: null,
        total: null,
        label: 'data',
    },
);
</script>

<template>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-muted-foreground text-xs">Menampilkan {{ from ?? 0 }}–{{ to ?? 0 }} dari {{ total ?? 0 }} {{ label }}</p>

        <div v-if="links && links.length > 3" class="flex flex-wrap items-center gap-1">
            <template v-for="(link, i) in links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="border-border rounded-md border px-3 py-1 text-xs transition-colors"
                    :class="link.active ? 'bg-muted font-semibold' : 'hover:bg-muted/60'"
                >
                    <span v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="text-muted-foreground cursor-not-allowed rounded-md border border-transparent px-3 py-1 text-xs opacity-50"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
