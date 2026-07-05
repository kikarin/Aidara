<script setup lang="ts">
import MatchCard from '@/components/worldcup/MatchCard.vue';
import type { WorldCupMatch } from '@/types/worldcup';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    matches: WorldCupMatch[];
    title: string;
}>();

const localMatches = ref<WorldCupMatch[]>([...props.matches]);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const hasLive = computed(() => localMatches.value.some((match) => match.status === 'live'));

const refreshPreview = async () => {
    if (document.visibilityState !== 'visible') {
        return;
    }

    try {
        const response = await fetch(route('worldcup.preview'));
        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (Array.isArray(data.matches)) {
            localMatches.value = data.matches;
        }
    } catch {
        // Graceful fallback — keep existing data
    }
};

onMounted(() => {
    pollTimer = setInterval(refreshPreview, 60_000);
    document.addEventListener('visibilitychange', refreshPreview);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
    document.removeEventListener('visibilitychange', refreshPreview);
});
</script>

<template>
    <section id="piala-dunia">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">FIFA World Cup 2026</p>
                    <h2 class="text-foreground text-2xl font-bold lg:text-3xl">{{ title }}</h2>
                    <p class="text-muted-foreground mt-2 max-w-2xl text-sm">
                        Preview jadwal knockout mendatang dan skor live — host: USA, Canada &amp; Mexico.
                    </p>
                </div>
                <Link
                    :href="route('worldcup.index')"
                    class="welcome-cta inline-flex shrink-0 items-center rounded-md px-5 py-2 text-sm font-medium transition-colors"
                >
                    Selengkapnya →
                </Link>
            </div>

            <div v-if="hasLive" class="mb-4 inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-950/30 dark:text-red-300">
                <span class="worldcup-live-dot h-2 w-2 rounded-full bg-red-500"></span>
                Ada pertandingan sedang berlangsung
            </div>

            <div v-if="localMatches.length > 0" class="grid gap-4 sm:grid-cols-2">
                <MatchCard v-for="match in localMatches" :key="match.id" :match="match" compact />
            </div>

            <div v-else class="content-panel p-8 text-center">
                <p class="text-muted-foreground text-sm">Tidak ada pertandingan knockout mendatang saat ini.</p>
            </div>

            <p class="text-muted-foreground mt-6 text-center text-xs">Data: FIFA World Cup 2026 API · worldcup26.ir</p>
        </div>
    </section>
</template>
