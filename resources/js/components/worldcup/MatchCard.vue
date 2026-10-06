<script setup lang="ts">
import TeamFlag from '@/components/worldcup/TeamFlag.vue';
import type { WorldCupMatch } from '@/types/worldcup';

defineProps<{
    match: WorldCupMatch;
    compact?: boolean;
}>();
</script>

<template>
    <article class="content-panel overflow-hidden p-4 lg:p-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-950/50 dark:text-green-300">
                {{ match.stageLabel }}
            </span>
            <span
                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="{
                    'worldcup-live-badge bg-red-100 text-red-700 dark:bg-red-950/50 dark:text-red-300': match.status === 'live',
                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300': match.status === 'finished',
                    'bg-muted text-muted-foreground': match.status === 'upcoming',
                }"
            >
                <template v-if="match.status === 'live'">{{ match.timeElapsed }}' · LIVE</template>
                <template v-else>{{ match.statusLabel }}</template>
            </span>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex min-w-0 flex-1 items-center justify-end gap-2">
                <p class="truncate text-right text-sm font-semibold lg:text-base">{{ match.homeTeam.name }}</p>
                <TeamFlag
                    :flag="match.homeTeam.flag"
                    :fifa-code="match.homeTeam.fifaCode"
                    :name="match.homeTeam.name"
                    :size="compact ? 'sm' : 'md'"
                />
            </div>

            <div
                class="shrink-0 rounded-lg bg-[var(--brand-green,#2e7d32)]/10 px-3 py-2 text-center font-bold tabular-nums"
                :class="compact ? 'min-w-[4.5rem] text-base' : 'min-w-[5.5rem] text-lg'"
            >
                <span v-if="match.status === 'upcoming'" class="text-muted-foreground text-sm">vs</span>
                <span v-else class="text-[var(--brand-green,#2e7d32)]">{{ match.homeScore }} - {{ match.awayScore }}</span>
            </div>

            <div class="flex min-w-0 flex-1 items-center gap-2">
                <TeamFlag
                    :flag="match.awayTeam.flag"
                    :fifa-code="match.awayTeam.fifaCode"
                    :name="match.awayTeam.name"
                    :size="compact ? 'sm' : 'md'"
                />
                <p class="truncate text-sm font-semibold lg:text-base">{{ match.awayTeam.name }}</p>
            </div>
        </div>

        <div class="text-muted-foreground mt-3 flex flex-wrap items-center justify-between gap-2 text-xs">
            <span>{{ match.localDateFormatted ?? '—' }}</span>
            <span v-if="match.stadium">{{ match.stadium.name }}<span v-if="match.stadium.city">, {{ match.stadium.city }}</span></span>
        </div>

        <div
            v-if="!compact && (match.homeScorers || match.awayScorers)"
            class="text-muted-foreground mt-3 grid gap-1 border-t pt-3 text-xs"
        >
            <p v-if="match.homeScorers"><span class="font-medium">{{ match.homeTeam.name }}:</span> {{ match.homeScorers }}</p>
            <p v-if="match.awayScorers"><span class="font-medium">{{ match.awayTeam.name }}:</span> {{ match.awayScorers }}</p>
        </div>
    </article>
</template>
