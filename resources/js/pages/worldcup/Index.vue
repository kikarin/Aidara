<script setup lang="ts">
import GroupStandingsTable from '@/components/worldcup/GroupStandingsTable.vue';
import KnockoutBracket from '@/components/worldcup/KnockoutBracket.vue';
import MatchCard from '@/components/worldcup/MatchCard.vue';
import WorldCupPublicHeader from '@/components/worldcup/WorldCupPublicHeader.vue';
import { Button } from '@/components/ui/button';
import { useWorldCupLive } from '@/composables/useWorldCupLive';
import type { WorldCupGroup, WorldCupMatch } from '@/types/worldcup';
import { Head, Link } from '@inertiajs/vue3';
import { LoaderCircle, RefreshCw } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type TabKey = 'schedule' | 'groups' | 'bracket';

const props = defineProps<{
    sectionTitle: string;
    liveMatches: WorldCupMatch[];
    upcomingMatches: WorldCupMatch[];
    finishedMatches: WorldCupMatch[];
    knockoutMatches: WorldCupMatch[];
    knockoutByStage: Record<string, WorldCupMatch[]>;
}>();

const activeTab = ref<TabKey>('schedule');
const visitedTabs = ref<Set<TabKey>>(new Set(['schedule']));
const statusFilter = ref<'all' | 'live' | 'upcoming' | 'finished'>('all');
const stageFilter = ref<'all' | 'r32' | 'r16' | 'qf' | 'sf' | 'third' | 'final'>('all');

const groups = ref<WorldCupGroup[]>([]);
const groupsLoading = ref(false);
const groupsError = ref(false);
const groupsLoaded = ref(false);

const {
    liveMatches,
    upcomingMatches,
    finishedMatches,
    knockoutByStage,
    isRefreshing,
    lastUpdated,
    refresh,
} = useWorldCupLive({
    liveMatches: props.liveMatches,
    upcomingMatches: props.upcomingMatches,
    finishedMatches: props.finishedMatches,
    knockoutMatches: props.knockoutMatches,
    knockoutByStage: props.knockoutByStage,
});

const statusFilters = [
    { key: 'all', label: 'Semua' },
    { key: 'live', label: 'Live' },
    { key: 'upcoming', label: 'Akan Datang' },
    { key: 'finished', label: 'Selesai' },
] as const;

const stageFilters = [
    { key: 'all', label: 'Semua Stage' },
    { key: 'r32', label: 'R32' },
    { key: 'r16', label: 'R16' },
    { key: 'qf', label: 'QF' },
    { key: 'sf', label: 'SF' },
    { key: 'third', label: 'Juara 3' },
    { key: 'final', label: 'Final' },
] as const;

const selectTab = async (tab: TabKey) => {
    activeTab.value = tab;
    visitedTabs.value.add(tab);

    if (tab === 'groups') {
        await loadGroups();
    }
};

const loadGroups = async () => {
    if (groupsLoaded.value || groupsLoading.value) {
        return;
    }

    groupsLoading.value = true;
    groupsError.value = false;

    try {
        const response = await fetch(route('worldcup.groups'));

        if (!response.ok) {
            groupsError.value = true;
            return;
        }

        const payload = await response.json();
        groups.value = Array.isArray(payload.groups) ? payload.groups : [];
        groupsLoaded.value = true;
    } catch {
        groupsError.value = true;
    } finally {
        groupsLoading.value = false;
    }
};

const retryGroups = async () => {
    groupsLoaded.value = false;
    await loadGroups();
};

const allScheduleMatches = computed(() => {
    const combined = [...liveMatches.value, ...upcomingMatches.value, ...finishedMatches.value];
    const seen = new Set<string>();

    return combined.filter((match) => {
        if (seen.has(match.id)) {
            return false;
        }

        seen.add(match.id);
        return true;
    });
});

const filteredScheduleMatches = computed(() =>
    allScheduleMatches.value.filter((match) => {
        if (statusFilter.value !== 'all' && match.status !== statusFilter.value) {
            return false;
        }

        if (stageFilter.value !== 'all' && match.stage !== stageFilter.value) {
            return false;
        }

        return true;
    }),
);

const hasLiveMatches = computed(() => liveMatches.value.length > 0);

const formattedLastUpdated = computed(() => {
    if (!lastUpdated.value) {
        return null;
    }

    return lastUpdated.value.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
});
</script>

<template>
    <Head :title="`${sectionTitle} — AIDARA`" />

    <div class="welcome-page bg-background text-foreground min-h-screen">
        <WorldCupPublicHeader />

        <main class="mx-auto max-w-6xl px-6 py-10">
            <nav class="text-muted-foreground mb-6 text-xs">
                <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                <span class="mx-2">→</span>
                <span class="text-foreground font-medium">{{ sectionTitle }}</span>
            </nav>

            <div class="mb-8 text-center">
                <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">FIFA World Cup 2026</p>
                <h1 class="text-foreground mb-2 text-3xl font-bold lg:text-4xl">{{ sectionTitle }}</h1>
                <p class="text-muted-foreground mx-auto max-w-2xl text-sm">
                    USA · Canada · Mexico — jadwal lengkap, skor live, klasemen 12 grup, dan bracket knockout.
                </p>
            </div>

            <div class="mb-6 flex flex-wrap justify-center gap-2">
                <Button :variant="activeTab === 'schedule' ? 'default' : 'outline'" size="sm" @click="selectTab('schedule')">
                    Jadwal &amp; Live
                    <span v-if="hasLiveMatches" class="worldcup-live-dot ml-1.5 inline-block h-2 w-2 rounded-full bg-red-500"></span>
                </Button>
                <Button :variant="activeTab === 'groups' ? 'default' : 'outline'" size="sm" @click="selectTab('groups')">
                    Klasemen Grup
                </Button>
                <Button :variant="activeTab === 'bracket' ? 'default' : 'outline'" size="sm" @click="selectTab('bracket')">
                    Bracket Knockout
                </Button>
            </div>

            <div v-if="activeTab === 'schedule'" class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="filter in statusFilters"
                            :key="filter.key"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                            :class="
                                statusFilter === filter.key
                                    ? 'border-[var(--brand-green,#2e7d32)] bg-[var(--brand-green,#2e7d32)] text-white'
                                    : 'border-border text-muted-foreground hover:border-[var(--brand-green,#2e7d32)]/50'
                            "
                            @click="statusFilter = filter.key"
                        >
                            {{ filter.label }}
                            <span v-if="filter.key === 'live' && hasLiveMatches" class="worldcup-live-dot ml-1 inline-block h-1.5 w-1.5 rounded-full bg-red-400"></span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5 text-xs transition-colors"
                        :disabled="isRefreshing"
                        @click="refresh"
                    >
                        <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': isRefreshing }" />
                        Refresh
                        <span v-if="formattedLastUpdated">· {{ formattedLastUpdated }}</span>
                    </button>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="filter in stageFilters"
                        :key="filter.key"
                        type="button"
                        class="rounded-md border px-2.5 py-1 text-xs font-medium transition-colors"
                        :class="
                            stageFilter === filter.key
                                ? 'border-orange-400 bg-orange-50 text-orange-800 dark:bg-orange-950/30 dark:text-orange-200'
                                : 'border-border text-muted-foreground hover:bg-muted/50'
                        "
                        @click="stageFilter = filter.key"
                    >
                        {{ filter.label }}
                    </button>
                </div>

                <div v-if="filteredScheduleMatches.length === 0" class="content-panel p-8 text-center">
                    <p class="text-muted-foreground text-sm">Tidak ada pertandingan untuk filter ini.</p>
                </div>

                <div v-else class="grid gap-4 lg:grid-cols-2">
                    <MatchCard v-for="match in filteredScheduleMatches" :key="match.id" :match="match" />
                </div>
            </div>

            <div v-else-if="activeTab === 'groups'">
                <div v-if="groupsLoading" class="content-panel flex items-center justify-center gap-2 p-10 text-sm">
                    <LoaderCircle class="h-4 w-4 animate-spin" />
                    Memuat klasemen grup...
                </div>

                <div v-else-if="groupsError" class="content-panel space-y-3 p-8 text-center">
                    <p class="text-muted-foreground text-sm">Data klasemen sedang tidak tersedia.</p>
                    <Button size="sm" variant="outline" @click="retryGroups">Coba lagi</Button>
                </div>

                <div v-else-if="groups.length === 0" class="content-panel p-8 text-center">
                    <p class="text-muted-foreground text-sm">Belum ada data klasemen grup.</p>
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <GroupStandingsTable v-for="group in groups" :key="group.group" :group="group" />
                </div>
            </div>

            <div v-else-if="activeTab === 'bracket'">
                <KnockoutBracket v-if="visitedTabs.has('bracket')" :knockout-by-stage="knockoutByStage" />
            </div>

            <p class="text-muted-foreground mt-10 text-center text-xs">Data: FIFA World Cup 2026 API · worldcup26.ir</p>
        </main>
    </div>
</template>
