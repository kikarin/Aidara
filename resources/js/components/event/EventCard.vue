<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import EventCoverPlaceholder from '@/components/event/EventCoverPlaceholder.vue';
import { EVENT_PHASE_LABEL, eventDateRange, eventDateTile, eventPhase, eventPhaseClass } from '@/lib/publicEvent';
import { trackSpotlight } from '@/lib/publicMotion';
import type { PublicEventSummary } from '@/types/event';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRight, faCalendarDays, faLocationDot, faMedal } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

library.add(faArrowRight, faCalendarDays, faLocationDot, faMedal);

const props = defineProps<{
    event: PublicEventSummary;
    compact?: boolean;
}>();

const phase = computed(() => eventPhase(props.event));
const dateTile = computed(() => eventDateTile(props.event));
const dateRange = computed(() => eventDateRange(props.event));
</script>

<template>
    <Link
        :href="route('event.public.show', { id: event.id })"
        class="wp-surface wp-tile group flex h-full flex-col rounded-3xl p-2"
        @pointermove="trackSpotlight"
    >
        <div class="relative aspect-[16/10] overflow-hidden rounded-2xl">
            <AppImage
                v-if="event.foto_url"
                :src="event.foto_url"
                :alt="`Poster ${event.nama_event}`"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
            />
            <EventCoverPlaceholder v-else />

            <div v-if="dateTile" class="wp-glass absolute top-3 left-3 flex w-12 flex-col items-center rounded-xl py-1.5 leading-none">
                <span class="text-lg font-bold tabular-nums">{{ dateTile.day }}</span>
                <span class="text-muted-foreground mt-1 text-[0.65rem] font-semibold">{{ dateTile.month }}</span>
            </div>
            <span class="absolute top-3 right-3 rounded-md px-2 py-1 text-xs font-semibold" :class="eventPhaseClass(phase)">
                {{ EVENT_PHASE_LABEL[phase] }}
            </span>
        </div>

        <div class="flex flex-1 flex-col p-4 lg:p-5">
            <p v-if="!compact" class="text-muted-foreground text-xs font-medium">{{ event.kategori_event_nama }}</p>
            <h3 class="mt-1.5 line-clamp-2 text-lg leading-snug font-semibold tracking-[-0.015em]">{{ event.nama_event }}</h3>
            <p v-if="event.deskripsi_singkat" class="text-muted-foreground mt-2 line-clamp-2 text-sm leading-relaxed">
                {{ event.deskripsi_singkat }}
            </p>

            <ul class="text-muted-foreground mt-auto space-y-1.5 pt-5 text-xs">
                <li class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'calendar-days']" class="size-3.5 shrink-0 text-(--wp-accent)" />
                    <span class="tabular-nums">{{ dateRange }}</span>
                </li>
                <li v-if="event.lokasi" class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'location-dot']" class="size-3.5 shrink-0 text-(--wp-accent)" />
                    <span class="line-clamp-1">{{ event.lokasi }}</span>
                </li>
                <li v-if="compact" class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'medal']" class="size-3.5 shrink-0 text-(--wp-accent)" />
                    <span>{{ event.tingkat_event_nama }}</span>
                </li>
            </ul>

            <span class="wp-link-arrow mt-4 inline-flex items-center gap-2 text-sm font-semibold">
                Lihat detail
                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3 text-(--wp-accent)" />
            </span>
        </div>
    </Link>
</template>
