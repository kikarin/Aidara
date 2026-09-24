<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton';
import { ChevronLeft, ChevronRight, LoaderCircle } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

type DayCell = {
    date: string;
    status: string;
    bookable: boolean;
    reason: string | null;
};

const props = defineProps<{
    venueId: number;
    areaId?: number | '' | null;
    modelValue: string;
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
        return 'cursor-default text-transparent';
    }
    if (selected) {
        return 'border-sky-600 bg-sky-600 text-white shadow-sm';
    }
    if (meta.status === 'past' || !meta.bookable) {
        if (meta.status === 'merah' || meta.status === 'past') {
            return 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 line-through';
        }

        return 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400';
    }
    if (meta.status === 'kuning') {
        return 'border-amber-200 bg-amber-50 text-amber-900 hover:border-amber-400';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:border-emerald-400';
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
        const params = new URLSearchParams({ month: monthCursor.value });
        if (props.areaId !== '' && props.areaId != null) {
            params.set('area_id', String(props.areaId));
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
    () => [monthCursor.value, props.areaId] as const,
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
    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4">
        <div class="mb-3 flex items-center justify-between gap-2">
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100"
                aria-label="Bulan sebelumnya"
                @click="shiftMonth(-1)"
            >
                <ChevronLeft class="size-4" />
            </button>
            <div class="text-center">
                <p class="text-sm font-bold capitalize text-slate-900">{{ monthLabel }}</p>
                <p class="text-[11px] text-slate-500">Klik tanggal hijau untuk memilih</p>
            </div>
            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-100"
                aria-label="Bulan berikutnya"
                @click="shiftMonth(1)"
            >
                <ChevronRight class="size-4" />
            </button>
        </div>

        <div v-if="loading" class="space-y-2" aria-busy="true" aria-label="Memuat kalender">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <LoaderCircle class="size-3.5 animate-spin" />
                Memuat ketersediaan…
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                <Skeleton v-for="n in 28" :key="n" class="aspect-square rounded-xl" />
            </div>
        </div>

        <p v-else-if="error" class="text-sm text-red-600">{{ error }}</p>

        <template v-else>
            <div class="mb-1.5 grid grid-cols-7 gap-1.5">
                <div
                    v-for="label in weekdayLabels"
                    :key="label"
                    class="text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400"
                >
                    {{ label }}
                </div>
            </div>
            <div class="grid grid-cols-7 gap-1.5">
                <button
                    v-for="cell in calendarCells"
                    :key="cell.key"
                    type="button"
                    class="aspect-square rounded-xl border text-sm font-semibold transition"
                    :class="cellClass(cell.meta, cell.date === modelValue)"
                    :disabled="!cell.meta || !cell.meta.bookable"
                    :title="cell.meta?.reason || cell.date || ''"
                    @click="selectDay(cell.meta)"
                >
                    {{ cell.dayNum }}
                </button>
            </div>
        </template>

        <div class="mt-3 flex flex-wrap gap-3 text-[11px] text-slate-600">
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full bg-emerald-400" /> Kosong / tersedia
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full bg-amber-400" /> Ada antrean
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="size-2.5 rounded-full bg-slate-300" /> Penuh / tutup / lewat
            </span>
        </div>
    </div>
</template>
