<script setup lang="ts">
import EventPublicHeader from '@/components/event/EventPublicHeader.vue';
import EventCard from '@/components/event/EventCard.vue';
import type { PublicEventSummary } from '@/types/event';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    events: PublicEventSummary[];
}>();
</script>

<template>
    <Head title="Event & Kegiatan — AIDARA" />

    <div class="welcome-page bg-background text-foreground min-h-screen">
        <EventPublicHeader active="list" />

        <main class="mx-auto max-w-6xl px-6 py-10 lg:py-14">
            <div class="mb-8">
                <Link :href="route('home') + '#event'" class="text-muted-foreground hover:text-foreground mb-3 inline-flex text-sm transition-colors">
                    ← Kembali ke Beranda
                </Link>
                <h1 class="text-foreground text-3xl font-bold">Event &amp; Kegiatan</h1>
                <p class="text-muted-foreground mt-2 max-w-2xl text-sm">
                    Daftar lengkap kegiatan dan event olahraga Dispora Kabupaten Bogor.
                </p>
            </div>

            <div v-if="events.length > 0" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <EventCard v-for="event in events" :key="event.id" :event="event" />
            </div>

            <div v-else class="content-panel p-10 text-center">
                <p class="text-muted-foreground text-sm">Belum ada event yang dipublikasikan.</p>
            </div>
        </main>
    </div>
</template>
