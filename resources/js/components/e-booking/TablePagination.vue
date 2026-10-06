<script setup lang="ts">
import { library } from '@fortawesome/fontawesome-svg-core';
import { faChevronLeft, faChevronRight } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';

library.add(faChevronLeft, faChevronRight);

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

const kind = (link: PaginatorLink): 'prev' | 'next' | 'page' => {
    if (link.label.includes('&laquo;') || link.label.includes('«')) return 'prev';
    if (link.label.includes('&raquo;') || link.label.includes('»')) return 'next';
    return 'page';
};

const ariaLabel = (link: PaginatorLink) => {
    const k = kind(link);
    if (k === 'prev') return 'Halaman sebelumnya';
    if (k === 'next') return 'Halaman berikutnya';
    return link.label === '...' ? undefined : `Halaman ${link.label}`;
};
</script>

<template>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-muted-foreground text-sm tabular-nums">Menampilkan {{ from ?? 0 }}–{{ to ?? 0 }} dari {{ total ?? 0 }} {{ label }}</p>

        <nav v-if="links && links.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Paginasi">
            <template v-for="(link, i) in links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-sm font-medium tabular-nums transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                    :class="
                        link.active
                            ? 'bg-(--wp-accent-soft) font-semibold text-(--wp-accent-strong)'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    :aria-label="ariaLabel(link)"
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <FontAwesomeIcon v-if="kind(link) === 'prev'" :icon="['fas', 'chevron-left']" class="size-3" aria-hidden="true" />
                    <FontAwesomeIcon v-else-if="kind(link) === 'next'" :icon="['fas', 'chevron-right']" class="size-3" aria-hidden="true" />
                    <span v-else v-html="link.label" />
                </Link>
                <span
                    v-else
                    class="text-muted-foreground inline-flex h-8 min-w-8 cursor-not-allowed items-center justify-center rounded-lg px-2.5 text-sm tabular-nums opacity-40"
                    :aria-hidden="kind(link) !== 'page' ? 'true' : undefined"
                >
                    <FontAwesomeIcon v-if="kind(link) === 'prev'" :icon="['fas', 'chevron-left']" class="size-3" aria-hidden="true" />
                    <FontAwesomeIcon v-else-if="kind(link) === 'next'" :icon="['fas', 'chevron-right']" class="size-3" aria-hidden="true" />
                    <span v-else v-html="link.label" />
                </span>
            </template>
        </nav>
    </div>
</template>
