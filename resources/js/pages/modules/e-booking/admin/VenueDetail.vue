<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Pencil, Plus, Power, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
        <Link :href="route('e-booking.admin.venues.index')" class="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline">
            <ArrowLeft class="size-4" />
            Kembali ke daftar venue
        </Link>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <AppImage v-if="venue.cover_url" :src="venue.cover_url" :alt="venue.name" class="size-14 rounded-xl object-cover" />
                <div v-else class="size-14 rounded-xl bg-slate-100"></div>
                <div>
                    <h1 class="text-foreground text-2xl font-bold">{{ venue.name }}</h1>
                    <p class="text-muted-foreground text-sm">
                        <code class="text-xs">{{ venue.code }}</code>
                        <Badge class="ml-2" :variant="venue.is_active ? 'default' : 'secondary'">
                            {{ venue.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </p>
                </div>
            </div>
            <Button as-child variant="outline" size="sm">
                <Link :href="route('e-booking.admin.venues.edit', venue.id)">Ubah venue</Link>
            </Button>
        </div>

        <Tabs default-value="area" class="mt-8">
            <TabsList>
                <TabsTrigger value="area">Area ({{ areas.total }})</TabsTrigger>
                <TabsTrigger value="tarif">Tarif ({{ tarifs.total }})</TabsTrigger>
                <TabsTrigger value="aturan">Aturan</TabsTrigger>
            </TabsList>

            <TabsContent value="area" class="mt-4">
                <form class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-2" @submit.prevent="submitArea">
                    <div class="flex items-center justify-between sm:col-span-2">
                        <h2 class="text-sm font-semibold">{{ areaEditingId ? 'Ubah area' : 'Tambah area' }}</h2>
                        <button
                            v-if="areaEditingId"
                            type="button"
                            class="text-muted-foreground text-xs font-semibold hover:underline"
                            @click="resetAreaForm"
                        >
                            Batal edit
                        </button>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="areaForm.code" type="text" maxlength="64" placeholder="lapangan_utama" />
                        <InputError :message="areaForm.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama</label>
                        <Input v-model="areaForm.name" type="text" maxlength="150" placeholder="Lapangan Utama" />
                        <InputError :message="areaForm.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan</label>
                        <Input v-model="areaForm.sort_order" type="number" min="0" />
                        <InputError :message="areaForm.errors.sort_order" />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <Checkbox v-model="areaForm.is_tentative" />
                            Tentatif
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <Checkbox v-model="areaForm.is_active" />
                            Aktif
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="areaForm.processing">
                            <LoaderCircle v-if="areaForm.processing" class="size-4 animate-spin" />
                            <Plus v-else class="size-4" />
                            {{ areaEditingId ? 'Simpan area' : 'Tambah area' }}
                        </Button>
                    </div>
                </form>

                <Table class="mt-4 min-w-[560px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama</TableHead>
                            <TableHead>Kode</TableHead>
                            <TableHead>Tentatif</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in areas.data" :key="row.id">
                            <TableCell class="font-medium">{{ row.name }}</TableCell>
                            <TableCell>
                                <code class="text-xs">{{ row.code }}</code>
                            </TableCell>
                            <TableCell>{{ row.is_tentative ? 'Ya' : '—' }}</TableCell>
                            <TableCell>
                                <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <RowActionsMenu :items="areaRowActions(row)" />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="areas.data.length === 0">
                            <TableCell colspan="5" class="text-muted-foreground py-8 text-center">Belum ada area.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <TablePagination :links="areas.links" :from="areas.from" :to="areas.to" :total="areas.total" label="area" />
            </TabsContent>

            <TabsContent value="tarif" class="mt-4">
                <form class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-2" @submit.prevent="submitTarif">
                    <div class="flex items-center justify-between sm:col-span-2">
                        <h2 class="text-sm font-semibold">{{ tarifEditingId ? 'Ubah tarif' : 'Tambah tarif' }}</h2>
                        <button
                            v-if="tarifEditingId"
                            type="button"
                            class="text-muted-foreground text-xs font-semibold hover:underline"
                            @click="resetTarifForm"
                        >
                            Batal edit
                        </button>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Area</label>
                        <SimpleSelect v-model="tarifForm.area_id" :options="areaOptions" placeholder="Tanpa area" />
                        <InputError :message="tarifForm.errors.area_id" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Uraian</label>
                        <Input v-model="tarifForm.uraian" type="text" maxlength="255" placeholder="Latihan" />
                        <InputError :message="tarifForm.errors.uraian" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Satuan</label>
                        <SimpleSelect v-model="tarifForm.satuan" :options="satuanOptions" />
                        <InputError :message="tarifForm.errors.satuan" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kategori</label>
                        <SimpleSelect v-model="tarifForm.category" :options="categoryOptions" />
                        <InputError :message="tarifForm.errors.category" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tarif instansi pemerintah</label>
                        <Input v-model="tarifForm.tarif_pemerintah" type="number" min="0" placeholder="0" />
                        <InputError :message="tarifForm.errors.tarif_pemerintah" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tarif umum / non-pemerintah</label>
                        <Input v-model="tarifForm.tarif_non_pemerintah" type="number" min="0" placeholder="0" />
                        <InputError :message="tarifForm.errors.tarif_non_pemerintah" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Min jam (opsional)</label>
                        <Input v-model="tarifForm.min_hours" type="number" min="1" max="24" placeholder="—" />
                        <InputError :message="tarifForm.errors.min_hours" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Max jam (opsional)</label>
                        <Input v-model="tarifForm.max_hours" type="number" min="1" max="24" placeholder="—" />
                        <InputError :message="tarifForm.errors.max_hours" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode (opsional)</label>
                        <Input v-model="tarifForm.code" type="text" maxlength="96" />
                        <InputError :message="tarifForm.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Time slot (opsional)</label>
                        <Input v-model="tarifForm.time_slot" type="text" maxlength="32" placeholder="pagi/siang/malam" />
                        <InputError :message="tarifForm.errors.time_slot" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Event level (opsional)</label>
                        <Input v-model="tarifForm.event_level" type="text" maxlength="64" placeholder="nasional" />
                        <InputError :message="tarifForm.errors.event_level" />
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox id="tarif-active" v-model="tarifForm.is_active" />
                        <label for="tarif-active" class="text-sm font-medium">Aktif</label>
                    </div>
                    <div class="sm:col-span-2">
                        <Button type="submit" :disabled="tarifForm.processing">
                            <LoaderCircle v-if="tarifForm.processing" class="size-4 animate-spin" />
                            <Plus v-else class="size-4" />
                            {{ tarifEditingId ? 'Simpan tarif' : 'Tambah tarif' }}
                        </Button>
                    </div>
                </form>

                <Table class="mt-4 min-w-[820px]">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Uraian</TableHead>
                            <TableHead>Area</TableHead>
                            <TableHead>Satuan</TableHead>
                            <TableHead>Durasi</TableHead>
                            <TableHead>Instansi</TableHead>
                            <TableHead>Umum</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="row in tarifs.data" :key="row.id">
                            <TableCell>
                                <p class="font-medium">{{ row.uraian }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ row.category ? (categoryLabels[row.category] ?? row.category) : '—' }}
                                </p>
                            </TableCell>
                            <TableCell>{{ row.area_name || 'Venue' }}</TableCell>
                            <TableCell>{{ satuanLabels[row.satuan] ?? row.satuan }}</TableCell>
                            <TableCell class="whitespace-nowrap">
                                <template v-if="row.min_hours || row.max_hours"> {{ row.min_hours ?? 1 }}–{{ row.max_hours ?? '∞' }} jam </template>
                                <template v-else>—</template>
                            </TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatRupiah(row.tarif_pemerintah) }}</TableCell>
                            <TableCell class="whitespace-nowrap">{{ formatRupiah(row.tarif_non_pemerintah) }}</TableCell>
                            <TableCell>
                                <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <RowActionsMenu :items="tarifRowActions(row)" />
                            </TableCell>
                        </TableRow>
                        <TableRow v-if="tarifs.data.length === 0">
                            <TableCell colspan="8" class="text-muted-foreground py-8 text-center">Belum ada tarif.</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <TablePagination :links="tarifs.links" :from="tarifs.from" :to="tarifs.to" :total="tarifs.total" label="tarif" />
            </TabsContent>

            <TabsContent value="aturan" class="mt-4">
                <form class="space-y-6" @submit.prevent="submitRules">
                    <section class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-3">
                        <h2 class="text-sm font-semibold sm:col-span-3">Jam & hari operasional</h2>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Jam buka</label>
                            <TimeField v-model="ruleForm.operating_start" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Jam tutup</label>
                            <TimeField v-model="ruleForm.operating_end" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Timezone</label>
                            <Input v-model="ruleForm.operating_timezone" type="text" maxlength="64" placeholder="Asia/Jakarta" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="mb-1.5 block text-sm font-medium">Hari operasional</label>
                            <div class="flex flex-wrap gap-3">
                                <label v-for="day in days" :key="day" class="flex items-center gap-1.5 text-sm capitalize">
                                    <Checkbox
                                        :model-value="ruleForm.operating_days.includes(day)"
                                        @update:model-value="(value) => toggleInArray(ruleForm.operating_days, day, !!value)"
                                    />
                                    {{ day }}
                                </label>
                            </div>
                        </div>
                    </section>

                    <section class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-3">
                        <h2 class="text-sm font-semibold sm:col-span-3">Batas & buffer booking</h2>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Horizon booking (hari)</label>
                            <Input v-model="ruleForm.booking_horizon_days" type="number" min="1" placeholder="Tanpa batas" />
                            <p class="text-muted-foreground mt-1 text-xs">Kosongkan = tanpa batas.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Buffer sebelum (hari)</label>
                            <Input v-model="ruleForm.buffer_before_days" type="number" min="0" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Buffer sesudah (hari)</label>
                            <Input v-model="ruleForm.buffer_after_days" type="number" min="0" />
                        </div>
                    </section>

                    <section class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-2">
                        <h2 class="text-sm font-semibold sm:col-span-2">Kebijakan pembatalan & hujan</h2>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Batas batal</label>
                            <Input v-model="ruleForm.cancel_deadline" type="text" maxlength="32" placeholder="H-1" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Batal hari H</label>
                            <Input v-model="ruleForm.cancel_on_day_h" type="text" maxlength="32" placeholder="forfeited" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Hujan sebelum main</label>
                            <Input v-model="ruleForm.force_majeure_rain_before_play" type="text" maxlength="32" placeholder="reschedule" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Ambang hujan (menit)</label>
                            <Input v-model="ruleForm.force_majeure_rain_after_play_minutes" type="number" min="0" placeholder="20" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Keputusan setelah ambang</label>
                            <Input
                                v-model="ruleForm.force_majeure_rain_after_play_decision"
                                type="text"
                                maxlength="32"
                                placeholder="no_compensation"
                            />
                        </div>
                        <div class="flex flex-col gap-2 pt-6">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="ruleForm.allow_same_day_reschedule" />
                                Izinkan reschedule hari H
                            </label>
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="ruleForm.allow_same_day_court_change" />
                                Izinkan ganti lapangan hari H
                            </label>
                        </div>
                    </section>

                    <section class="border-border grid gap-3 rounded-xl border p-4 sm:grid-cols-2">
                        <h2 class="text-sm font-semibold sm:col-span-2">Lainnya</h2>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Tata tertib</label>
                            <SimpleSelect v-model="ruleForm.terms_key" :options="terms" placeholder="— Tanpa tata tertib —" />
                            <p class="text-muted-foreground mt-1 text-xs">
                                Kelola isi di menu
                                <Link :href="route('e-booking.admin.terms.index')" class="font-semibold text-sky-700 hover:underline"
                                    >Tata tertib</Link
                                >.
                            </p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Prefer hari booking</label>
                            <Input v-model="ruleForm.prefer_booking_on_weekday" type="text" maxlength="16" placeholder="monday" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Tentative priority</label>
                            <Input v-model="ruleForm.tentative_priority" type="text" maxlength="64" placeholder="kegiatan_pemerintah_daerah" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Datang lebih awal (min)</label>
                            <Input v-model="ruleForm.early_arrival_minutes_min" type="number" min="0" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Datang lebih awal (max)</label>
                            <Input v-model="ruleForm.early_arrival_minutes_max" type="number" min="0" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Keluar lapangan (menit)</label>
                            <Input v-model="ruleForm.leave_court_after_minutes" type="number" min="0" />
                        </div>
                        <div class="flex flex-col gap-2 pt-6">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="ruleForm.advance_payment_required" />
                                Wajib bayar di muka
                            </label>
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="ruleForm.adjacent_empty_court_counts_as_rental" />
                                Lapangan sebelah kosong dihitung sewa
                            </label>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium">Area tentatif</label>
                            <div class="flex flex-wrap gap-3">
                                <label v-for="area in allAreas" :key="area.id" class="flex items-center gap-1.5 text-sm">
                                    <Checkbox
                                        :model-value="ruleForm.tentative_areas.includes(area.code)"
                                        @update:model-value="(value) => toggleInArray(ruleForm.tentative_areas, area.code, !!value)"
                                    />
                                    {{ area.name }}
                                </label>
                                <span v-if="allAreas.length === 0" class="text-muted-foreground text-sm">Belum ada area.</span>
                            </div>
                        </div>
                    </section>

                    <div class="border-border bg-background/95 sticky bottom-0 z-10 flex items-center gap-2 border-t py-3 backdrop-blur">
                        <Button type="submit" :disabled="ruleForm.processing">
                            <LoaderCircle v-if="ruleForm.processing" class="size-4 animate-spin" />
                            Simpan aturan
                        </Button>
                    </div>
                </form>

                <div class="mt-8">
                    <h2 class="text-sm font-semibold">Aturan lanjutan (key-value)</h2>
                    <Table class="mt-3 min-w-[640px]">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Key</TableHead>
                                <TableHead>Value</TableHead>
                                <TableHead>Aktif</TableHead>
                                <TableHead></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in ruleList.data" :key="row.id">
                                <TableCell>
                                    <code class="text-xs">{{ row.key }}</code>
                                </TableCell>
                                <TableCell class="text-muted-foreground max-w-[320px] break-all whitespace-normal">{{
                                    ruleValueLabel(row.value)
                                }}</TableCell>
                                <TableCell>{{ row.is_active ? 'Ya' : 'Tidak' }}</TableCell>
                                <TableCell class="text-right">
                                    <RowActionsMenu :items="ruleRowActions(row)" />
                                </TableCell>
                            </TableRow>
                            <TableRow v-if="ruleList.data.length === 0">
                                <TableCell colspan="4" class="text-muted-foreground py-6 text-center">Belum ada aturan khusus venue.</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <TablePagination :links="ruleList.links" :from="ruleList.from" :to="ruleList.to" :total="ruleList.total" label="aturan" />

                    <form class="border-border mt-4 grid gap-3 rounded-xl border p-4 sm:grid-cols-3" @submit.prevent="addRule">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Key</label>
                            <Input v-model="newRuleForm.key" type="text" maxlength="96" placeholder="custom_key" />
                            <InputError :message="newRuleForm.errors.key" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Value</label>
                            <Input v-model="newRuleForm.value" type="text" placeholder="nilai" />
                            <InputError :message="newRuleForm.errors.value" />
                        </div>
                        <div class="flex items-end gap-2">
                            <label class="flex items-center gap-2 pb-2 text-sm font-medium">
                                <Checkbox v-model="newRuleForm.is_active" />
                                Aktif
                            </label>
                            <Button type="submit" :disabled="newRuleForm.processing">
                                <LoaderCircle v-if="newRuleForm.processing" class="size-4 animate-spin" />
                                <Plus v-else class="size-4" />
                                Tambah
                            </Button>
                        </div>
                    </form>
                </div>
            </TabsContent>
        </Tabs>
    </AdminLayout>
</template>
