<script setup lang="ts">
import TeamFlag from '@/components/worldcup/TeamFlag.vue';
import type { WorldCupGroup } from '@/types/worldcup';

defineProps<{
    group: WorldCupGroup;
}>();
</script>

<template>
    <div class="content-panel overflow-hidden">
        <div class="border-border/60 bg-muted/40 border-b px-4 py-3">
            <h3 class="text-foreground text-sm font-bold tracking-wide uppercase">Grup {{ group.group }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-muted-foreground border-b text-left text-xs">
                        <th class="px-3 py-2 font-medium">#</th>
                        <th class="px-3 py-2 font-medium">Tim</th>
                        <th class="px-3 py-2 text-center font-medium">GF</th>
                        <th class="px-3 py-2 text-center font-medium">GA</th>
                        <th class="px-3 py-2 text-center font-medium">GD</th>
                        <th class="px-3 py-2 text-center font-medium">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(team, index) in group.standings"
                        :key="team.teamId"
                        class="border-border/50 border-b last:border-0"
                        :class="index < 2 ? 'bg-green-50/70 dark:bg-green-950/20' : ''"
                    >
                        <td class="text-muted-foreground px-3 py-2.5">{{ index + 1 }}</td>
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-2">
                                <TeamFlag :flag="team.flag" :fifa-code="team.fifaCode" :name="team.name" size="sm" />
                                <span class="font-medium">{{ team.name }}</span>
                                <span
                                    v-if="index < 2"
                                    class="rounded bg-green-100 px-1.5 py-0.5 text-[10px] font-semibold text-green-700 dark:bg-green-950/50 dark:text-green-300"
                                >
                                    Lolos
                                </span>
                            </div>
                        </td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ team.gf }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ team.ga }}</td>
                        <td class="px-3 py-2.5 text-center tabular-nums">{{ team.gd >= 0 ? `+${team.gd}` : team.gd }}</td>
                        <td class="px-3 py-2.5 text-center font-bold tabular-nums">{{ team.pts }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
