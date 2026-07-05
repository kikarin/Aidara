<script setup lang="ts">
import type { EventStatus, PublicEventSummary } from '@/types/event';
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    event: PublicEventSummary;
    compact?: boolean;
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

const formatDate = (value: string | null) => {
    if (!value) {
        return '-';
    }

    return new Date(value).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const dateRange = computed(() => {
    const mulai = formatDate(props.event.tanggal_mulai);
    const selesai = formatDate(props.event.tanggal_selesai);

    if (mulai === selesai || selesai === '-') {
        return mulai;
    }

    return `${mulai} – ${selesai}`;
});
</script>

<template>
    <Link
        :href="route('event.public.show', { id: event.id })"
        class="content-panel group flex h-full flex-col overflow-hidden transition-shadow hover:shadow-md"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-muted">
            <img
                v-if="event.foto_url"
                :src="event.foto_url"
                :alt="event.nama_event"
                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-green-50 to-emerald-100 dark:from-green-950/30 dark:to-emerald-950/20"
            >
                <CalendarDays class="text-muted-foreground/50 h-12 w-12" />
            </div>
            <span
                class="absolute top-3 right-3 rounded-full px-2.5 py-0.5 text-xs font-semibold shadow-sm"
                :class="statusClass"
            >
                {{ event.status_label }}
            </span>
        </div>

        <div class="flex flex-1 flex-col p-4 lg:p-5">
            <p v-if="!compact" class="text-muted-foreground mb-1 text-xs font-medium tracking-wide uppercase">
                {{ event.kategori_event_nama }}
            </p>
            <h3 class="text-foreground group-hover:text-[var(--brand-green,#2e7d32)] mb-2 line-clamp-2 text-base font-bold transition-colors lg:text-lg">
                {{ event.nama_event }}
            </h3>
            <p v-if="event.deskripsi_singkat" class="text-muted-foreground mb-4 line-clamp-2 text-sm leading-relaxed">
                {{ event.deskripsi_singkat }}
            </p>

            <div class="text-muted-foreground mt-auto space-y-2 text-xs">
                <div class="flex items-center gap-2">
                    <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                    <span>{{ dateRange }}</span>
                </div>
                <div v-if="event.lokasi" class="flex items-center gap-2">
                    <MapPin class="h-3.5 w-3.5 shrink-0" />
                    <span class="line-clamp-1">{{ event.lokasi }}</span>
                </div>
                <p v-if="compact" class="text-muted-foreground/80">{{ event.tingkat_event_nama }}</p>
            </div>

            <p class="text-[var(--brand-green,#2e7d32)] mt-4 text-sm font-semibold">Lihat detail →</p>
        </div>
    </Link>
</template>
