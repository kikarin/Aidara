<script setup lang="ts">
import EventCard from '@/components/event/EventCard.vue';
import type { PublicEventSummary } from '@/types/event';
import { Link } from '@inertiajs/vue3';

defineProps<{
    events: PublicEventSummary[];
}>();
</script>

<template>
    <section id="event">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <div class="mb-8 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Kegiatan Olahraga</p>
                    <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Event &amp; Kegiatan</h2>
                    <p class="text-muted-foreground mt-2 max-w-2xl text-sm">
                        Informasi kegiatan dan event olahraga yang diselenggarakan oleh Dispora Kabupaten Bogor.
                    </p>
                </div>
                <Link
                    v-if="events.length > 0"
                    :href="route('event.public.index')"
                    class="welcome-cta inline-flex shrink-0 items-center rounded-md px-5 py-2 text-sm font-medium transition-colors"
                >
                    Semua Event →
                </Link>
            </div>

            <div v-if="events.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <EventCard v-for="event in events" :key="event.id" :event="event" compact />
            </div>

            <div v-else class="content-panel p-8 text-center">
                <p class="text-muted-foreground text-sm">Belum ada event yang dipublikasikan saat ini.</p>
            </div>
        </div>
    </section>
</template>
