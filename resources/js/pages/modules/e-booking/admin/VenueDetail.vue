<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import { Checkbox } from '@/components/ui/checkbox';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import type { IconName } from '@fortawesome/fontawesome-svg-core';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faBuilding,
    faCircleNotch,
    faFloppyDisk,
    faLayerGroup,
    faPen,
    faPlus,
    faPowerOff,
    faScroll,
    faTag,
    faTrash,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, h, ref, type FunctionalComponent } from 'vue';

library.add(faArrowLeft, faBuilding, faCircleNotch, faFloppyDisk, faLayerGroup, faPen, faPlus, faPowerOff, faScroll, faTag, faTrash);

const faAction =
    (name: IconName): FunctionalComponent =>
    (_, { attrs }) =>
        h(FontAwesomeIcon, { ...attrs, icon: ['fas', name], 'aria-hidden': 'true' });
const Pencil = faAction('pen');
const Power = faAction('power-off');
const Trash2 = faAction('trash');

const selectTrigger = 'h-10 w-full rounded-xl border-(--wp-hairline) bg-background px-3.5 text-sm shadow-none';
const invalid = (message?: string) => (message ? 'true' : undefined);

type Venue = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    is_active: boolean;
    sort_order: number;
};
type AreaRow = {
    id: number;
    code: string;
    name: string;
    is_tentative: boolean;
    is_active: boolean;
    sort_order: number;
};
type AreaOption = { id: number; code: string; name: string };
type TarifRow = {
    id: number;
    area_id: number | null;
    area_name: string | null;
    code: string | null;
    uraian: string;
    satuan: string;
    tarif_pemerintah: number | null;
    tarif_non_pemerintah: number | null;
    time_slot: string | null;
    event_level: string | null;
    category: string | null;
    min_hours: number | null;
    max_hours: number | null;
    is_active: boolean;
};
type RuleRow = {
    id: number;
    key: string;
    value: unknown;
    is_active: boolean;
    description: string | null;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};

const props = defineProps<{
    venue: Venue;
    areas: Paginator<AreaRow>;
    allAreas: AreaOption[];
    tarifs: Paginator<TarifRow>;
    rules: Record<string, unknown>;
    ruleList: Paginator<RuleRow>;
    options: { satuan: string[]; categories: string[] };
    terms: { value: string; label: string }[];
}>();

const satuanLabels: Record<string, string> = {
    per_match: 'Per pertandingan',
    per_day: 'Per hari',
    per_hour: 'Per jam',
    per_court_hour: 'Per lapangan/jam',
    per_unit_3hour: 'Per unit 3 jam',
    per_person: 'Per orang',
    per_m2_day: 'Per m²/hari',
    per_m2_month: 'Per m²/bulan',
    per_activity_day: 'Per kegiatan/hari',
};
const categoryLabels: Record<string, string> = {
    olahraga: 'Olahraga',
    non_olahraga: 'Non olahraga',
    sewa_lahan: 'Sewa lahan',
    ruang: 'Ruang',
};

const satuanOptions = computed(() => props.options.satuan.map((v) => ({ value: v, label: satuanLabels[v] ?? v })));
const categoryOptions = computed(() => props.options.categories.map((v) => ({ value: v, label: categoryLabels[v] ?? v })));
const AREA_NONE = '__none__';
const areaOptions = computed(() => [
    { value: AREA_NONE, label: '— Semua area (level venue)' },
    ...props.allAreas.map((a) => ({ value: String(a.id), label: a.name })),
]);

const formatRupiah = (value: number | null) => (value === null || value === undefined ? '—' : new Intl.NumberFormat('id-ID').format(value));

/* ---------- Area ---------- */
const areaEditingId = ref<number | null>(null);
const areaForm = useForm<{
    code: string;
    name: string;
    is_tentative: boolean;
    is_active: boolean;
    sort_order: number | string;
}>({
    code: '',
    name: '',
    is_tentative: false,
    is_active: true,
    sort_order: 0,
});

const startEditArea = (row: AreaRow) => {
    areaEditingId.value = row.id;
    areaForm.clearErrors();
    areaForm.code = row.code;
    areaForm.name = row.name;
    areaForm.is_tentative = row.is_tentative;
    areaForm.is_active = row.is_active;
    areaForm.sort_order = row.sort_order;
};

const resetAreaForm = () => {
    areaEditingId.value = null;
    areaForm.reset();
    areaForm.clearErrors();
};

const submitArea = () => {
    const options = { preserveScroll: true, onSuccess: () => resetAreaForm() };

    if (areaEditingId.value) {
        areaForm.put(route('e-booking.admin.areas.update', areaEditingId.value), options);
    } else {
        areaForm.post(route('e-booking.admin.areas.store', props.venue.id), options);
    }
};

const toggleArea = (row: AreaRow) => {
    router.post(route('e-booking.admin.areas.toggle', row.id), {}, { preserveScroll: true });
};

const areaRowActions = (row: AreaRow) => [
    { label: 'Edit', icon: Pencil, onClick: () => startEditArea(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan area ini?',
                  description: 'Area akan disembunyikan dari pilihan penyewa.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggleArea(row),
    },
];

/* ---------- Tarif ---------- */
const tarifEditingId = ref<number | null>(null);
const tarifForm = useForm<{
    area_id: string | number;
    code: string;
    uraian: string;
    satuan: string;
    tarif_pemerintah: number | string;
    tarif_non_pemerintah: number | string;
    time_slot: string;
    event_level: string;
    category: string;
    min_hours: number | string;
    max_hours: number | string;
    is_active: boolean;
}>({
    area_id: AREA_NONE,
    code: '',
    uraian: '',
    satuan: props.options.satuan[0] ?? 'per_hour',
    tarif_pemerintah: '',
    tarif_non_pemerintah: '',
    time_slot: '',
    event_level: '',
    category: 'olahraga',
    min_hours: '',
    max_hours: '',
    is_active: true,
});

const startEditTarif = (row: TarifRow) => {
    tarifEditingId.value = row.id;
    tarifForm.clearErrors();
    tarifForm.area_id = row.area_id ? String(row.area_id) : AREA_NONE;
    tarifForm.code = row.code ?? '';
    tarifForm.uraian = row.uraian;
    tarifForm.satuan = row.satuan;
    tarifForm.tarif_pemerintah = row.tarif_pemerintah ?? '';
    tarifForm.tarif_non_pemerintah = row.tarif_non_pemerintah ?? '';
    tarifForm.time_slot = row.time_slot ?? '';
    tarifForm.event_level = row.event_level ?? '';
    tarifForm.category = row.category ?? 'olahraga';
    tarifForm.min_hours = row.min_hours ?? '';
    tarifForm.max_hours = row.max_hours ?? '';
    tarifForm.is_active = row.is_active;
};

const resetTarifForm = () => {
    tarifEditingId.value = null;
    tarifForm.reset();
    tarifForm.clearErrors();
};

const submitTarif = () => {
    const transform = (data: ReturnType<typeof tarifForm.data>) => ({
        ...data,
        area_id: data.area_id && data.area_id !== AREA_NONE ? Number(data.area_id) : null,
        tarif_pemerintah: data.tarif_pemerintah === '' ? null : Number(data.tarif_pemerintah),
        tarif_non_pemerintah: data.tarif_non_pemerintah === '' ? null : Number(data.tarif_non_pemerintah),
        min_hours: data.min_hours === '' ? null : Number(data.min_hours),
        max_hours: data.max_hours === '' ? null : Number(data.max_hours),
        code: data.code || null,
        time_slot: data.time_slot || null,
        event_level: data.event_level || null,
    });

    const options = { preserveScroll: true, onSuccess: () => resetTarifForm() };

    if (tarifEditingId.value) {
        tarifForm.transform(transform).put(route('e-booking.admin.tarifs.update', tarifEditingId.value), options);
    } else {
        tarifForm.transform(transform).post(route('e-booking.admin.tarifs.store', props.venue.id), options);
    }
};

const toggleTarif = (row: TarifRow) => {
    router.post(route('e-booking.admin.tarifs.toggle', row.id), {}, { preserveScroll: true });
};

const tarifRowActions = (row: TarifRow) => [
    { label: 'Edit', icon: Pencil, onClick: () => startEditTarif(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan tarif ini?',
                  description: 'Tarif akan disembunyikan dari pilihan penyewa.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggleTarif(row),
    },
];

/* ---------- Aturan ---------- */
const days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

const toggleInArray = (arr: string[], value: string, checked: boolean) => {
    const index = arr.indexOf(value);
    if (checked && index === -1) arr.push(value);
    if (!checked && index > -1) arr.splice(index, 1);
};

const asStr = (value: unknown) => (value === null || value === undefined ? '' : String(value));
const asNum = (value: unknown): number | string => (value === null || value === undefined || value === '' ? '' : Number(value));

type RuleForm = {
    operating_start: string;
    operating_end: string;
    operating_timezone: string;
    operating_days: string[];
    booking_horizon_days: number | string;
    buffer_before_days: number | string;
    buffer_after_days: number | string;
    cancel_deadline: string;
    cancel_on_day_h: string;
    allow_same_day_reschedule: boolean;
    allow_same_day_court_change: boolean;
    force_majeure_rain_before_play: string;
    force_majeure_rain_after_play_minutes: number | string;
    force_majeure_rain_after_play_decision: string;
    tentative_areas: string[];
    tentative_priority: string;
    terms_key: string;
    prefer_booking_on_weekday: string;
    advance_payment_required: boolean;
    early_arrival_minutes_min: number | string;
    early_arrival_minutes_max: number | string;
    leave_court_after_minutes: number | string;
    adjacent_empty_court_counts_as_rental: boolean;
};

const operatingHours = computed(() => (props.rules.operating_hours ?? {}) as { start?: string; end?: string; timezone?: string });

const ruleForm = useForm<RuleForm>({
    operating_start: asStr(operatingHours.value.start ?? '06:00'),
    operating_end: asStr(operatingHours.value.end ?? '21:00'),
    operating_timezone: asStr(operatingHours.value.timezone ?? 'Asia/Jakarta'),
    operating_days: (props.rules.operating_days as string[] | undefined) ?? days,
    booking_horizon_days: asNum(props.rules.booking_horizon_days),
    buffer_before_days: asNum(props.rules.buffer_before_days),
    buffer_after_days: asNum(props.rules.buffer_after_days),
    cancel_deadline: asStr(props.rules.cancel_deadline),
    cancel_on_day_h: asStr(props.rules.cancel_on_day_h),
    allow_same_day_reschedule: Boolean(props.rules.allow_same_day_reschedule ?? false),
    allow_same_day_court_change: Boolean(props.rules.allow_same_day_court_change ?? false),
    force_majeure_rain_before_play: asStr(props.rules.force_majeure_rain_before_play),
    force_majeure_rain_after_play_minutes: asNum(props.rules.force_majeure_rain_after_play_minutes),
    force_majeure_rain_after_play_decision: asStr(props.rules.force_majeure_rain_after_play_decision),
    tentative_areas: (props.rules.tentative_areas as string[] | undefined) ?? [],
    tentative_priority: asStr(props.rules.tentative_priority),
    terms_key: asStr(props.rules.terms_key),
    prefer_booking_on_weekday: asStr(props.rules.prefer_booking_on_weekday),
    advance_payment_required: Boolean(props.rules.advance_payment_required ?? true),
    early_arrival_minutes_min: asNum(props.rules.early_arrival_minutes_min),
    early_arrival_minutes_max: asNum(props.rules.early_arrival_minutes_max),
    leave_court_after_minutes: asNum(props.rules.leave_court_after_minutes),
    adjacent_empty_court_counts_as_rental: Boolean(props.rules.adjacent_empty_court_counts_as_rental ?? true),
});

const toInt = (value: number | string) => (value === '' ? null : Number(value));

const submitRules = () => {
    const rules = {
        operating_hours: {
            start: ruleForm.operating_start,
            end: ruleForm.operating_end,
            timezone: ruleForm.operating_timezone,
        },
        operating_days: ruleForm.operating_days,
        booking_horizon_days: toInt(ruleForm.booking_horizon_days),
        buffer_before_days: toInt(ruleForm.buffer_before_days),
        buffer_after_days: toInt(ruleForm.buffer_after_days),
        cancel_deadline: ruleForm.cancel_deadline || null,
        cancel_on_day_h: ruleForm.cancel_on_day_h || null,
        allow_same_day_reschedule: ruleForm.allow_same_day_reschedule,
        allow_same_day_court_change: ruleForm.allow_same_day_court_change,
        force_majeure_rain_before_play: ruleForm.force_majeure_rain_before_play || null,
        force_majeure_rain_after_play_minutes: toInt(ruleForm.force_majeure_rain_after_play_minutes),
        force_majeure_rain_after_play_decision: ruleForm.force_majeure_rain_after_play_decision || null,
        tentative_areas: ruleForm.tentative_areas,
        tentative_priority: ruleForm.tentative_priority || null,
        terms_key: ruleForm.terms_key || null,
        prefer_booking_on_weekday: ruleForm.prefer_booking_on_weekday || null,
        advance_payment_required: ruleForm.advance_payment_required,
        early_arrival_minutes_min: toInt(ruleForm.early_arrival_minutes_min),
        early_arrival_minutes_max: toInt(ruleForm.early_arrival_minutes_max),
        leave_court_after_minutes: toInt(ruleForm.leave_court_after_minutes),
        adjacent_empty_court_counts_as_rental: ruleForm.adjacent_empty_court_counts_as_rental,
    };

    ruleForm
        .transform(() => ({ rules }))
        .put(route('e-booking.admin.venues.rules.update', props.venue.id), {
            preserveScroll: true,
            onFinish: () => ruleForm.transform((data) => data),
        });
};

const newRuleForm = useForm<{ key: string; value: string; is_active: boolean }>({
    key: '',
    value: '',
    is_active: true,
});

const addRule = () => {
    newRuleForm.post(route('e-booking.admin.venues.rules.store', props.venue.id), {
        preserveScroll: true,
        onSuccess: () => newRuleForm.reset(),
    });
};

const removeRule = (id: number) => {
    router.delete(route('e-booking.admin.rules.destroy', id), { preserveScroll: true });
};

const ruleRowActions = (row: RuleRow) => [
    {
        label: 'Hapus',
        icon: Trash2,
        variant: 'destructive' as const,
        confirm: {
            title: 'Hapus aturan ini?',
            description: 'Aturan akan dihapus permanen.',
            confirmText: 'Hapus',
            variant: 'destructive' as const,
        },
        onClick: () => removeRule(row.id),
    },
];

const ruleValueLabel = (value: unknown) => (typeof value === 'object' && value !== null ? JSON.stringify(value) : String(value ?? '—'));
</script>

<template>
    <SeoHead :title="`Venue ${venue.name} — E-Booking`" />

    <AdminLayout active="venues">
        <Link
            :href="route('e-booking.admin.venues.index')"
            class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-sm text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
        >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
            Kembali ke daftar venue
        </Link>

        <header class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-4">
                <AppImage v-if="venue.cover_url" :src="venue.cover_url" :alt="venue.name" class="size-16 shrink-0 rounded-xl object-cover" />
                <div v-else class="wp-icon size-16 shrink-0 rounded-xl" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'building']" class="size-6" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl">{{ venue.name }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2">
                        <span class="text-muted-foreground font-mono text-xs">{{ venue.code }}</span>
                        <span class="sb-badge" :class="venue.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                            {{ venue.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
            </div>
            <Link :href="route('e-booking.admin.venues.edit', venue.id)" class="wp-btn wp-btn-quiet px-4 py-2 text-sm">
                <FontAwesomeIcon :icon="['fas', 'pen']" class="size-3.5" aria-hidden="true" />
                Ubah venue
            </Link>
        </header>

        <div class="mt-8 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
            <Tabs default-value="area" class="min-w-0">
                <TabsList class="sb-card-muted w-fit gap-1 p-1">
                    <TabsTrigger value="area" class="text-muted-foreground gap-2 rounded-xl px-4 py-2">
                        Area <span class="tabular-nums">({{ areas.total }})</span>
                    </TabsTrigger>
                    <TabsTrigger value="tarif" class="text-muted-foreground gap-2 rounded-xl px-4 py-2">
                        Tarif <span class="tabular-nums">({{ tarifs.total }})</span>
                    </TabsTrigger>
                    <TabsTrigger value="aturan" class="text-muted-foreground gap-2 rounded-xl px-4 py-2">Aturan</TabsTrigger>
                </TabsList>

                <!-- Area -->
                <TabsContent value="area" class="mt-5 space-y-5">
                    <form class="sb-card p-5 sm:p-6" @submit.prevent="submitArea">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold tracking-tight">{{ areaEditingId ? 'Ubah area' : 'Tambah area' }}</h2>
                            <button v-if="areaEditingId" type="button" class="wp-btn wp-btn-quiet px-3 py-1.5 text-xs" @click="resetAreaForm">
                                Batal edit
                            </button>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="area-code" class="sb-label">Kode</label>
                                <input
                                    id="area-code"
                                    v-model="areaForm.code"
                                    type="text"
                                    maxlength="64"
                                    placeholder="lapangan_utama"
                                    class="sb-input font-mono"
                                    :aria-invalid="invalid(areaForm.errors.code)"
                                    :aria-describedby="areaForm.errors.code ? 'area-code-error' : undefined"
                                />
                                <p v-if="areaForm.errors.code" id="area-code-error" class="sb-error">{{ areaForm.errors.code }}</p>
                            </div>
                            <div>
                                <label for="area-name" class="sb-label">Nama</label>
                                <input
                                    id="area-name"
                                    v-model="areaForm.name"
                                    type="text"
                                    maxlength="150"
                                    placeholder="Lapangan Utama"
                                    class="sb-input"
                                    :aria-invalid="invalid(areaForm.errors.name)"
                                    :aria-describedby="areaForm.errors.name ? 'area-name-error' : undefined"
                                />
                                <p v-if="areaForm.errors.name" id="area-name-error" class="sb-error">{{ areaForm.errors.name }}</p>
                            </div>
                            <div>
                                <label for="area-sort" class="sb-label">Urutan</label>
                                <input
                                    id="area-sort"
                                    v-model="areaForm.sort_order"
                                    type="number"
                                    min="0"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(areaForm.errors.sort_order)"
                                    :aria-describedby="areaForm.errors.sort_order ? 'area-sort-error' : undefined"
                                />
                                <p v-if="areaForm.errors.sort_order" id="area-sort-error" class="sb-error">{{ areaForm.errors.sort_order }}</p>
                            </div>
                            <fieldset class="flex flex-wrap items-center gap-x-5 gap-y-2 sm:pt-7">
                                <legend class="sr-only">Status area</legend>
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <Checkbox v-model="areaForm.is_tentative" />
                                    Tentatif
                                </label>
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <Checkbox v-model="areaForm.is_active" />
                                    Aktif
                                </label>
                            </fieldset>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="areaForm.processing">
                                <FontAwesomeIcon
                                    v-if="areaForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'plus']" class="size-4" aria-hidden="true" />
                                {{ areaEditingId ? 'Simpan area' : 'Tambah area' }}
                            </button>
                        </div>
                    </form>

                    <div class="sb-card overflow-x-auto">
                        <table class="sb-table min-w-[560px]">
                            <caption class="sr-only">
                                Daftar area venue
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Nama</th>
                                    <th scope="col">Kode</th>
                                    <th scope="col">Tentatif</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in areas.data" :key="row.id">
                                    <td class="font-medium">{{ row.name }}</td>
                                    <td class="text-muted-foreground font-mono text-xs">{{ row.code }}</td>
                                    <td>
                                        <span v-if="row.is_tentative" class="sb-badge sb-tone-warning">Ya</span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td>
                                        <span class="sb-badge" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <RowActionsMenu :items="areaRowActions(row)" />
                                    </td>
                                </tr>
                                <tr v-if="areas.data.length === 0">
                                    <td colspan="5" class="text-muted-foreground py-10 text-center">Belum ada area.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <TablePagination :links="areas.links" :from="areas.from" :to="areas.to" :total="areas.total" label="area" />
                </TabsContent>

                <!-- Tarif -->
                <TabsContent value="tarif" class="mt-5 space-y-5">
                    <form class="sb-card p-5 sm:p-6" @submit.prevent="submitTarif">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="text-base font-semibold tracking-tight">{{ tarifEditingId ? 'Ubah tarif' : 'Tambah tarif' }}</h2>
                            <button v-if="tarifEditingId" type="button" class="wp-btn wp-btn-quiet px-3 py-1.5 text-xs" @click="resetTarifForm">
                                Batal edit
                            </button>
                        </div>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="sb-label">Area</span>
                                <SimpleSelect
                                    v-model="tarifForm.area_id"
                                    :options="areaOptions"
                                    placeholder="Tanpa area"
                                    :trigger-class="selectTrigger"
                                />
                                <span v-if="tarifForm.errors.area_id" class="sb-error block">{{ tarifForm.errors.area_id }}</span>
                            </label>
                            <div class="sm:col-span-2">
                                <label for="tarif-uraian" class="sb-label">Uraian</label>
                                <input
                                    id="tarif-uraian"
                                    v-model="tarifForm.uraian"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Latihan"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifForm.errors.uraian)"
                                    :aria-describedby="tarifForm.errors.uraian ? 'tarif-uraian-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.uraian" id="tarif-uraian-error" class="sb-error">{{ tarifForm.errors.uraian }}</p>
                            </div>
                            <label class="block">
                                <span class="sb-label">Satuan</span>
                                <SimpleSelect v-model="tarifForm.satuan" :options="satuanOptions" :trigger-class="selectTrigger" />
                                <span v-if="tarifForm.errors.satuan" class="sb-error block">{{ tarifForm.errors.satuan }}</span>
                            </label>
                            <label class="block">
                                <span class="sb-label">Kategori</span>
                                <SimpleSelect v-model="tarifForm.category" :options="categoryOptions" :trigger-class="selectTrigger" />
                                <span v-if="tarifForm.errors.category" class="sb-error block">{{ tarifForm.errors.category }}</span>
                            </label>
                            <div>
                                <label for="tarif-pemerintah" class="sb-label">Tarif instansi pemerintah</label>
                                <div class="relative">
                                    <span
                                        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs"
                                        aria-hidden="true"
                                        >Rp</span
                                    >
                                    <input
                                        id="tarif-pemerintah"
                                        v-model="tarifForm.tarif_pemerintah"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="sb-input pl-10 tabular-nums"
                                        :aria-invalid="invalid(tarifForm.errors.tarif_pemerintah)"
                                        :aria-describedby="tarifForm.errors.tarif_pemerintah ? 'tarif-pemerintah-error' : undefined"
                                    />
                                </div>
                                <p v-if="tarifForm.errors.tarif_pemerintah" id="tarif-pemerintah-error" class="sb-error">
                                    {{ tarifForm.errors.tarif_pemerintah }}
                                </p>
                            </div>
                            <div>
                                <label for="tarif-umum" class="sb-label">Tarif umum / non-pemerintah</label>
                                <div class="relative">
                                    <span
                                        class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs"
                                        aria-hidden="true"
                                        >Rp</span
                                    >
                                    <input
                                        id="tarif-umum"
                                        v-model="tarifForm.tarif_non_pemerintah"
                                        type="number"
                                        min="0"
                                        placeholder="0"
                                        class="sb-input pl-10 tabular-nums"
                                        :aria-invalid="invalid(tarifForm.errors.tarif_non_pemerintah)"
                                        :aria-describedby="tarifForm.errors.tarif_non_pemerintah ? 'tarif-umum-error' : undefined"
                                    />
                                </div>
                                <p v-if="tarifForm.errors.tarif_non_pemerintah" id="tarif-umum-error" class="sb-error">
                                    {{ tarifForm.errors.tarif_non_pemerintah }}
                                </p>
                            </div>
                            <div>
                                <label for="tarif-min" class="sb-label">Min jam (opsional)</label>
                                <input
                                    id="tarif-min"
                                    v-model="tarifForm.min_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    placeholder="—"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(tarifForm.errors.min_hours)"
                                    :aria-describedby="tarifForm.errors.min_hours ? 'tarif-min-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.min_hours" id="tarif-min-error" class="sb-error">{{ tarifForm.errors.min_hours }}</p>
                            </div>
                            <div>
                                <label for="tarif-max" class="sb-label">Max jam (opsional)</label>
                                <input
                                    id="tarif-max"
                                    v-model="tarifForm.max_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    placeholder="—"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(tarifForm.errors.max_hours)"
                                    :aria-describedby="tarifForm.errors.max_hours ? 'tarif-max-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.max_hours" id="tarif-max-error" class="sb-error">{{ tarifForm.errors.max_hours }}</p>
                            </div>
                            <div>
                                <label for="tarif-code" class="sb-label">Kode (opsional)</label>
                                <input
                                    id="tarif-code"
                                    v-model="tarifForm.code"
                                    type="text"
                                    maxlength="96"
                                    class="sb-input font-mono"
                                    :aria-invalid="invalid(tarifForm.errors.code)"
                                    :aria-describedby="tarifForm.errors.code ? 'tarif-code-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.code" id="tarif-code-error" class="sb-error">{{ tarifForm.errors.code }}</p>
                            </div>
                            <div>
                                <label for="tarif-slot" class="sb-label">Time slot (opsional)</label>
                                <input
                                    id="tarif-slot"
                                    v-model="tarifForm.time_slot"
                                    type="text"
                                    maxlength="32"
                                    placeholder="pagi/siang/malam"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifForm.errors.time_slot)"
                                    :aria-describedby="tarifForm.errors.time_slot ? 'tarif-slot-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.time_slot" id="tarif-slot-error" class="sb-error">{{ tarifForm.errors.time_slot }}</p>
                            </div>
                            <div>
                                <label for="tarif-level" class="sb-label">Event level (opsional)</label>
                                <input
                                    id="tarif-level"
                                    v-model="tarifForm.event_level"
                                    type="text"
                                    maxlength="64"
                                    placeholder="nasional"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifForm.errors.event_level)"
                                    :aria-describedby="tarifForm.errors.event_level ? 'tarif-level-error' : undefined"
                                />
                                <p v-if="tarifForm.errors.event_level" id="tarif-level-error" class="sb-error">{{ tarifForm.errors.event_level }}</p>
                            </div>
                            <div class="flex items-center gap-2 sm:pt-7">
                                <Checkbox id="tarif-active" v-model="tarifForm.is_active" />
                                <label for="tarif-active" class="text-sm font-medium">Aktif</label>
                            </div>
                        </div>
                        <div class="mt-5">
                            <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="tarifForm.processing">
                                <FontAwesomeIcon
                                    v-if="tarifForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'plus']" class="size-4" aria-hidden="true" />
                                {{ tarifEditingId ? 'Simpan tarif' : 'Tambah tarif' }}
                            </button>
                        </div>
                    </form>

                    <div class="sb-card overflow-x-auto">
                        <table class="sb-table min-w-[820px]">
                            <caption class="sr-only">
                                Daftar tarif venue
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">Uraian</th>
                                    <th scope="col">Area</th>
                                    <th scope="col">Satuan</th>
                                    <th scope="col">Durasi</th>
                                    <th scope="col" class="text-right">Instansi (Rp)</th>
                                    <th scope="col" class="text-right">Umum (Rp)</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="sr-only">Aksi</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in tarifs.data" :key="row.id">
                                    <td>
                                        <p class="font-medium">{{ row.uraian }}</p>
                                        <p class="text-muted-foreground text-xs">
                                            {{ row.category ? (categoryLabels[row.category] ?? row.category) : '—' }}
                                        </p>
                                    </td>
                                    <td>{{ row.area_name || 'Venue' }}</td>
                                    <td class="text-muted-foreground">{{ satuanLabels[row.satuan] ?? row.satuan }}</td>
                                    <td class="whitespace-nowrap tabular-nums">
                                        <template v-if="row.min_hours || row.max_hours">
                                            {{ row.min_hours ?? 1 }}–{{ row.max_hours ?? '∞' }} jam
                                        </template>
                                        <template v-else>—</template>
                                    </td>
                                    <td class="text-right whitespace-nowrap tabular-nums">{{ formatRupiah(row.tarif_pemerintah) }}</td>
                                    <td class="text-right whitespace-nowrap tabular-nums">{{ formatRupiah(row.tarif_non_pemerintah) }}</td>
                                    <td>
                                        <span class="sb-badge" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <RowActionsMenu :items="tarifRowActions(row)" />
                                    </td>
                                </tr>
                                <tr v-if="tarifs.data.length === 0">
                                    <td colspan="8" class="text-muted-foreground py-10 text-center">Belum ada tarif.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <TablePagination :links="tarifs.links" :from="tarifs.from" :to="tarifs.to" :total="tarifs.total" label="tarif" />
                </TabsContent>

                <!-- Aturan -->
                <TabsContent value="aturan" class="mt-5">
                    <form class="space-y-5" @submit.prevent="submitRules">
                        <section class="sb-card p-5 sm:p-6" aria-labelledby="rule-hours-title">
                            <h2 id="rule-hours-title" class="text-base font-semibold tracking-tight">Jam & hari operasional</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                <div>
                                    <span id="rule-open-label" class="sb-label">Jam buka</span>
                                    <div role="group" aria-labelledby="rule-open-label">
                                        <TimeField v-model="ruleForm.operating_start" />
                                    </div>
                                </div>
                                <div>
                                    <span id="rule-close-label" class="sb-label">Jam tutup</span>
                                    <div role="group" aria-labelledby="rule-close-label">
                                        <TimeField v-model="ruleForm.operating_end" />
                                    </div>
                                </div>
                                <div>
                                    <label for="rule-timezone" class="sb-label">Timezone</label>
                                    <input
                                        id="rule-timezone"
                                        v-model="ruleForm.operating_timezone"
                                        type="text"
                                        maxlength="64"
                                        placeholder="Asia/Jakarta"
                                        class="sb-input"
                                    />
                                </div>
                                <fieldset class="sm:col-span-3">
                                    <legend class="sb-label">Hari operasional</legend>
                                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                                        <label v-for="day in days" :key="day" class="flex items-center gap-2 text-sm capitalize">
                                            <Checkbox
                                                :model-value="ruleForm.operating_days.includes(day)"
                                                @update:model-value="(value) => toggleInArray(ruleForm.operating_days, day, !!value)"
                                            />
                                            {{ day }}
                                        </label>
                                    </div>
                                </fieldset>
                            </div>
                        </section>

                        <section class="sb-card p-5 sm:p-6" aria-labelledby="rule-buffer-title">
                            <h2 id="rule-buffer-title" class="text-base font-semibold tracking-tight">Batas & buffer booking</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                                <div>
                                    <label for="rule-horizon" class="sb-label">Horizon booking (hari)</label>
                                    <input
                                        id="rule-horizon"
                                        v-model="ruleForm.booking_horizon_days"
                                        type="number"
                                        min="1"
                                        placeholder="Tanpa batas"
                                        class="sb-input tabular-nums"
                                        aria-describedby="rule-horizon-hint"
                                    />
                                    <p id="rule-horizon-hint" class="sb-hint">Kosongkan jika tanpa batas.</p>
                                </div>
                                <div>
                                    <label for="rule-buffer-before" class="sb-label">Buffer sebelum (hari)</label>
                                    <input
                                        id="rule-buffer-before"
                                        v-model="ruleForm.buffer_before_days"
                                        type="number"
                                        min="0"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                                <div>
                                    <label for="rule-buffer-after" class="sb-label">Buffer sesudah (hari)</label>
                                    <input
                                        id="rule-buffer-after"
                                        v-model="ruleForm.buffer_after_days"
                                        type="number"
                                        min="0"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                            </div>
                        </section>

                        <section class="sb-card p-5 sm:p-6" aria-labelledby="rule-cancel-title">
                            <h2 id="rule-cancel-title" class="text-base font-semibold tracking-tight">Kebijakan pembatalan & hujan</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="rule-cancel-deadline" class="sb-label">Batas batal</label>
                                    <input
                                        id="rule-cancel-deadline"
                                        v-model="ruleForm.cancel_deadline"
                                        type="text"
                                        maxlength="32"
                                        placeholder="H-1"
                                        class="sb-input"
                                    />
                                </div>
                                <div>
                                    <label for="rule-cancel-day" class="sb-label">Batal hari H</label>
                                    <input
                                        id="rule-cancel-day"
                                        v-model="ruleForm.cancel_on_day_h"
                                        type="text"
                                        maxlength="32"
                                        placeholder="forfeited"
                                        class="sb-input"
                                    />
                                </div>
                                <div>
                                    <label for="rule-rain-before" class="sb-label">Hujan sebelum main</label>
                                    <input
                                        id="rule-rain-before"
                                        v-model="ruleForm.force_majeure_rain_before_play"
                                        type="text"
                                        maxlength="32"
                                        placeholder="reschedule"
                                        class="sb-input"
                                    />
                                </div>
                                <div>
                                    <label for="rule-rain-minutes" class="sb-label">Ambang hujan (menit)</label>
                                    <input
                                        id="rule-rain-minutes"
                                        v-model="ruleForm.force_majeure_rain_after_play_minutes"
                                        type="number"
                                        min="0"
                                        placeholder="20"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                                <div>
                                    <label for="rule-rain-decision" class="sb-label">Keputusan setelah ambang</label>
                                    <input
                                        id="rule-rain-decision"
                                        v-model="ruleForm.force_majeure_rain_after_play_decision"
                                        type="text"
                                        maxlength="32"
                                        placeholder="no_compensation"
                                        class="sb-input"
                                    />
                                </div>
                                <fieldset class="flex flex-col gap-2 sm:pt-7">
                                    <legend class="sr-only">Perubahan di hari H</legend>
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox v-model="ruleForm.allow_same_day_reschedule" />
                                        Izinkan reschedule hari H
                                    </label>
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox v-model="ruleForm.allow_same_day_court_change" />
                                        Izinkan ganti lapangan hari H
                                    </label>
                                </fieldset>
                            </div>
                        </section>

                        <section class="sb-card p-5 sm:p-6" aria-labelledby="rule-other-title">
                            <h2 id="rule-other-title" class="text-base font-semibold tracking-tight">Lainnya</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block">
                                        <span class="sb-label">Tata tertib</span>
                                        <SimpleSelect
                                            v-model="ruleForm.terms_key"
                                            :options="terms"
                                            placeholder="— Tanpa tata tertib —"
                                            :trigger-class="selectTrigger"
                                        />
                                    </label>
                                    <p class="sb-hint">
                                        Kelola isi di menu
                                        <Link
                                            :href="route('e-booking.admin.terms.index')"
                                            class="font-semibold text-(--wp-accent) hover:text-(--wp-accent-strong) hover:underline"
                                            >Tata tertib</Link
                                        >.
                                    </p>
                                </div>
                                <div>
                                    <label for="rule-prefer-day" class="sb-label">Prefer hari booking</label>
                                    <input
                                        id="rule-prefer-day"
                                        v-model="ruleForm.prefer_booking_on_weekday"
                                        type="text"
                                        maxlength="16"
                                        placeholder="monday"
                                        class="sb-input"
                                    />
                                </div>
                                <div>
                                    <label for="rule-tentative-priority" class="sb-label">Tentative priority</label>
                                    <input
                                        id="rule-tentative-priority"
                                        v-model="ruleForm.tentative_priority"
                                        type="text"
                                        maxlength="64"
                                        placeholder="kegiatan_pemerintah_daerah"
                                        class="sb-input"
                                    />
                                </div>
                                <div>
                                    <label for="rule-early-min" class="sb-label">Datang lebih awal (min)</label>
                                    <input
                                        id="rule-early-min"
                                        v-model="ruleForm.early_arrival_minutes_min"
                                        type="number"
                                        min="0"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                                <div>
                                    <label for="rule-early-max" class="sb-label">Datang lebih awal (max)</label>
                                    <input
                                        id="rule-early-max"
                                        v-model="ruleForm.early_arrival_minutes_max"
                                        type="number"
                                        min="0"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                                <div>
                                    <label for="rule-leave" class="sb-label">Keluar lapangan (menit)</label>
                                    <input
                                        id="rule-leave"
                                        v-model="ruleForm.leave_court_after_minutes"
                                        type="number"
                                        min="0"
                                        class="sb-input tabular-nums"
                                    />
                                </div>
                                <fieldset class="flex flex-col gap-2 sm:col-span-2">
                                    <legend class="sr-only">Pembayaran dan pemakaian lapangan</legend>
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox v-model="ruleForm.advance_payment_required" />
                                        Wajib bayar di muka
                                    </label>
                                    <label class="flex items-center gap-2 text-sm font-medium">
                                        <Checkbox v-model="ruleForm.adjacent_empty_court_counts_as_rental" />
                                        Lapangan sebelah kosong dihitung sewa
                                    </label>
                                </fieldset>
                                <fieldset class="sm:col-span-2">
                                    <legend class="sb-label">Area tentatif</legend>
                                    <div class="flex flex-wrap gap-x-4 gap-y-2">
                                        <label v-for="area in allAreas" :key="area.id" class="flex items-center gap-2 text-sm">
                                            <Checkbox
                                                :model-value="ruleForm.tentative_areas.includes(area.code)"
                                                @update:model-value="(value) => toggleInArray(ruleForm.tentative_areas, area.code, !!value)"
                                            />
                                            {{ area.name }}
                                        </label>
                                        <span v-if="allAreas.length === 0" class="text-muted-foreground text-sm">Belum ada area.</span>
                                    </div>
                                </fieldset>
                            </div>
                        </section>

                        <div class="wp-glass sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl px-4 py-3">
                            <p class="text-muted-foreground text-sm">Perubahan aturan berlaku untuk booking baru.</p>
                            <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="ruleForm.processing">
                                <FontAwesomeIcon
                                    v-if="ruleForm.processing"
                                    :icon="['fas', 'circle-notch']"
                                    class="size-4 animate-spin"
                                    aria-hidden="true"
                                />
                                <FontAwesomeIcon v-else :icon="['fas', 'floppy-disk']" class="size-4" aria-hidden="true" />
                                Simpan aturan
                            </button>
                        </div>
                    </form>

                    <section class="mt-10 space-y-4" aria-labelledby="rule-custom-title">
                        <div>
                            <h2 id="rule-custom-title" class="text-base font-semibold tracking-tight">Aturan lanjutan (key-value)</h2>
                            <p class="text-muted-foreground mt-1 text-sm">Aturan tambahan khusus venue ini.</p>
                        </div>

                        <div class="sb-card overflow-x-auto">
                            <table class="sb-table min-w-[640px]">
                                <caption class="sr-only">
                                    Daftar aturan lanjutan
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Key</th>
                                        <th scope="col">Value</th>
                                        <th scope="col">Aktif</th>
                                        <th scope="col"><span class="sr-only">Aksi</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in ruleList.data" :key="row.id">
                                        <td class="font-mono text-xs">{{ row.key }}</td>
                                        <td class="text-muted-foreground max-w-[320px] break-all whitespace-normal">
                                            {{ ruleValueLabel(row.value) }}
                                        </td>
                                        <td>
                                            <span class="sb-badge" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                                {{ row.is_active ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <RowActionsMenu :items="ruleRowActions(row)" />
                                        </td>
                                    </tr>
                                    <tr v-if="ruleList.data.length === 0">
                                        <td colspan="4" class="text-muted-foreground py-8 text-center">Belum ada aturan khusus venue.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <TablePagination :links="ruleList.links" :from="ruleList.from" :to="ruleList.to" :total="ruleList.total" label="aturan" />

                        <form
                            class="sb-card-muted grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-start"
                            @submit.prevent="addRule"
                        >
                            <div>
                                <label for="new-rule-key" class="sb-label">Key</label>
                                <input
                                    id="new-rule-key"
                                    v-model="newRuleForm.key"
                                    type="text"
                                    maxlength="96"
                                    placeholder="custom_key"
                                    class="sb-input font-mono"
                                    :aria-invalid="invalid(newRuleForm.errors.key)"
                                    :aria-describedby="newRuleForm.errors.key ? 'new-rule-key-error' : undefined"
                                />
                                <p v-if="newRuleForm.errors.key" id="new-rule-key-error" class="sb-error">{{ newRuleForm.errors.key }}</p>
                            </div>
                            <div>
                                <label for="new-rule-value" class="sb-label">Value</label>
                                <input
                                    id="new-rule-value"
                                    v-model="newRuleForm.value"
                                    type="text"
                                    placeholder="nilai"
                                    class="sb-input"
                                    :aria-invalid="invalid(newRuleForm.errors.value)"
                                    :aria-describedby="newRuleForm.errors.value ? 'new-rule-value-error' : undefined"
                                />
                                <p v-if="newRuleForm.errors.value" id="new-rule-value-error" class="sb-error">{{ newRuleForm.errors.value }}</p>
                            </div>
                            <div class="flex items-center gap-4 sm:pt-7">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <Checkbox v-model="newRuleForm.is_active" />
                                    Aktif
                                </label>
                                <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="newRuleForm.processing">
                                    <FontAwesomeIcon
                                        v-if="newRuleForm.processing"
                                        :icon="['fas', 'circle-notch']"
                                        class="size-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                    <FontAwesomeIcon v-else :icon="['fas', 'plus']" class="size-4" aria-hidden="true" />
                                    Tambah
                                </button>
                            </div>
                        </form>
                    </section>
                </TabsContent>
            </Tabs>

            <aside class="sb-card p-5 lg:sticky lg:top-32" aria-labelledby="venue-summary-title">
                <h2 id="venue-summary-title" class="text-base font-semibold tracking-tight">Ringkasan venue</h2>
                <p v-if="venue.description" class="text-muted-foreground mt-2 text-sm">{{ venue.description }}</p>
                <dl class="mt-4 divide-y divide-(--wp-hairline) text-sm">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground">Kode</dt>
                        <dd class="font-mono text-xs">{{ venue.code }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground">Status</dt>
                        <dd>
                            <span class="sb-badge" :class="venue.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                {{ venue.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'layer-group']" class="size-3.5" aria-hidden="true" />
                            Area
                        </dt>
                        <dd class="font-semibold tabular-nums">{{ areas.total }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'tag']" class="size-3.5" aria-hidden="true" />
                            Tarif
                        </dt>
                        <dd class="font-semibold tabular-nums">{{ tarifs.total }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground flex items-center gap-2">
                            <FontAwesomeIcon :icon="['fas', 'scroll']" class="size-3.5" aria-hidden="true" />
                            Aturan lanjutan
                        </dt>
                        <dd class="font-semibold tabular-nums">{{ ruleList.total }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground">Jam operasional</dt>
                        <dd class="font-medium tabular-nums">{{ operatingHours.start ?? '—' }}–{{ operatingHours.end ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <dt class="text-muted-foreground">Urutan tampil</dt>
                        <dd class="font-medium tabular-nums">{{ venue.sort_order }}</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </AdminLayout>
</template>
