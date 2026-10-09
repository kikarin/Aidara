<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faChevronLeft, faChevronRight, faCircleNotch } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { computed, onMounted, ref, watch } from 'vue';

library.add(faChevronLeft, faChevronRight, faCircleNotch);

type DayCell = {
    date: string;
    status: string;
    bookable: boolean;
    pengajuan?: boolean;
    reason: string | null;
};

const props = defineProps<{
    venueId: number;
    areaIds?: number[];
    modelValue: string;
    isPerHari?: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const monthCursor = ref(props.modelValue ? props.modelValue.slice(0, 7) : new Date().toISOString().slice(0, 7));
const days = ref<DayCell[]>([]);
const loading = ref(false);
const error = ref('');

const monthLabel = computed(() => {
    const [y, m] = monthCursor.value.split('-').map(Number);
    const date = new Date(y, m - 1, 1);

    return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
});

const weekdayLabels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const dayMap = computed(() => {
    const map = new Map<string, DayCell>();
    for (const day of days.value) {
        map.set(day.date, day);
    }

    return map;
});

const calendarCells = computed(() => {
    const [y, m] = monthCursor.value.split('-').map(Number);
    const first = new Date(y, m - 1, 1);
    // Monday-first: JS getDay Sun=0 → remap
    const startPad = (first.getDay() + 6) % 7;
    const daysInMonth = new Date(y, m, 0).getDate();
    const cells: Array<{ key: string; date: string | null; dayNum: number | null; meta: DayCell | null }> = [];

    for (let i = 0; i < startPad; i++) {
        cells.push({ key: `pad-${i}`, date: null, dayNum: null, meta: null });
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const date = `${monthCursor.value}-${String(d).padStart(2, '0')}`;
        cells.push({
            key: date,
            date,
            dayNum: d,
            meta: dayMap.value.get(date) ?? null,
        });
    }

    return cells;
});

const shiftMonth = (delta: number) => {
    const [y, m] = monthCursor.value.split('-').map(Number);
    const next = new Date(y, m - 1 + delta, 1);
    const ym = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`;
    monthCursor.value = ym;
};

const cellClass = (meta: DayCell | null, selected: boolean) => {
    if (!meta) {
        return 'invisible';
    }
    if (selected) {
        return 'bg-(--wp-accent) text-(--wp-accent-contrast)';
    }
    if (meta.status === 'past' || !meta.bookable) {
        if (meta.status === 'merah' || meta.status === 'past') {
            return 'cursor-not-allowed bg-muted text-muted-foreground line-through';
        }

        return 'cursor-not-allowed bg-muted/50 text-muted-foreground';
    }

    return 'bg-(--wp-accent-soft) text-(--wp-accent-strong) ring-1 ring-(--wp-hairline) ring-inset hover:ring-(--wp-accent)';
};

const todayIso = (() => {
    const d = new Date();

    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
})();

const dayTitle = (meta: DayCell | null, date: string) => {
    if (!meta) {
        return date;
    }
    const parts: string[] = [];
    if (meta.reason) {
        parts.push(meta.reason);
    }
    if (meta.pengajuan) {
        parts.push('Ada pengajuan — masih bisa diajukan');
    }

    return parts.length > 0 ? parts.join(' • ') : date;
};

const selectDay = (meta: DayCell | null) => {
    if (!meta?.bookable) {
        return;
    }
    emit('update:modelValue', meta.date);
};

const loadMonth = async () => {
    loading.value = true;
    error.value = '';

    try {
        const params = new URLSearchParams({
            month: monthCursor.value,
            is_per_hari: props.isPerHari === false ? '0' : '1',
        });
        for (const areaId of props.areaIds ?? []) {
            params.append('area_ids[]', String(areaId));
        }

        const res = await fetch(`${route('e-booking.venues.month-overview', props.venueId)}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Gagal memuat kalender.');
        }
        days.value = json.data.days ?? [];
    } catch (e) {
        days.value = [];
        error.value = e instanceof Error ? e.message : 'Gagal memuat kalender.';
    } finally {
        loading.value = false;
    }
};

watch(
    () => [monthCursor.value, (props.areaIds ?? []).join(','), props.isPerHari] as const,
    () => {
        loadMonth();
    },
);

watch(
    () => props.modelValue,
    (val) => {
        if (val && val.slice(0, 7) !== monthCursor.value) {
            monthCursor.value = val.slice(0, 7);
        }
    },
);

onMounted(() => {
    if (props.modelValue) {
        monthCursor.value = props.modelValue.slice(0, 7);
    }
    loadMonth();
});
</script>

<template>
    <div class="sb-card-muted p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-2">
            <button
                type="button"
                class="bg-card text-foreground inline-flex size-9 items-center justify-center rounded-full ring-1 ring-(--wp-hairline) transition ring-inset hover:bg-(--wp-accent-soft) hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                aria-label="Bulan sebelumnya"
                @click="shiftMonth(-1)"
            >
                <FontAwesomeIcon :icon="['fas', 'chevron-left']" class="size-3.5" aria-hidden="true" />
            </button>
            <div class="text-center">
                <p class="text-foreground text-sm font-semibold tracking-tight capitalize tabular-nums" aria-live="polite">{{ monthLabel }}</p>
                <p class="text-muted-foreground text-xs">Klik tanggal hijau untuk memilih</p>
            </div>
            <button
                type="button"
                class="bg-card text-foreground inline-flex size-9 items-center justify-center rounded-full ring-1 ring-(--wp-hairline) transition ring-inset hover:bg-(--wp-accent-soft) hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                aria-label="Bulan berikutnya"
                @click="shiftMonth(1)"
            >
                <FontAwesomeIcon :icon="['fas', 'chevron-right']" class="size-3.5" aria-hidden="true" />
            </button>
        </div>

        <div v-if="loading" class="space-y-2" aria-busy="true" aria-label="Memuat kalender">
            <div class="text-muted-foreground flex items-center gap-2 text-xs">
                <FontAwesomeIcon :icon="['fas', 'circle-notch']" class="size-3.5 animate-spin" aria-hidden="true" />
                Memuat ketersediaan…
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                <Skeleton v-for="n in 28" :key="n" class="aspect-square rounded-xl" />
            </div>
        </div>

        <p v-else-if="error" class="sb-callout sb-tone-danger" role="alert">{{ error }}</p>

        <template v-else>
            <div class="mb-1.5 grid grid-cols-7 gap-1.5" aria-hidden="true">
                <div v-for="label in weekdayLabels" :key="label" class="text-muted-foreground text-center text-xs font-medium">
                    {{ label }}
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                <button
                    v-for="cell in calendarCells"
                    :key="cell.key"
                    type="button"
                    class="focus-visible:outline-foreground relative aspect-square rounded-xl text-sm font-semibold tabular-nums transition focus-visible:outline-2 focus-visible:outline-offset-2"
                    :class="[
                        cellClass(cell.meta, cell.date === modelValue),
                        cell.date === todayIso && cell.date !== modelValue ? 'outline-2 outline-offset-1 outline-(--wp-accent)' : '',
                    ]"
                    :disabled="!cell.meta || !cell.meta.bookable"
                    :title="dayTitle(cell.meta, cell.date || '')"
                    :aria-pressed="cell.date === modelValue"
                    :aria-current="cell.date === todayIso ? 'date' : undefined"
                    @click="selectDay(cell.meta)"
                >
                    {{ cell.dayNum }}
                    <span
                        v-if="cell.meta?.pengajuan && cell.meta.bookable && cell.date !== modelValue"
                        class="absolute top-1 right-1 size-1.5 rounded-full bg-(--sb-warning)"
                        aria-hidden="true"
                    />
                </button>
            </div>
        </template>

        <ul class="text-muted-foreground mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs">
            <li class="inline-flex items-center gap-1.5">
                <span class="size-3 rounded-sm bg-(--wp-accent-soft) ring-1 ring-(--wp-hairline) ring-inset" aria-hidden="true" />
                Kosong / tersedia
            </li>
            <li class="inline-flex items-center gap-1.5">
                <span class="size-1.5 rounded-full bg-(--sb-warning)" aria-hidden="true" />
                Ada pengajuan (masih bisa diajukan)
            </li>
            <li class="inline-flex items-center gap-1.5">
                <span class="bg-muted size-3 rounded-sm" aria-hidden="true" />
                Disetujui / dipesan / tutup / lewat
            </li>
            <li class="inline-flex items-center gap-1.5">
                <span class="size-3 rounded-sm outline-2 outline-offset-0 outline-(--wp-accent)" aria-hidden="true" />
                Hari ini
            </li>
        </ul>
    </div>
</template>
