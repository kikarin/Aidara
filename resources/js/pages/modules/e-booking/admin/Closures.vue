<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { formatTanggalJamIndo } from '@/lib/format-tanggal';
import { router, useForm } from '@inertiajs/vue3';
import { CalendarX2, LoaderCircle, Trash2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';

type VenueOpt = { id: number; code: string; name: string };
type AreaOpt = { id: number; venue_id: number; code: string; name: string };
type ClosureRow = {
    id: number;
    venue_id: number;
    venue_name: string | null;
    area_id: number | null;
    area_name: string | null;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    reason: string | null;
    batch_id: string | null;
    batch_size: number | null;
    is_full_day: boolean;
    is_active: boolean;
};

const props = defineProps<{
    closures: {
        data: ClosureRow[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    venues: VenueOpt[];
    areas: AreaOpt[];
    filters: { venue_id: string; include_inactive: boolean };
}>();

const filterVenueId = computed({
    get: () => props.filters.venue_id,
    set: (value: string) => {
        router.get(
            route('e-booking.admin.closures.index'),
            {
                venue_id: value || undefined,
                include_inactive: props.filters.include_inactive ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    },
});

const form = useForm({
    mode: 'once' as 'once' | 'weekly',
    full_day: false,
    venue_id: props.filters.venue_id || (props.venues[0] ? String(props.venues[0].id) : ''),
    area_id: '',
    date: '',
    once_start_date: '',
    once_start_time: '06:00',
    once_end_date: '',
    once_end_time: '21:00',
    weekdays: [] as number[],
    start_date: '',
    end_date: '',
    start_time: '06:00',
    end_time: '21:00',
    reason: '',
});

const weekdayOptions = [
    { value: 1, label: 'Sen' },
    { value: 2, label: 'Sel' },
    { value: 3, label: 'Rab' },
    { value: 4, label: 'Kam' },
    { value: 5, label: 'Jum' },
    { value: 6, label: 'Sab' },
    { value: 0, label: 'Min' },
];

const toggleWeekday = (day: number) => {
    const index = form.weekdays.indexOf(day);
    if (index > -1) form.weekdays.splice(index, 1);
    else form.weekdays.push(day);
};

const filteredAreas = computed(() => {
    const vid = Number(form.venue_id);
    if (!vid) return props.areas;
    return props.areas.filter((a) => a.venue_id === vid);
});

const venueOptions = computed(() => props.venues.map((v) => ({ value: String(v.id), label: v.name })));
const areaOptions = computed(() => [
    { value: 'all', label: 'Seluruh venue' },
    ...filteredAreas.value.map((a) => ({ value: String(a.id), label: a.name })),
]);
const areaSelectValue = computed({
    get: () => form.area_id || 'all',
    set: (val: string | number) => {
        form.area_id = val === 'all' ? '' : String(val);
    },
});
const filterVenueOptions = computed(() => [
    { value: 'all', label: 'Semua venue' },
    ...props.venues.map((v) => ({ value: String(v.id), label: v.name })),
]);
const filterVenueSelect = computed({
    get: () => filterVenueId.value || 'all',
    set: (val: string | number) => {
        filterVenueId.value = val === 'all' ? '' : String(val);
    },
});

watch(
    () => form.venue_id,
    () => {
        form.area_id = '';
    },
);

const submit = () => {
    form.transform((data) => {
        const base = {
            mode: data.mode,
            full_day: data.full_day,
            venue_id: Number(data.venue_id),
            area_id: data.area_id ? Number(data.area_id) : null,
            reason: data.reason,
        };

        if (data.mode === 'weekly') {
            return {
                ...base,
                weekdays: data.weekdays,
                start_date: data.start_date,
                end_date: data.end_date,
                ...(data.full_day ? {} : { start_time: data.start_time, end_time: data.end_time }),
            };
        }

        if (data.full_day) {
            return { ...base, date: data.date };
        }

        return {
            ...base,
            starts_at: `${data.once_start_date}T${data.once_start_time}`,
            ends_at: `${data.once_end_date}T${data.once_end_time}`,
        };
    }).post(route('e-booking.admin.closures.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset(
                'date',
                'once_start_date',
                'once_start_time',
                'once_end_date',
                'once_end_time',
                'weekdays',
                'start_date',
                'end_date',
                'start_time',
                'end_time',
                'area_id',
            );
        },
    });
};

const remove = (row: ClosureRow) => {
    router.delete(route('e-booking.admin.closures.destroy', row.id), { preserveScroll: true });
};

const removeBatch = (row: ClosureRow) => {
    if (!row.batch_id) return;
    router.delete(route('e-booking.admin.closures.destroyBatch', row.batch_id), { preserveScroll: true });
};

const rowActions = (row: ClosureRow) => {
    const items = [
        {
            label: 'Hapus blok',
            icon: Trash2,
            variant: 'destructive' as const,
            confirm: {
                title: 'Hapus blok jadwal ini?',
                description: 'Slot akan tersedia kembali untuk penyewa.',
                confirmText: 'Hapus',
                variant: 'destructive' as const,
            },
            onClick: () => remove(row),
        },
    ];

    if (row.batch_id) {
        items.push({
            label: `Hapus seri (${row.batch_size ?? 1})`,
            icon: CalendarX2,
            variant: 'destructive' as const,
            confirm: {
                title: `Hapus seluruh seri (${row.batch_size ?? 1} tanggal)?`,
                description: 'Semua tanggal pada blok berulang ini akan dihapus.',
                confirmText: 'Hapus seri',
                variant: 'destructive' as const,
            },
            onClick: () => removeBatch(row),
        });
    }

    return items;
};
</script>

<template>
    <SeoHead title="Blok Jadwal E-Booking" />

    <AdminLayout active="closures">
        <h1 class="text-foreground text-2xl font-bold">Blok jadwal</h1>
        <p class="text-muted-foreground mt-1 text-sm">
            Tutup slot tanggal/jam per venue atau area — slot tertutup tampil merah di ketersediaan. Bisa sekali atau berulang mingguan (mis. setiap
            Senin selama 1 bulan).
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!-- Daftar blok -->
            <div class="lg:col-span-2">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="min-w-[220px]">
                        <label class="mb-1.5 block text-sm font-medium">Filter venue</label>
                        <SimpleSelect
                            v-model="filterVenueSelect"
                            :options="filterVenueOptions"
                            placeholder="Semua venue"
                            trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                        />
                    </div>
                </div>

                <div class="border-border mt-4 overflow-x-auto rounded-xl border bg-white">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="border-border text-muted-foreground border-b">
                            <tr>
                                <th class="py-2 pr-3 pl-3 font-medium">Venue / area</th>
                                <th class="py-2 pr-3 font-medium">Mulai</th>
                                <th class="py-2 pr-3 font-medium">Selesai</th>
                                <th class="py-2 pr-3 font-medium">Alasan</th>
                                <th class="py-2 pr-3 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in closures.data" :key="row.id" class="border-border/60 border-b">
                                <td class="py-3 pr-3 pl-3">
                                    <p class="font-medium">{{ row.venue_name }}</p>
                                    <p class="text-muted-foreground text-xs">{{ row.area_name || 'Seluruh venue' }}</p>
                                    <span
                                        v-if="row.batch_id"
                                        class="mt-1 inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold text-sky-700"
                                    >
                                        <CalendarX2 class="size-3" />
                                        Seri ×{{ row.batch_size ?? 1 }}
                                    </span>
                                    <span
                                        v-if="row.is_full_day"
                                        class="mt-1 inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700"
                                    >
                                        Full hari
                                    </span>
                                </td>
                                <td class="py-3 pr-3 whitespace-nowrap">{{ formatTanggalJamIndo(row.starts_at) }}</td>
                                <td class="py-3 pr-3 whitespace-nowrap">{{ formatTanggalJamIndo(row.ends_at) }}</td>
                                <td class="text-muted-foreground py-3 pr-3">{{ row.reason || '—' }}</td>
                                <td class="py-3 pr-3 text-right">
                                    <RowActionsMenu :items="rowActions(row)" />
                                </td>
                            </tr>
                            <tr v-if="closures.data.length === 0">
                                <td colspan="5" class="text-muted-foreground py-8 text-center">Belum ada blok jadwal.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <TablePagination :links="closures.links" :from="closures.from" :to="closures.to" :total="closures.total" label="blok jadwal" />
            </div>

            <!-- Form blok -->
            <div class="lg:col-span-1">
                <form class="border-border grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 lg:sticky lg:top-6" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Venue</label>
                        <SimpleSelect
                            v-model="form.venue_id"
                            :options="venueOptions"
                            placeholder="Pilih venue"
                            required
                            trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                        />
                        <InputError :message="form.errors.venue_id" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Area (opsional — kosong = seluruh venue)</label>
                        <SimpleSelect
                            v-model="areaSelectValue"
                            :options="areaOptions"
                            placeholder="Seluruh venue"
                            trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Jenis blok</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                :class="
                                    form.mode === 'once'
                                        ? 'border-sky-500 bg-sky-50 text-sky-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                "
                                @click="form.mode = 'once'"
                            >
                                Sekali
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                :class="
                                    form.mode === 'weekly'
                                        ? 'border-sky-500 bg-sky-50 text-sky-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                "
                                @click="form.mode = 'weekly'"
                            >
                                Berulang mingguan
                            </button>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Durasi blok</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                :class="
                                    !form.full_day
                                        ? 'border-sky-500 bg-sky-50 text-sky-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                "
                                @click="form.full_day = false"
                            >
                                Jam tertentu
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                :class="
                                    form.full_day
                                        ? 'border-rose-500 bg-rose-50 text-rose-800'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                "
                                @click="form.full_day = true"
                            >
                                Full hari
                            </button>
                        </div>
                        <p class="text-muted-foreground mt-1 text-xs">
                            Full hari memakai jam operasional venue (buka–tutup) sehingga tanggal tidak bisa dipesan.
                        </p>
                    </div>

                    <template v-if="form.mode === 'weekly'">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium">Hari</label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="day in weekdayOptions"
                                    :key="day.value"
                                    type="button"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                                    :class="
                                        form.weekdays.includes(day.value)
                                            ? 'border-sky-500 bg-sky-50 text-sky-800'
                                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                    "
                                    @click="toggleWeekday(day.value)"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                            <InputError :message="form.errors.weekdays" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Tanggal mulai</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                required
                            />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Tanggal selesai</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                required
                            />
                            <InputError :message="form.errors.end_date" />
                        </div>
                        <template v-if="!form.full_day">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Jam mulai</label>
                                <TimeField v-model="form.start_time" />
                                <InputError :message="form.errors.start_time" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Jam selesai</label>
                                <TimeField v-model="form.end_time" />
                                <InputError :message="form.errors.end_time" />
                            </div>
                        </template>
                    </template>
                    <template v-else-if="form.full_day">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium">Tanggal</label>
                            <input
                                v-model="form.date"
                                type="date"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                required
                            />
                            <InputError :message="form.errors.date" />
                        </div>
                    </template>
                    <template v-else>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Mulai — tanggal</label>
                            <input
                                v-model="form.once_start_date"
                                type="date"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                required
                            />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Mulai — jam</label>
                            <TimeField v-model="form.once_start_time" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Selesai — tanggal</label>
                            <input
                                v-model="form.once_end_date"
                                type="date"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                                required
                            />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Selesai — jam</label>
                            <TimeField v-model="form.once_end_time" />
                        </div>
                    </template>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Alasan</label>
                        <input
                            v-model="form.reason"
                            type="text"
                            maxlength="255"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            placeholder="Latihan atlet Dispora / libur nasional / maintenance…"
                        />
                        <InputError :message="form.errors.reason" />
                    </div>
                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[var(--brand-green,#2e7d32)] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                            :disabled="form.processing"
                        >
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            {{ form.mode === 'weekly' ? 'Tambah blok berulang' : 'Tambah blok' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
