<script setup lang="ts">
import TeamFlag from '@/components/worldcup/TeamFlag.vue';
import type { WorldCupMatch } from '@/types/worldcup';
import { computed } from 'vue';

const props = defineProps<{
    knockoutByStage: Record<string, WorldCupMatch[]>;
}>();

const stageOrder = [
    { key: 'r32', label: 'Babak 32 Besar' },
    { key: 'r16', label: 'Babak 16 Besar' },
    { key: 'qf', label: 'Perempat Final' },
    { key: 'sf', label: 'Semi Final' },
    { key: 'third', label: 'Perebutan Juara 3' },
    { key: 'final', label: 'Final' },
];

const stages = computed(() =>
    stageOrder
        .map((stage) => ({
            ...stage,
            matches: props.knockoutByStage?.[stage.key] ?? [],
        }))
        .filter((stage) => stage.matches.length > 0),
);

const winnerSide = (match: WorldCupMatch): 'home' | 'away' | null => {
    if (match.status !== 'finished') {
        return null;
    }

    if (match.homeScore > match.awayScore) {
        return 'home';
    }

    if (match.awayScore > match.homeScore) {
        return 'away';
    }

    return null;
};
</script>

<template>
    <div v-if="stages.length === 0" class="text-muted-foreground content-panel p-8 text-center text-sm">
        Bracket knockout belum tersedia.
    </div>

    <div v-else class="scrollbar-none overflow-x-auto pb-4">
        <div class="flex min-w-max gap-4 lg:gap-6">
            <div v-for="stage in stages" :key="stage.key" class="flex w-64 shrink-0 flex-col gap-3 lg:w-72">
                <div class="sticky top-0 z-10 rounded-lg bg-[var(--brand-green,#2e7d32)] px-3 py-2 text-center text-xs font-bold tracking-wide text-white uppercase">
                    {{ stage.label }}
                </div>

                <div
                    v-for="match in stage.matches"
                    :key="match.id"
                    class="content-panel overflow-hidden"
                    :class="match.status === 'live' ? 'ring-2 ring-red-400/60' : ''"
                >
                    <div class="text-muted-foreground border-border/60 border-b px-3 py-1.5 text-[10px]">
                        {{ match.localDateFormatted ?? '—' }}
                    </div>

                    <div class="space-y-0 divide-y">
                        <div
                            class="flex items-center justify-between gap-2 px-3 py-2"
                            :class="winnerSide(match) === 'home' ? 'bg-green-50/80 font-semibold dark:bg-green-950/30' : ''"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <TeamFlag :flag="match.homeTeam.flag" :fifa-code="match.homeTeam.fifaCode" :name="match.homeTeam.name" size="sm" />
                                <span class="truncate text-xs">{{ match.homeTeam.name }}</span>
                            </div>
                            <span class="shrink-0 text-xs font-bold tabular-nums">
                                {{ match.status === 'upcoming' ? '—' : match.homeScore }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between gap-2 px-3 py-2"
                            :class="winnerSide(match) === 'away' ? 'bg-green-50/80 font-semibold dark:bg-green-950/30' : ''"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <TeamFlag :flag="match.awayTeam.flag" :fifa-code="match.awayTeam.fifaCode" :name="match.awayTeam.name" size="sm" />
                                <span class="truncate text-xs">{{ match.awayTeam.name }}</span>
                            </div>
                            <span class="shrink-0 text-xs font-bold tabular-nums">
                                {{ match.status === 'upcoming' ? '—' : match.awayScore }}
                            </span>
                        </div>
                    </div>

                    <div class="border-border/60 flex items-center justify-between border-t px-3 py-1.5">
                        <span
                            class="text-[10px] font-semibold"
                            :class="{
                                'worldcup-live-badge text-red-600': match.status === 'live',
                                'text-emerald-600': match.status === 'finished',
                                'text-muted-foreground': match.status === 'upcoming',
                            }"
                        >
                            {{ match.status === 'live' ? `${match.timeElapsed}' LIVE` : match.statusLabel }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
