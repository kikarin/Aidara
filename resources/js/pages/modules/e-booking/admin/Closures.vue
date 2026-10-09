<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { formatTanggalJamIndo } from '@/lib/format-tanggal';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCalendarCheck, faCalendarXmark, faCircleNotch, faRepeat, faTrashCan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

library.add(faCalendarCheck, faCalendarXmark, faCircleNotch, faRepeat, faTrashCan);

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
            icon: 'trash-can',
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
            icon: 'calendar-xmark',
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

// Server validates the transformed payload, so some error keys aren't form fields.
const serverError = (key: string) => (form.errors as Record<string, string | undefined>)[key];
</script>

<template>
    <SeoHead title="Blok Jadwal E-Booking" />

    <AdminLayout active="closures">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Blok jadwal</h1>
            <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                Tutup tanggal dan jam per venue atau area, sekali atau berulang mingguan. Slot yang ditutup tampil merah di ketersediaan.
            </p>
        </div>

        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_23rem]">
            <section aria-labelledby="closures-list-title" class="min-w-0">
                <div class="sb-card">
                    <div class="flex flex-wrap items-end justify-between gap-3 border-b border-(--wp-hairline) px-5 py-4">
                        <div>
                            <h2 id="closures-list-title" class="text-base font-semibold tracking-tight">Daftar blok</h2>
                            <p class="text-muted-foreground mt-0.5 text-sm tabular-nums">{{ closures.total }} blok tercatat</p>
                        </div>
                        <div class="w-full sm:w-56">
                            <span class="sr-only">Filter venue</span>
                            <SimpleSelect
                                v-model="filterVenueSelect"
                                :options="filterVenueOptions"
                                placeholder="Semua venue"
                                trigger-class="h-10 w-full rounded-xl border-0 bg-background px-3.5 text-sm shadow-none ring-1 ring-(--wp-hairline) ring-inset"
                            />
                        </div>
                    </div>

                    <div v-if="closures.data.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                        <span class="wp-icon size-12" aria-hidden="true">
                            <FontAwesomeIcon :icon="['fas', 'calendar-check']" class="size-5" />
                        </span>
                        <h3 class="mt-4 text-base font-semibold tracking-tight">Belum ada blok jadwal</h3>
                        <p class="text-muted-foreground mt-1 max-w-sm text-sm">
                            Semua slot masih terbuka. Gunakan form di samping untuk menutup tanggal atau jam tertentu.
                        </p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="sb-table min-w-[40rem]">
                            <caption class="sr-only">
                                Daftar blok jadwal
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Venue / area</th>
                                    <th scope="col">Mulai</th>
                                    <th scope="col">Selesai</th>
                                    <th scope="col">Alasan</th>
                                    <th scope="col" class="w-12"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in closures.data" :key="row.id">
                                    <td>
                                        <p class="text-foreground font-medium">{{ row.venue_name }}</p>
                                        <p class="text-muted-foreground mt-0.5 text-xs">{{ row.area_name || 'Seluruh venue' }}</p>
                                        <div v-if="row.batch_id || row.is_full_day" class="mt-1.5 flex flex-wrap gap-1.5">
                                            <span v-if="row.batch_id" class="sb-badge sb-tone-info tabular-nums">
                                                <FontAwesomeIcon :icon="['fas', 'repeat']" class="size-3" aria-hidden="true" />
                                                Seri ×{{ row.batch_size ?? 1 }}
                                            </span>
                                            <span v-if="row.is_full_day" class="sb-badge sb-tone-danger">Seharian</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap tabular-nums">{{ formatTanggalJamIndo(row.starts_at) }}</td>
                                    <td class="whitespace-nowrap tabular-nums">{{ formatTanggalJamIndo(row.ends_at) }}</td>
                                    <td class="text-muted-foreground">{{ row.reason || '—' }}</td>
                                    <td class="text-right">
                                        <RowActionsMenu :items="rowActions(row)" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <TablePagination :links="closures.links" :from="closures.from" :to="closures.to" :total="closures.total" label="blok jadwal" />
            </section>

            <aside aria-labelledby="closure-form-title" class="lg:sticky lg:top-32">
                <form class="sb-card grid gap-4 p-5 sm:grid-cols-2 sm:p-6" @submit.prevent="submit">
                    <div class="sm:col-span-2">
                        <h2 id="closure-form-title" class="text-base font-semibold tracking-tight">Tambah blok</h2>
                        <p class="text-muted-foreground mt-0.5 text-sm">Slot yang diblok tidak bisa dipesan penyewa.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="sb-label">Venue</span>
                        <SimpleSelect
                            v-model="form.venue_id"
                            :options="venueOptions"
                            placeholder="Pilih venue"
                            required
                            trigger-class="h-11 w-full rounded-xl border-0 bg-background px-3.5 text-sm shadow-none ring-1 ring-(--wp-hairline) ring-inset"
                        />
                        <p v-if="form.errors.venue_id" class="sb-error">{{ form.errors.venue_id }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="sb-label">Area</span>
                        <SimpleSelect
                            v-model="areaSelectValue"
                            :options="areaOptions"
                            placeholder="Seluruh venue"
                            trigger-class="h-11 w-full rounded-xl border-0 bg-background px-3.5 text-sm shadow-none ring-1 ring-(--wp-hairline) ring-inset"
                        />
                        <p class="sb-hint">Opsional. Biarkan "Seluruh venue" untuk menutup semua area.</p>
                    </div>

                    <fieldset class="sm:col-span-2">
                        <legend class="sb-label">Jenis blok</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="sb-chip justify-center"
                                :aria-pressed="form.mode === 'once' ? 'true' : 'false'"
                                @click="form.mode = 'once'"
                            >
                                Sekali
                            </button>
                            <button
                                type="button"
                                class="sb-chip justify-center"
                                :aria-pressed="form.mode === 'weekly' ? 'true' : 'false'"
                                @click="form.mode = 'weekly'"
                            >
                                Berulang mingguan
                            </button>
                        </div>
                    </fieldset>

                    <fieldset class="sm:col-span-2">
                        <legend class="sb-label">Durasi blok</legend>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="sb-chip justify-center"
                                :aria-pressed="!form.full_day ? 'true' : 'false'"
                                @click="form.full_day = false"
                            >
                                Jam tertentu
                            </button>
                            <button
                                type="button"
                                class="sb-chip justify-center"
                                :aria-pressed="form.full_day ? 'true' : 'false'"
                                @click="form.full_day = true"
                            >
                                Seharian
                            </button>
                        </div>
                        <p class="sb-hint">Seharian memakai jam operasional venue, sehingga tanggal tersebut tidak bisa dipesan.</p>
                    </fieldset>

                    <template v-if="form.mode === 'weekly'">
                        <fieldset class="sm:col-span-2">
                            <legend class="sb-label">Hari</legend>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="day in weekdayOptions"
                                    :key="day.value"
                                    type="button"
                                    class="sb-chip min-w-11 justify-center"
                                    :aria-pressed="form.weekdays.includes(day.value) ? 'true' : 'false'"
                                    @click="toggleWeekday(day.value)"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                            <p v-if="form.errors.weekdays" class="sb-error">{{ form.errors.weekdays }}</p>
                        </fieldset>
                        <div>
                            <label for="closure-start-date" class="sb-label">Tanggal mulai</label>
                            <input
                                id="closure-start-date"
                                v-model="form.start_date"
                                type="date"
                                class="sb-input tabular-nums"
                                :aria-invalid="form.errors.start_date ? 'true' : undefined"
                                required
                            />
                            <p v-if="form.errors.start_date" class="sb-error">{{ form.errors.start_date }}</p>
                        </div>
                        <div>
                            <label for="closure-end-date" class="sb-label">Tanggal selesai</label>
                            <input
                                id="closure-end-date"
                                v-model="form.end_date"
                                type="date"
                                class="sb-input tabular-nums"
                                :aria-invalid="form.errors.end_date ? 'true' : undefined"
                                required
                            />
                            <p v-if="form.errors.end_date" class="sb-error">{{ form.errors.end_date }}</p>
                        </div>
                        <template v-if="!form.full_day">
                            <div>
                                <span class="sb-label">Jam mulai</span>
                                <TimeField v-model="form.start_time" />
                                <p v-if="form.errors.start_time" class="sb-error">{{ form.errors.start_time }}</p>
                            </div>
                            <div>
                                <span class="sb-label">Jam selesai</span>
                                <TimeField v-model="form.end_time" />
                                <p v-if="form.errors.end_time" class="sb-error">{{ form.errors.end_time }}</p>
                            </div>
                        </template>
                    </template>
                    <template v-else-if="form.full_day">
                        <div class="sm:col-span-2">
                            <label for="closure-date" class="sb-label">Tanggal</label>
                            <input
                                id="closure-date"
                                v-model="form.date"
                                type="date"
                                class="sb-input tabular-nums"
                                :aria-invalid="form.errors.date ? 'true' : undefined"
                                required
                            />
                            <p v-if="form.errors.date" class="sb-error">{{ form.errors.date }}</p>
                        </div>
                    </template>
                    <template v-else>
                        <div>
                            <label for="closure-once-start-date" class="sb-label">Tanggal mulai</label>
                            <input
                                id="closure-once-start-date"
                                v-model="form.once_start_date"
                                type="date"
                                class="sb-input tabular-nums"
                                :aria-invalid="serverError('starts_at') ? 'true' : undefined"
                                required
                            />
                            <p v-if="serverError('starts_at')" class="sb-error">{{ serverError('starts_at') }}</p>
                        </div>
                        <div>
                            <span class="sb-label">Jam mulai</span>
                            <TimeField v-model="form.once_start_time" />
                        </div>
                        <div>
                            <label for="closure-once-end-date" class="sb-label">Tanggal selesai</label>
                            <input
                                id="closure-once-end-date"
                                v-model="form.once_end_date"
                                type="date"
                                class="sb-input tabular-nums"
                                :aria-invalid="serverError('ends_at') ? 'true' : undefined"
                                required
                            />
                            <p v-if="serverError('ends_at')" class="sb-error">{{ serverError('ends_at') }}</p>
                        </div>
                        <div>
                            <span class="sb-label">Jam selesai</span>
                            <TimeField v-model="form.once_end_time" />
                        </div>
                    </template>

                    <div class="sm:col-span-2">
                        <label for="closure-reason" class="sb-label">Alasan</label>
                        <input
                            id="closure-reason"
                            v-model="form.reason"
                            type="text"
                            maxlength="255"
                            class="sb-input"
                            :aria-invalid="form.errors.reason ? 'true' : undefined"
                            placeholder="Latihan atlet, libur nasional, perawatan…"
                        />
                        <p v-if="form.errors.reason" class="sb-error">{{ form.errors.reason }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="wp-btn wp-btn-primary w-full justify-center px-5 py-2.5 text-sm" :disabled="form.processing">
                            <FontAwesomeIcon
                                v-if="form.processing"
                                :icon="['fas', 'circle-notch']"
                                class="size-3.5 animate-spin"
                                aria-hidden="true"
                            />
                            <FontAwesomeIcon v-else :icon="['fas', 'calendar-xmark']" class="size-3.5" aria-hidden="true" />
                            {{ form.mode === 'weekly' ? 'Tambah blok berulang' : 'Tambah blok' }}
                        </button>
                    </div>
                </form>
            </aside>
        </div>
    </AdminLayout>
</template>
