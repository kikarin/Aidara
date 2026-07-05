<script setup lang="ts">
import EventPublicHeader from '@/components/event/EventPublicHeader.vue';
import type { EventStatus, PublicEventSummary } from '@/types/event';
import { Head, Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    event: PublicEventSummary;
}>();

const statusClass = computed(() => {
    const map: Record<EventStatus, string> = {
        draft: 'bg-muted text-muted-foreground',
        publish: 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
        selesai: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
        dibatalkan: 'bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300',
    };

    return map[props.event.status] ?? map.draft;
});

const formatDate = (value: string | null, long = false) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: long ? 'long' : 'short',
        year: 'numeric',
    });
};

const dateRange = computed(() => {
    const mulai = formatDate(props.event.tanggal_mulai, true);
    const selesai = formatDate(props.event.tanggal_selesai, true);

    if (mulai === selesai || selesai === '-') {
        return mulai;
    }

    return `${mulai} – ${selesai}`;
});
</script>

<template>
    <Head :title="`${event.nama_event} — Event AIDARA`" />

    <div class="welcome-page bg-background text-foreground min-h-screen">
        <EventPublicHeader active="list" />

        <main class="mx-auto max-w-4xl px-6 py-10 lg:py-14">
            <Link :href="route('event.public.index')" class="text-muted-foreground hover:text-foreground mb-6 inline-flex text-sm transition-colors">
                ← Semua Event
            </Link>

            <article class="content-panel overflow-hidden">
                <div v-if="event.foto_url" class="aspect-[21/9] w-full overflow-hidden bg-muted">
                    <img :src="event.foto_url" :alt="event.nama_event" class="h-full w-full object-cover" />
                </div>

                <div class="space-y-6 p-6 lg:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-muted-foreground mb-2 text-xs font-semibold tracking-widest uppercase">
                                {{ event.kategori_event_nama }}
                            </p>
                            <h1 class="text-foreground text-2xl font-bold lg:text-3xl">{{ event.nama_event }}</h1>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass">
                            {{ event.status_label }}
                        </span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="bg-muted/40 flex items-center gap-3 rounded-xl px-4 py-3">
                            <CalendarDays class="text-[var(--brand-green,#2e7d32)] h-5 w-5 shrink-0" />
                            <div>
                                <p class="text-muted-foreground text-xs">Tanggal</p>
                                <p class="text-foreground text-sm font-medium">{{ dateRange }}</p>
                            </div>
                        </div>
                        <div v-if="event.lokasi" class="bg-muted/40 flex items-center gap-3 rounded-xl px-4 py-3">
                            <MapPin class="text-[var(--brand-green,#2e7d32)] h-5 w-5 shrink-0" />
                            <div>
                                <p class="text-muted-foreground text-xs">Lokasi</p>
                                <p class="text-foreground text-sm font-medium">{{ event.lokasi }}</p>
                            </div>
                        </div>
                        <div class="bg-muted/40 flex items-center gap-3 rounded-xl px-4 py-3">
                            <Trophy class="text-[var(--brand-green,#2e7d32)] h-5 w-5 shrink-0" />
                            <div>
                                <p class="text-muted-foreground text-xs">Tingkat</p>
                                <p class="text-foreground text-sm font-medium">{{ event.tingkat_event_nama }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="event.deskripsi" class="border-border/60 border-t pt-6">
                        <h2 class="text-foreground mb-3 text-lg font-semibold">Deskripsi</h2>
                        <div class="text-muted-foreground prose prose-sm dark:prose-invert max-w-none text-sm leading-relaxed whitespace-pre-line">
                            {{ event.deskripsi }}
                        </div>
                    </div>
                </div>
            </article>
        </main>
    </div>
</template>
