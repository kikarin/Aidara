import type { WorldCupMatch } from '@/types/worldcup';
import { onMounted, onUnmounted, ref } from 'vue';

export interface WorldCupSchedulePayload {
    liveMatches: WorldCupMatch[];
    upcomingMatches: WorldCupMatch[];
    finishedMatches: WorldCupMatch[];
    knockoutMatches: WorldCupMatch[];
    knockoutByStage: Record<string, WorldCupMatch[]>;
}

const POLL_INTERVAL_MS = 60_000;

export function useWorldCupLive(initial: WorldCupSchedulePayload) {
    const liveMatches = ref<WorldCupMatch[]>([...initial.liveMatches]);
    const upcomingMatches = ref<WorldCupMatch[]>([...initial.upcomingMatches]);
    const finishedMatches = ref<WorldCupMatch[]>([...initial.finishedMatches]);
    const knockoutMatches = ref<WorldCupMatch[]>([...initial.knockoutMatches]);
    const knockoutByStage = ref<Record<string, WorldCupMatch[]>>({ ...initial.knockoutByStage });
    const isRefreshing = ref(false);
    const lastUpdated = ref<Date | null>(null);

    let pollTimer: ReturnType<typeof setInterval> | null = null;

    const applyPayload = (payload: WorldCupSchedulePayload) => {
        liveMatches.value = payload.liveMatches ?? [];
        upcomingMatches.value = payload.upcomingMatches ?? [];
        finishedMatches.value = payload.finishedMatches ?? [];
        knockoutMatches.value = payload.knockoutMatches ?? [];
        knockoutByStage.value = payload.knockoutByStage ?? {};
        lastUpdated.value = new Date();
    };

    const refresh = async () => {
        if (document.visibilityState !== 'visible' || isRefreshing.value) {
            return;
        }

        isRefreshing.value = true;

        try {
            const response = await fetch(route('worldcup.schedule'));
            if (response.ok) {
                applyPayload(await response.json());
            }
        } catch {
            // Keep existing data on failure
        } finally {
            isRefreshing.value = false;
        }
    };

    onMounted(() => {
        pollTimer = setInterval(refresh, POLL_INTERVAL_MS);
        document.addEventListener('visibilitychange', refresh);
    });

    onUnmounted(() => {
        if (pollTimer) {
            clearInterval(pollTimer);
        }
        document.removeEventListener('visibilitychange', refresh);
    });

    return {
        liveMatches,
        upcomingMatches,
        finishedMatches,
        knockoutMatches,
        knockoutByStage,
        isRefreshing,
        lastUpdated,
        refresh,
    };
}
