<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import FacilityIcon from '@/components/e-booking/FacilityIcon.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import { Checkbox } from '@/components/ui/checkbox';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowLeft,
    faArrowRight,
    faBox,
    faBuilding,
    faCheck,
    faChevronDown,
    faChevronRight,
    faCircleNotch,
    faClipboardCheck,
    faClock,
    faCloudArrowUp,
    faLocationDot,
    faPlus,
    faScroll,
    faTableCellsLarge,
    faTag,
    faTrash,
    faTriangleExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

library.add(
    faArrowLeft,
    faArrowRight,
    faBox,
    faBuilding,
    faCheck,
    faChevronDown,
    faChevronRight,
    faCircleNotch,
    faClipboardCheck,
    faClock,
    faCloudArrowUp,
    faLocationDot,
    faPlus,
    faScroll,
    faTableCellsLarge,
    faTag,
    faTrash,
    faTriangleExclamation,
);

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
    operating_start: string;
    operating_end: string;
    operating_days: string[];
    facility_ids: number[];
    addon_ids: number[];
    terms_key: string | null;
};

type FacilityOption = {
    id: number;
    code: string;
    name: string;
    icon: string | null;
};

type AddonOption = {
    id: number;
    name: string;
    harga: number | null;
};

type TermOption = {
    value: string;
    label: string;
};

type AreaInput = {
    code: string;
    name: string;
    is_tentative: boolean;
    is_active: boolean;
    sort_order: number | string;
};

type TarifInput = {
    area_code: string;
    code: string;
    uraian: string;
    satuan: string;
    tarif_pemerintah: number | string;
    tarif_non_pemerintah: number | string;
    min_hours: number | string;
    max_hours: number | string;
    category: string;
    time_slot: string;
    event_level: string;
    day_type: string;
    vehicle_class: string;
    audience_type: string;
    effective_from: string;
};

const props = defineProps<{
    venue: Venue | null;
    defaults: { operating_start: string; operating_end: string };
    facilities: FacilityOption[];
    addons: AddonOption[];
    terms: TermOption[];
    options: { satuan: string[]; categories: string[] };
}>();

const isEdit = computed(() => props.venue !== null);

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
const satuanOptions = computed(() => props.options.satuan.map((v) => ({ value: v, label: satuanLabels[v] ?? v })));

const categoryLabels: Record<string, string> = {
    olahraga: 'Olahraga',
    non_olahraga: 'Non olahraga',
    sewa_lahan: 'Sewa lahan',
    ruang: 'Ruang',
};
const categoryOptions = computed(() => props.options.categories.map((v) => ({ value: v, label: categoryLabels[v] ?? v })));

const days = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];
const toggleInArray = (arr: string[], value: string, checked: boolean) => {
    const index = arr.indexOf(value);
    if (checked && index === -1) arr.push(value);
    if (!checked && index > -1) arr.splice(index, 1);
};

const dayTypeOptions = [
    { value: 'weekday', label: 'Hari kerja (weekday)' },
    { value: 'weekend', label: 'Akhir pekan (weekend)' },
];

const AREA_ALL = '__all__';

const newArea = (index: number): AreaInput => ({ code: '', name: '', is_tentative: false, is_active: true, sort_order: index + 1 });

const newTarif = (): TarifInput => ({
    area_code: AREA_ALL,
    code: '',
    uraian: '',
    satuan: props.options.satuan[0] ?? 'per_hour',
    tarif_pemerintah: '',
    tarif_non_pemerintah: '',
    min_hours: '',
    max_hours: '',
    category: 'olahraga',
    time_slot: '',
    event_level: '',
    day_type: '',
    vehicle_class: '',
    audience_type: '',
    effective_from: '',
});

const form = useForm<{
    code: string;
    name: string;
    description: string;
    sort_order: number | string;
    is_active: boolean;
    operating_start: string;
    operating_end: string;
    operating_days: string[];
    facility_ids: number[];
    addon_ids: number[];
    terms_key: string;
    cover: File | null;
    areas: AreaInput[];
    tarifs: TarifInput[];
}>({
    code: props.venue?.code ?? '',
    name: props.venue?.name ?? '',
    description: props.venue?.description ?? '',
    sort_order: props.venue?.sort_order ?? 0,
    is_active: props.venue?.is_active ?? true,
    operating_start: props.venue?.operating_start ?? props.defaults.operating_start,
    operating_end: props.venue?.operating_end ?? props.defaults.operating_end,
    operating_days: props.venue?.operating_days ?? [...days],
    facility_ids: props.venue?.facility_ids ?? [],
    addon_ids: props.venue?.addon_ids ?? props.addons.map((a) => a.id),
    terms_key: props.venue?.terms_key ?? props.terms[0]?.value ?? '',
    cover: null,
    areas: isEdit.value ? [] : [newArea(0)],
    tarifs: isEdit.value ? [] : [newTarif()],
});

const toggleFacility = (id: number, checked: boolean) => {
    const index = form.facility_ids.indexOf(id);
    if (checked && index === -1) form.facility_ids.push(id);
    if (!checked && index > -1) form.facility_ids.splice(index, 1);
};

const toggleAddon = (id: number, checked: boolean) => {
    const index = form.addon_ids.indexOf(id);
    if (checked && index === -1) form.addon_ids.push(id);
    if (!checked && index > -1) form.addon_ids.splice(index, 1);
};

const coverPreview = ref<string | null>(props.venue?.cover_url ?? null);
const onCoverChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.cover = files && files.length > 0 ? files[0] : null;
    if (form.cover) coverPreview.value = URL.createObjectURL(form.cover);
};

const areaOptions = computed(() => [
    { value: AREA_ALL, label: 'Semua area' },
    ...form.areas.filter((a) => a.code).map((a) => ({ value: a.code, label: a.name || a.code })),
]);

const areaLabel = (code: string) => {
    if (code === AREA_ALL) return 'Semua area';
    const area = form.areas.find((a) => a.code === code);

    return area ? area.name || area.code : 'Semua area';
};

const addArea = () => form.areas.push(newArea(form.areas.length));
const removeArea = (index: number) => {
    form.areas.splice(index, 1);
    if (form.areas.length === 0) form.areas.push(newArea(0));
};

const expandedTarif = ref<number | null>(null);
const addTarif = () => form.tarifs.push(newTarif());
const addTarifForArea = (code: string) => {
    const tarif = newTarif();
    tarif.area_code = code;
    form.tarifs.push(tarif);
};
const removeTarif = (index: number) => {
    form.tarifs.splice(index, 1);
    expandedTarif.value = null;
    if (form.tarifs.length === 0) form.tarifs.push(newTarif());
};
const toggleTarifAdvanced = (index: number) => {
    expandedTarif.value = expandedTarif.value === index ? null : index;
};

const areaError = (index: number, field: string) => form.errors[`areas.${index}.${field}` as keyof typeof form.errors];
const tarifError = (index: number, field: string) => form.errors[`tarifs.${index}.${field}` as keyof typeof form.errors];

/* ---------- Wizard ---------- */
const steps = computed(() =>
    isEdit.value
        ? [
              { key: 'info', label: 'Info venue', icon: 'building' },
              { key: 'hours', label: 'Jam operasional', icon: 'clock' },
              { key: 'facilities', label: 'Fasilitas', icon: 'table-cells-large' },
              { key: 'addons', label: 'Layanan', icon: 'box' },
              { key: 'terms', label: 'Tata tertib', icon: 'scroll' },
          ]
        : [
              { key: 'info', label: 'Info venue', icon: 'building' },
              { key: 'hours', label: 'Jam operasional', icon: 'clock' },
              { key: 'areas', label: 'Area', icon: 'location-dot' },
              { key: 'facilities', label: 'Fasilitas', icon: 'table-cells-large' },
              { key: 'addons', label: 'Layanan', icon: 'box' },
              { key: 'terms', label: 'Tata tertib', icon: 'scroll' },
              { key: 'tarifs', label: 'Harga sewa', icon: 'tag' },
              { key: 'review', label: 'Ringkasan', icon: 'clipboard-check' },
          ],
);

const step = ref(0);
const lastStep = computed(() => steps.value.length - 1);
const currentKey = computed(() => steps.value[step.value]?.key ?? 'info');
const progress = computed(() => Math.round(((step.value + 1) / steps.value.length) * 100));

const stepError = ref('');

const goTo = (index: number) => {
    step.value = Math.max(0, Math.min(lastStep.value, index));
    stepError.value = '';
};
const goToKey = (key: string) => {
    const index = steps.value.findIndex((s) => s.key === key);
    if (index >= 0) step.value = index;
};

const validateFields = (): Record<string, string> => {
    const errors: Record<string, string> = {};
    if (!form.name.trim()) errors.name = 'Nama venue wajib diisi.';
    if (!form.code.trim()) {
        errors.code = 'Kode venue wajib diisi.';
    } else if (!/^[a-z0-9_]+$/.test(form.code)) {
        errors.code = 'Kode hanya boleh huruf kecil, angka, dan garis bawah.';
    }
    if (!form.operating_start) errors.operating_start = 'Jam buka wajib diisi.';
    if (!form.operating_end) errors.operating_end = 'Jam tutup wajib diisi.';

    if (!isEdit.value) {
        if (form.areas.length === 0) errors.areas = 'Tambahkan minimal satu area.';
        form.areas.forEach((area, index) => {
            if (!area.name.trim()) errors[`areas.${index}.name`] = 'Nama area wajib diisi.';
            if (!area.code.trim()) {
                errors[`areas.${index}.code`] = 'Kode area wajib diisi.';
            } else if (!/^[a-z0-9_]+$/.test(area.code)) {
                errors[`areas.${index}.code`] = 'Kode area hanya huruf kecil, angka, dan garis bawah.';
            }
        });
        const codes = form.areas.map((a) => a.code).filter(Boolean);
        codes.forEach((code, index) => {
            if (codes.indexOf(code) !== index) errors[`areas.${index}.code`] = 'Kode area tidak boleh sama.';
        });

        if (form.tarifs.length === 0) errors.tarifs = 'Tambahkan minimal satu tarif.';
        form.tarifs.forEach((tarif, index) => {
            if (!tarif.uraian.trim()) errors[`tarifs.${index}.uraian`] = 'Uraian tarif wajib diisi.';
            if (tarif.tarif_pemerintah === '' && tarif.tarif_non_pemerintah === '') {
                errors[`tarifs.${index}.tarif_pemerintah`] = 'Isi minimal salah satu harga.';
            }
        });
    }

    return errors;
};

const errorsForStep = (all: Record<string, string>, key: string): Record<string, string> => {
    const match = (k: string) => {
        if (key === 'info') return ['name', 'code'].includes(k);
        if (key === 'hours') return ['operating_start', 'operating_end'].includes(k);
        if (key === 'areas') return k === 'areas' || k.startsWith('areas.');
        if (key === 'tarifs') return k === 'tarifs' || k.startsWith('tarifs.');
        if (key === 'facilities') return k.startsWith('facility_ids');
        if (key === 'addons') return k.startsWith('addon_ids');
        if (key === 'terms') return k === 'terms_key';

        return false;
    };

    return Object.fromEntries(Object.entries(all).filter(([k]) => match(k)));
};

const nextStep = () => {
    const all = validateFields();
    const current = steps.value[step.value].key;
    const relevant = errorsForStep(all, current);

    if (Object.keys(relevant).length) {
        form.setError(relevant);
        stepError.value = 'Lengkapi dulu isian yang wajib sebelum lanjut.';

        return;
    }

    stepError.value = '';
    goTo(step.value + 1);
};

const goToFirstError = () => {
    const keys = Object.keys(form.errors);
    if (!keys.length) return;
    const key = keys[0];
    if (key.startsWith('tarifs')) return goToKey('tarifs');
    if (key.startsWith('areas')) return goToKey('areas');
    if (key.startsWith('facility_ids')) return goToKey('facilities');
    if (key.startsWith('addon_ids')) return goToKey('addons');
    if (key === 'terms_key') return goToKey('terms');
    if (['operating_start', 'operating_end', 'operating_days'].includes(key)) return goToKey('hours');
    goToKey('info');
};

const submit = () => {
    const all = validateFields();
    if (Object.keys(all).length) {
        form.setError(all);
        stepError.value = 'Ada isian yang belum lengkap. Periksa langkah yang bertanda.';
        goToFirstError();

        return;
    }

    stepError.value = '';
    const transform = (data: ReturnType<typeof form.data>) => ({
        ...data,
        sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
        facility_ids: data.facility_ids.map(Number),
        addon_ids: data.addon_ids.map(Number),
        terms_key: data.terms_key || null,
        areas: data.areas.map((area, index) => ({
            ...area,
            sort_order: area.sort_order === '' ? index + 1 : Number(area.sort_order),
            is_tentative: !!area.is_tentative,
            is_active: !!area.is_active,
        })),
        tarifs: data.tarifs.map((tarif) => ({
            ...tarif,
            area_code: tarif.area_code && tarif.area_code !== AREA_ALL ? tarif.area_code : null,
            code: tarif.code || null,
            tarif_pemerintah: tarif.tarif_pemerintah === '' ? null : Number(tarif.tarif_pemerintah),
            tarif_non_pemerintah: tarif.tarif_non_pemerintah === '' ? null : Number(tarif.tarif_non_pemerintah),
            min_hours: tarif.min_hours === '' ? null : Number(tarif.min_hours),
            max_hours: tarif.max_hours === '' ? null : Number(tarif.max_hours),
            time_slot: tarif.time_slot || null,
            event_level: tarif.event_level || null,
            day_type: tarif.day_type || null,
            vehicle_class: tarif.vehicle_class || null,
            audience_type: tarif.audience_type || null,
            effective_from: tarif.effective_from || null,
        })),
    });

    const options = { forceFormData: true, onError: goToFirstError };

    if (props.venue) {
        form.transform((data) => ({ ...transform(data), _method: 'put' })).post(route('e-booking.admin.venues.update', props.venue.id), options);
    } else {
        form.transform(transform).post(route('e-booking.admin.venues.store'), options);
    }
};
</script>

<template>
    <SeoHead :title="isEdit ? `Ubah Venue — E-Booking` : 'Tambah Venue — E-Booking'" />

    <AdminLayout active="venues">
        <Link
            :href="route('e-booking.admin.venues.index')"
            class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 rounded-sm text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
        >
            <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
            Kembali ke daftar venue
        </Link>

        <header class="mt-4 max-w-2xl">
            <h1 class="text-foreground text-2xl font-bold tracking-tight sm:text-3xl">{{ isEdit ? 'Ubah venue' : 'Tambah venue' }}</h1>
            <p class="text-muted-foreground mt-2 text-sm">
                {{
                    isEdit ? 'Perbarui informasi dan jam operasional venue.' : 'Ikuti langkah berikut untuk membuat venue beserta area dan harganya.'
                }}
            </p>
        </header>

        <nav class="mt-6" aria-label="Langkah pengisian">
            <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-2">
                <li v-for="(s, i) in steps" :key="s.key" class="flex items-center gap-1.5">
                    <button
                        type="button"
                        class="sb-chip"
                        :class="i < step ? 'text-foreground' : ''"
                        :data-active="i === step ? 'true' : undefined"
                        :aria-current="i === step ? 'step' : undefined"
                        @click="goTo(i)"
                    >
                        <FontAwesomeIcon v-if="i < step" :icon="['fas', 'check']" class="size-3 text-(--wp-accent)" aria-hidden="true" />
                        <FontAwesomeIcon v-else :icon="['fas', s.icon]" class="size-3" aria-hidden="true" />
                        {{ s.label }}
                    </button>
                    <FontAwesomeIcon v-if="i < lastStep" :icon="['fas', 'chevron-right']" class="text-muted-foreground size-2.5" aria-hidden="true" />
                </li>
            </ol>
            <div
                class="bg-muted mt-4 h-1 overflow-hidden rounded-full"
                role="progressbar"
                :aria-valuenow="progress"
                aria-valuemin="0"
                aria-valuemax="100"
                aria-label="Kemajuan pengisian"
            >
                <div class="h-full rounded-full bg-(--wp-accent) transition-all duration-300" :style="{ width: `${progress}%` }" />
            </div>
        </nav>

        <form class="mt-6 max-w-5xl" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="sb-callout sb-tone-danger mb-5" role="alert">
                <FontAwesomeIcon :icon="['fas', 'triangle-exclamation']" class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <div>
                    <p class="font-semibold">{{ stepError || `Ada ${Object.keys(form.errors).length} isian yang perlu diperbaiki.` }}</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
                    </ul>
                </div>
            </div>

            <!-- Step: Info venue -->
            <section
                v-if="currentKey === 'info'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-info-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'building']" class="size-4" /></span>
                    <h2 id="step-info-title" class="mt-3 text-base font-semibold tracking-tight">Info venue</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Identitas dasar tempat yang akan disewakan.</p>
                </div>

                <div class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="venue-name" class="sb-label">Nama venue</label>
                            <input
                                id="venue-name"
                                v-model="form.name"
                                type="text"
                                maxlength="150"
                                placeholder="Stadion Pakansari"
                                class="sb-input"
                                :aria-invalid="invalid(form.errors.name)"
                                :aria-describedby="form.errors.name ? 'venue-name-error' : undefined"
                            />
                            <p v-if="form.errors.name" id="venue-name-error" class="sb-error">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label for="venue-code" class="sb-label">Kode</label>
                            <input
                                id="venue-code"
                                v-model="form.code"
                                type="text"
                                maxlength="64"
                                placeholder="pakansari"
                                class="sb-input font-mono"
                                :aria-invalid="invalid(form.errors.code)"
                                :aria-describedby="form.errors.code ? 'venue-code-hint venue-code-error' : 'venue-code-hint'"
                            />
                            <p id="venue-code-hint" class="sb-hint">Kode unik, huruf kecil & garis bawah. Contoh: pakansari</p>
                            <p v-if="form.errors.code" id="venue-code-error" class="sb-error">{{ form.errors.code }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="venue-description" class="sb-label">Deskripsi</label>
                            <textarea
                                id="venue-description"
                                v-model="form.description"
                                rows="3"
                                class="sb-input"
                                placeholder="Deskripsi singkat (opsional)"
                                :aria-invalid="invalid(form.errors.description)"
                                :aria-describedby="form.errors.description ? 'venue-description-error' : undefined"
                            ></textarea>
                            <p v-if="form.errors.description" id="venue-description-error" class="sb-error">{{ form.errors.description }}</p>
                        </div>
                        <div>
                            <label for="venue-sort" class="sb-label">Urutan tampil</label>
                            <input
                                id="venue-sort"
                                v-model="form.sort_order"
                                type="number"
                                min="0"
                                class="sb-input tabular-nums"
                                :aria-invalid="invalid(form.errors.sort_order)"
                                :aria-describedby="form.errors.sort_order ? 'venue-sort-error' : undefined"
                            />
                            <p v-if="form.errors.sort_order" id="venue-sort-error" class="sb-error">{{ form.errors.sort_order }}</p>
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 pb-2.5 text-sm font-medium">
                                <Checkbox v-model="form.is_active" />
                                Tampilkan di katalog
                            </label>
                        </div>
                    </div>

                    <div>
                        <span id="venue-cover-label" class="sb-label">Foto cover (opsional)</span>
                        <label
                            for="venue-cover"
                            class="group flex cursor-pointer flex-col items-center gap-4 rounded-2xl border-2 border-dashed border-(--wp-hairline) p-5 text-center transition-colors focus-within:border-(--wp-accent) hover:border-(--wp-accent) hover:bg-(--wp-accent-soft) sm:flex-row sm:text-left"
                        >
                            <AppImage
                                v-if="coverPreview"
                                :src="coverPreview"
                                alt="Pratinjau cover"
                                class="h-24 w-36 shrink-0 rounded-xl object-cover"
                            />
                            <span v-else class="wp-icon size-12 shrink-0" aria-hidden="true">
                                <FontAwesomeIcon :icon="['fas', 'cloud-arrow-up']" class="size-5" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ coverPreview ? 'Ganti foto cover' : 'Pilih foto cover' }}</span>
                                <span class="text-muted-foreground mt-0.5 block text-xs">JPG, PNG, WEBP, atau SVG.</span>
                                <span v-if="form.cover" class="text-muted-foreground mt-1 block truncate text-xs">{{ form.cover.name }}</span>
                            </span>
                            <input
                                id="venue-cover"
                                type="file"
                                accept=".jpg,.jpeg,.png,.webp,.svg"
                                class="sr-only"
                                aria-labelledby="venue-cover-label"
                                :aria-invalid="invalid(form.errors.cover)"
                                :aria-describedby="form.errors.cover ? 'venue-cover-error' : undefined"
                                @change="onCoverChange"
                            />
                        </label>
                        <p v-if="form.errors.cover" id="venue-cover-error" class="sb-error">{{ form.errors.cover }}</p>
                    </div>
                </div>
            </section>

            <!-- Step: Jam operasional -->
            <section
                v-else-if="currentKey === 'hours'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-hours-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'clock']" class="size-4" /></span>
                    <h2 id="step-hours-title" class="mt-3 text-base font-semibold tracking-tight">Jam operasional</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Kapan venue ini buka dan hari apa saja.</p>
                </div>

                <div class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <span id="venue-open-label" class="sb-label">Jam buka</span>
                            <div role="group" aria-labelledby="venue-open-label">
                                <TimeField v-model="form.operating_start" />
                            </div>
                            <p v-if="form.errors.operating_start" class="sb-error">{{ form.errors.operating_start }}</p>
                        </div>
                        <div>
                            <span id="venue-close-label" class="sb-label">Jam tutup</span>
                            <div role="group" aria-labelledby="venue-close-label">
                                <TimeField v-model="form.operating_end" />
                            </div>
                            <p v-if="form.errors.operating_end" class="sb-error">{{ form.errors.operating_end }}</p>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="sb-label">Hari operasional</legend>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="day in days"
                                :key="day"
                                type="button"
                                class="sb-chip capitalize"
                                :aria-pressed="form.operating_days.includes(day) ? 'true' : 'false'"
                                @click="toggleInArray(form.operating_days, day, !form.operating_days.includes(day))"
                            >
                                {{ day }}
                            </button>
                        </div>
                        <p v-if="form.errors.operating_days" class="sb-error">{{ form.errors.operating_days }}</p>
                    </fieldset>
                </div>
            </section>

            <!-- Step: Area -->
            <section
                v-else-if="currentKey === 'areas'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-areas-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'location-dot']" class="size-4" /></span>
                    <h2 id="step-areas-title" class="mt-3 text-base font-semibold tracking-tight">Area yang disewakan</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Tambahkan setiap area/lapangan. Bisa lebih dari satu.</p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(area, index) in form.areas"
                        :key="index"
                        class="sb-card-muted p-4 sm:p-5"
                        role="group"
                        :aria-labelledby="`area-${index}-title`"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h3 :id="`area-${index}-title`" class="text-sm font-semibold">Area {{ index + 1 }}</h3>
                            <button
                                type="button"
                                class="text-muted-foreground hover:bg-card inline-flex size-8 items-center justify-center rounded-lg transition-colors hover:text-(--sb-danger) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                :aria-label="`Hapus area ${index + 1}`"
                                @click="removeArea(index)"
                            >
                                <FontAwesomeIcon :icon="['fas', 'trash']" class="size-3.5" aria-hidden="true" />
                            </button>
                        </div>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label :for="`area-${index}-name`" class="sb-label">Nama area</label>
                                <input
                                    :id="`area-${index}-name`"
                                    v-model="area.name"
                                    type="text"
                                    maxlength="150"
                                    placeholder="Lapangan Utama"
                                    class="sb-input"
                                    :aria-invalid="invalid(areaError(index, 'name'))"
                                    :aria-describedby="areaError(index, 'name') ? `area-${index}-name-error` : undefined"
                                />
                                <p v-if="areaError(index, 'name')" :id="`area-${index}-name-error`" class="sb-error">
                                    {{ areaError(index, 'name') }}
                                </p>
                            </div>
                            <div>
                                <label :for="`area-${index}-code`" class="sb-label">Kode</label>
                                <input
                                    :id="`area-${index}-code`"
                                    v-model="area.code"
                                    type="text"
                                    maxlength="64"
                                    placeholder="lapangan_utama"
                                    class="sb-input font-mono"
                                    :aria-invalid="invalid(areaError(index, 'code'))"
                                    :aria-describedby="areaError(index, 'code') ? `area-${index}-code-error` : undefined"
                                />
                                <p v-if="areaError(index, 'code')" :id="`area-${index}-code-error`" class="sb-error">
                                    {{ areaError(index, 'code') }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-5">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <Checkbox v-model="area.is_tentative" />
                                    Tentatif
                                </label>
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <Checkbox v-model="area.is_active" />
                                    Aktif
                                </label>
                            </div>
                            <div class="flex items-end sm:justify-end">
                                <button
                                    v-if="area.code"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-sm text-xs font-semibold text-(--wp-accent) hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                    @click="addTarifForArea(area.code)"
                                >
                                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3" aria-hidden="true" />
                                    Tambah harga untuk area ini
                                </button>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-(--wp-hairline) px-4 py-3.5 text-sm font-semibold transition-colors hover:border-(--wp-accent) hover:bg-(--wp-accent-soft) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                        @click="addArea"
                    >
                        <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                        Tambah area
                    </button>
                </div>
            </section>

            <!-- Step: Fasilitas -->
            <section
                v-else-if="currentKey === 'facilities'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-facilities-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'table-cells-large']" class="size-4" /></span>
                    <h2 id="step-facilities-title" class="mt-3 text-base font-semibold tracking-tight">Fasilitas</h2>
                    <p class="text-muted-foreground mt-1 text-sm">Pilih fasilitas yang tersedia di venue ini (opsional).</p>
                </div>

                <div>
                    <div v-if="facilities.length" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="facility in facilities"
                            :key="facility.id"
                            type="button"
                            class="flex items-center gap-2.5 rounded-xl p-3 text-left text-sm font-medium ring-1 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                            :class="
                                form.facility_ids.includes(facility.id)
                                    ? 'text-foreground bg-(--wp-accent-soft) ring-(--wp-accent)'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground ring-(--wp-hairline)'
                            "
                            :aria-pressed="form.facility_ids.includes(facility.id) ? 'true' : 'false'"
                            @click="toggleFacility(facility.id, !form.facility_ids.includes(facility.id))"
                        >
                            <FacilityIcon v-if="facility.icon" :icon="facility.icon" class="size-4 shrink-0" />
                            <span class="flex-1">{{ facility.name }}</span>
                            <FontAwesomeIcon
                                v-if="form.facility_ids.includes(facility.id)"
                                :icon="['fas', 'check']"
                                class="size-3.5 text-(--wp-accent)"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                    <div v-else class="flex flex-col items-center rounded-2xl border-2 border-dashed border-(--wp-hairline) px-6 py-10 text-center">
                        <span class="wp-icon size-12" aria-hidden="true"
                            ><FontAwesomeIcon :icon="['fas', 'table-cells-large']" class="size-5"
                        /></span>
                        <p class="mt-3 text-sm font-semibold">Belum ada fasilitas</p>
                        <p class="text-muted-foreground mt-1 text-sm">Tambahkan dulu di menu Fasilitas.</p>
                    </div>
                    <p v-if="form.errors.facility_ids" class="sb-error">{{ form.errors.facility_ids }}</p>
                </div>
            </section>

            <!-- Step: Layanan -->
            <section
                v-else-if="currentKey === 'addons'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-addons-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'box']" class="size-4" /></span>
                    <h2 id="step-addons-title" class="mt-3 text-base font-semibold tracking-tight">Tambahan layanan</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Pilih layanan tambahan yang tersedia di venue ini. Semua tercentang secara default — hapus centang yang tidak perlu.
                    </p>
                </div>

                <div>
                    <div v-if="addons.length" class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="addon in addons"
                            :key="addon.id"
                            type="button"
                            class="flex items-start justify-between gap-2 rounded-xl p-3 text-left text-sm ring-1 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                            :class="
                                form.addon_ids.includes(addon.id) ? 'bg-(--wp-accent-soft) ring-(--wp-accent)' : 'hover:bg-muted ring-(--wp-hairline)'
                            "
                            :aria-pressed="form.addon_ids.includes(addon.id) ? 'true' : 'false'"
                            @click="toggleAddon(addon.id, !form.addon_ids.includes(addon.id))"
                        >
                            <span>
                                <span
                                    class="block font-medium"
                                    :class="form.addon_ids.includes(addon.id) ? 'text-foreground' : 'text-muted-foreground'"
                                >
                                    {{ addon.name }}
                                </span>
                                <span v-if="addon.harga != null" class="text-muted-foreground text-xs tabular-nums">
                                    Rp {{ addon.harga.toLocaleString('id-ID') }}
                                </span>
                            </span>
                            <FontAwesomeIcon
                                v-if="form.addon_ids.includes(addon.id)"
                                :icon="['fas', 'check']"
                                class="mt-0.5 size-3.5 shrink-0 text-(--wp-accent)"
                                aria-hidden="true"
                            />
                        </button>
                    </div>
                    <div v-else class="flex flex-col items-center rounded-2xl border-2 border-dashed border-(--wp-hairline) px-6 py-10 text-center">
                        <span class="wp-icon size-12" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'box']" class="size-5" /></span>
                        <p class="mt-3 text-sm font-semibold">Belum ada layanan</p>
                        <p class="text-muted-foreground mt-1 text-sm">Tambahkan dulu di menu Layanan.</p>
                    </div>
                    <p v-if="form.errors.addon_ids" class="sb-error">{{ form.errors.addon_ids }}</p>
                </div>
            </section>

            <!-- Step: Tata tertib -->
            <section
                v-else-if="currentKey === 'terms'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-terms-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'scroll']" class="size-4" /></span>
                    <h2 id="step-terms-title" class="mt-3 text-base font-semibold tracking-tight">Tata tertib</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Pilih tata tertib yang berlaku di venue ini. Isinya akan tampil ke penyewa saat mengajukan booking.
                    </p>
                </div>

                <div>
                    <label class="block">
                        <span class="sb-label">Tata tertib</span>
                        <SimpleSelect v-model="form.terms_key" :options="terms" placeholder="— Tanpa tata tertib —" :trigger-class="selectTrigger" />
                    </label>
                    <p v-if="form.errors.terms_key" class="sb-error">{{ form.errors.terms_key }}</p>
                    <p v-if="terms.length" class="sb-hint">
                        Kelola isi tata tertib di menu
                        <Link
                            :href="route('e-booking.admin.terms.index')"
                            class="font-semibold text-(--wp-accent) hover:text-(--wp-accent-strong) hover:underline"
                            >Tata tertib</Link
                        >.
                    </p>
                    <p v-else class="sb-hint">Belum ada tata tertib. Buat dulu di menu Tata tertib.</p>
                </div>
            </section>

            <!-- Step: Harga -->
            <section
                v-else-if="currentKey === 'tarifs'"
                class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10"
                aria-labelledby="step-tarifs-title"
            >
                <div>
                    <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'tag']" class="size-4" /></span>
                    <h2 id="step-tarifs-title" class="mt-3 text-base font-semibold tracking-tight">Harga sewa</h2>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Satu kartu = satu jenis tarif. Harga bisa beda per area, siang/malam, atau weekday/weekend.
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(tarif, index) in form.tarifs"
                        :key="index"
                        class="sb-card-muted p-4 sm:p-5"
                        role="group"
                        :aria-labelledby="`tarif-${index}-title`"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h3 :id="`tarif-${index}-title`" class="flex min-w-0 items-center gap-2.5">
                                <span class="wp-icon size-7 shrink-0 rounded-lg text-xs font-bold tabular-nums">{{ index + 1 }}</span>
                                <span class="truncate text-sm font-semibold">{{ tarif.uraian || 'Tarif baru' }}</span>
                            </h3>
                            <button
                                type="button"
                                class="text-muted-foreground hover:bg-card inline-flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors hover:text-(--sb-danger) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                :aria-label="`Hapus tarif ${index + 1}`"
                                @click="removeTarif(index)"
                            >
                                <FontAwesomeIcon :icon="['fas', 'trash']" class="size-3.5" aria-hidden="true" />
                            </button>
                        </div>

                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label :for="`tarif-${index}-uraian`" class="sb-label">Uraian / nama tarif</label>
                                <input
                                    :id="`tarif-${index}-uraian`"
                                    v-model="tarif.uraian"
                                    type="text"
                                    maxlength="255"
                                    placeholder="Contoh: Sewa Lapangan – Malam"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifError(index, 'uraian'))"
                                    :aria-describedby="tarifError(index, 'uraian') ? `tarif-${index}-uraian-error` : undefined"
                                />
                                <p v-if="tarifError(index, 'uraian')" :id="`tarif-${index}-uraian-error`" class="sb-error">
                                    {{ tarifError(index, 'uraian') }}
                                </p>
                            </div>
                            <label class="block">
                                <span class="sb-label">Berlaku untuk area</span>
                                <SimpleSelect
                                    v-model="tarif.area_code"
                                    :options="areaOptions"
                                    placeholder="Semua area"
                                    :trigger-class="selectTrigger"
                                />
                                <span v-if="tarifError(index, 'area_code')" class="sb-error block">{{ tarifError(index, 'area_code') }}</span>
                            </label>
                            <label class="block">
                                <span class="sb-label">Satuan</span>
                                <SimpleSelect v-model="tarif.satuan" :options="satuanOptions" :trigger-class="selectTrigger" />
                                <span v-if="tarifError(index, 'satuan')" class="sb-error block">{{ tarifError(index, 'satuan') }}</span>
                            </label>
                            <label class="block">
                                <span class="sb-label">Kategori</span>
                                <SimpleSelect v-model="tarif.category" :options="categoryOptions" :trigger-class="selectTrigger" />
                                <span v-if="tarifError(index, 'category')" class="sb-error block">{{ tarifError(index, 'category') }}</span>
                            </label>
                        </div>

                        <div class="bg-card mt-4 rounded-xl p-4 ring-1 ring-(--wp-hairline)">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-semibold">Harga</p>
                                <p class="text-muted-foreground text-xs">Isi minimal salah satu</p>
                            </div>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label :for="`tarif-${index}-pemerintah`" class="sb-label">Instansi pemerintah</label>
                                    <div class="relative">
                                        <span
                                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs"
                                            aria-hidden="true"
                                            >Rp</span
                                        >
                                        <input
                                            :id="`tarif-${index}-pemerintah`"
                                            v-model="tarif.tarif_pemerintah"
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            class="sb-input pl-10 tabular-nums"
                                            :aria-invalid="invalid(tarifError(index, 'tarif_pemerintah'))"
                                            :aria-describedby="tarifError(index, 'tarif_pemerintah') ? `tarif-${index}-pemerintah-error` : undefined"
                                        />
                                    </div>
                                    <p v-if="tarifError(index, 'tarif_pemerintah')" :id="`tarif-${index}-pemerintah-error`" class="sb-error">
                                        {{ tarifError(index, 'tarif_pemerintah') }}
                                    </p>
                                </div>
                                <div>
                                    <label :for="`tarif-${index}-umum`" class="sb-label">Umum / non-pemerintah</label>
                                    <div class="relative">
                                        <span
                                            class="text-muted-foreground pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs"
                                            aria-hidden="true"
                                            >Rp</span
                                        >
                                        <input
                                            :id="`tarif-${index}-umum`"
                                            v-model="tarif.tarif_non_pemerintah"
                                            type="number"
                                            min="0"
                                            placeholder="0"
                                            class="sb-input pl-10 tabular-nums"
                                            :aria-invalid="invalid(tarifError(index, 'tarif_non_pemerintah'))"
                                            :aria-describedby="tarifError(index, 'tarif_non_pemerintah') ? `tarif-${index}-umum-error` : undefined"
                                        />
                                    </div>
                                    <p v-if="tarifError(index, 'tarif_non_pemerintah')" :id="`tarif-${index}-umum-error`" class="sb-error">
                                        {{ tarifError(index, 'tarif_non_pemerintah') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="mt-3 inline-flex items-center gap-1.5 rounded-sm text-xs font-semibold text-(--wp-accent) hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                            :aria-expanded="expandedTarif === index ? 'true' : 'false'"
                            :aria-controls="`tarif-${index}-advanced`"
                            @click="toggleTarifAdvanced(index)"
                        >
                            <FontAwesomeIcon
                                :icon="['fas', 'chevron-down']"
                                class="size-3 transition-transform"
                                :class="expandedTarif === index ? 'rotate-180' : ''"
                                aria-hidden="true"
                            />
                            {{ expandedTarif === index ? 'Sembunyikan pengaturan lanjutan' : 'Pengaturan lanjutan (waktu, hari, min/max jam…)' }}
                        </button>

                        <div
                            v-if="expandedTarif === index"
                            :id="`tarif-${index}-advanced`"
                            class="bg-card mt-3 grid gap-4 rounded-xl p-4 ring-1 ring-(--wp-hairline) sm:grid-cols-3"
                        >
                            <div>
                                <label :for="`tarif-${index}-slot`" class="sb-label">Waktu</label>
                                <input
                                    :id="`tarif-${index}-slot`"
                                    v-model="tarif.time_slot"
                                    type="text"
                                    maxlength="32"
                                    placeholder="pagi / siang / malam"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifError(index, 'time_slot'))"
                                />
                                <p v-if="tarifError(index, 'time_slot')" class="sb-error">{{ tarifError(index, 'time_slot') }}</p>
                            </div>
                            <label class="block">
                                <span class="sb-label">Hari</span>
                                <SimpleSelect
                                    v-model="tarif.day_type"
                                    :options="dayTypeOptions"
                                    placeholder="Semua hari"
                                    :trigger-class="selectTrigger"
                                />
                                <span v-if="tarifError(index, 'day_type')" class="sb-error block">{{ tarifError(index, 'day_type') }}</span>
                            </label>
                            <div>
                                <label :for="`tarif-${index}-code`" class="sb-label">Kode</label>
                                <input
                                    :id="`tarif-${index}-code`"
                                    v-model="tarif.code"
                                    type="text"
                                    maxlength="96"
                                    class="sb-input font-mono"
                                    :aria-invalid="invalid(tarifError(index, 'code'))"
                                />
                                <p v-if="tarifError(index, 'code')" class="sb-error">{{ tarifError(index, 'code') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-min`" class="sb-label">Min jam</label>
                                <input
                                    :id="`tarif-${index}-min`"
                                    v-model="tarif.min_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    placeholder="—"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(tarifError(index, 'min_hours'))"
                                />
                                <p v-if="tarifError(index, 'min_hours')" class="sb-error">{{ tarifError(index, 'min_hours') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-max`" class="sb-label">Max jam</label>
                                <input
                                    :id="`tarif-${index}-max`"
                                    v-model="tarif.max_hours"
                                    type="number"
                                    min="1"
                                    max="24"
                                    placeholder="—"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(tarifError(index, 'max_hours'))"
                                />
                                <p v-if="tarifError(index, 'max_hours')" class="sb-error">{{ tarifError(index, 'max_hours') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-from`" class="sb-label">Berlaku sejak</label>
                                <input
                                    :id="`tarif-${index}-from`"
                                    v-model="tarif.effective_from"
                                    type="date"
                                    class="sb-input tabular-nums"
                                    :aria-invalid="invalid(tarifError(index, 'effective_from'))"
                                />
                                <p v-if="tarifError(index, 'effective_from')" class="sb-error">{{ tarifError(index, 'effective_from') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-level`" class="sb-label">Event level</label>
                                <input
                                    :id="`tarif-${index}-level`"
                                    v-model="tarif.event_level"
                                    type="text"
                                    maxlength="64"
                                    placeholder="nasional"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifError(index, 'event_level'))"
                                />
                                <p v-if="tarifError(index, 'event_level')" class="sb-error">{{ tarifError(index, 'event_level') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-vehicle`" class="sb-label">Kelas kendaraan</label>
                                <input
                                    :id="`tarif-${index}-vehicle`"
                                    v-model="tarif.vehicle_class"
                                    type="text"
                                    maxlength="64"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifError(index, 'vehicle_class'))"
                                />
                                <p v-if="tarifError(index, 'vehicle_class')" class="sb-error">{{ tarifError(index, 'vehicle_class') }}</p>
                            </div>
                            <div>
                                <label :for="`tarif-${index}-audience`" class="sb-label">Audience type</label>
                                <input
                                    :id="`tarif-${index}-audience`"
                                    v-model="tarif.audience_type"
                                    type="text"
                                    maxlength="32"
                                    class="sb-input"
                                    :aria-invalid="invalid(tarifError(index, 'audience_type'))"
                                />
                                <p v-if="tarifError(index, 'audience_type')" class="sb-error">{{ tarifError(index, 'audience_type') }}</p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="text-muted-foreground hover:text-foreground flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-(--wp-hairline) px-4 py-3.5 text-sm font-semibold transition-colors hover:border-(--wp-accent) hover:bg-(--wp-accent-soft) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                        @click="addTarif"
                    >
                        <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                        Tambah tarif
                    </button>
                </div>
            </section>

            <!-- Step: Ringkasan -->
            <section v-else class="space-y-4" aria-labelledby="step-review-title">
                <div class="sb-card grid gap-6 p-5 sm:p-6 lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-10">
                    <div>
                        <span class="wp-icon size-10" aria-hidden="true"><FontAwesomeIcon :icon="['fas', 'clipboard-check']" class="size-4" /></span>
                        <h2 id="step-review-title" class="mt-3 text-base font-semibold tracking-tight">Ringkasan</h2>
                        <p class="text-muted-foreground mt-1 text-sm">Periksa dulu sebelum menyimpan.</p>
                    </div>

                    <div>
                        <div class="flex items-start gap-4">
                            <AppImage v-if="coverPreview" :src="coverPreview" alt="Cover" class="size-16 shrink-0 rounded-xl object-cover" />
                            <div v-else class="wp-icon size-16 shrink-0 rounded-xl" aria-hidden="true">
                                <FontAwesomeIcon :icon="['fas', 'building']" class="size-6" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold">{{ form.name || 'Tanpa nama' }}</p>
                                <p class="text-muted-foreground font-mono text-xs">{{ form.code || 'tanpa-kode' }}</p>
                                <p v-if="form.description" class="text-muted-foreground mt-1 line-clamp-2 text-sm">{{ form.description }}</p>
                            </div>
                        </div>
                        <dl class="mt-5 grid gap-x-6 gap-y-3 border-t border-(--wp-hairline) pt-4 text-sm sm:grid-cols-2">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Jam operasional</dt>
                                <dd class="font-medium tabular-nums">{{ form.operating_start }}–{{ form.operating_end }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Hari operasional</dt>
                                <dd class="font-medium tabular-nums">{{ form.operating_days.length }} hari</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Area</dt>
                                <dd class="font-medium tabular-nums">{{ form.areas.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Tarif</dt>
                                <dd class="font-medium tabular-nums">{{ form.tarifs.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Fasilitas</dt>
                                <dd class="font-medium tabular-nums">{{ form.facility_ids.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-muted-foreground">Layanan</dt>
                                <dd class="font-medium tabular-nums">{{ form.addon_ids.length }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:col-span-2">
                                <dt class="text-muted-foreground">Tata tertib</dt>
                                <dd class="text-right font-medium">
                                    {{ terms.find((t) => t.value === form.terms_key)?.label ?? 'Tanpa tata tertib' }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="sb-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Area</h3>
                            <button type="button" class="wp-btn wp-btn-quiet px-3 py-1 text-xs" @click="goToKey('areas')">Ubah</button>
                        </div>
                        <ul class="divide-y divide-(--wp-hairline) text-sm">
                            <li v-for="(area, i) in form.areas" :key="i" class="flex items-center justify-between gap-2 py-2 first:pt-0 last:pb-0">
                                <span>{{ area.name || '(tanpa nama)' }}</span>
                                <span class="text-muted-foreground font-mono text-xs">{{ area.code }}</span>
                            </li>
                            <li v-if="form.areas.length === 0" class="text-muted-foreground text-sm">Belum ada area.</li>
                        </ul>
                    </div>

                    <div class="sb-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Tarif</h3>
                            <button type="button" class="wp-btn wp-btn-quiet px-3 py-1 text-xs" @click="goToKey('tarifs')">Ubah</button>
                        </div>
                        <ul class="divide-y divide-(--wp-hairline) text-sm">
                            <li v-for="(tarif, i) in form.tarifs" :key="i" class="py-2 first:pt-0 last:pb-0">
                                <p class="font-medium">{{ tarif.uraian || '(tanpa uraian)' }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ areaLabel(String(tarif.area_code)) }} · {{ satuanLabels[String(tarif.satuan)] ?? tarif.satuan }}
                                </p>
                                <p class="text-muted-foreground text-xs tabular-nums">
                                    Instansi:
                                    {{ tarif.tarif_pemerintah === '' ? '—' : `Rp ${Number(tarif.tarif_pemerintah).toLocaleString('id-ID')}` }} · Umum:
                                    {{ tarif.tarif_non_pemerintah === '' ? '—' : `Rp ${Number(tarif.tarif_non_pemerintah).toLocaleString('id-ID')}` }}
                                </p>
                            </li>
                            <li v-if="form.tarifs.length === 0" class="text-muted-foreground text-sm">Belum ada tarif.</li>
                        </ul>
                    </div>

                    <div class="sb-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Fasilitas</h3>
                            <button type="button" class="wp-btn wp-btn-quiet px-3 py-1 text-xs" @click="goToKey('facilities')">Ubah</button>
                        </div>
                        <ul v-if="form.facility_ids.length" class="flex flex-wrap gap-1.5">
                            <li
                                v-for="facility in facilities.filter((f) => form.facility_ids.includes(f.id))"
                                :key="facility.id"
                                class="sb-badge sb-tone-neutral"
                            >
                                {{ facility.name }}
                            </li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">Belum ada fasilitas dipilih.</p>
                    </div>

                    <div class="sb-card p-5">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Layanan</h3>
                            <button type="button" class="wp-btn wp-btn-quiet px-3 py-1 text-xs" @click="goToKey('addons')">Ubah</button>
                        </div>
                        <ul v-if="form.addon_ids.length" class="flex flex-wrap gap-1.5">
                            <li v-for="addon in addons.filter((a) => form.addon_ids.includes(a.id))" :key="addon.id" class="sb-badge sb-tone-neutral">
                                {{ addon.name }}
                            </li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">Belum ada layanan dipilih.</p>
                    </div>
                </div>
            </section>

            <!-- Sticky action bar -->
            <div class="wp-glass sticky bottom-4 z-10 mt-6 flex flex-wrap items-center gap-2 rounded-2xl px-4 py-3">
                <p class="text-muted-foreground mr-auto text-xs sm:text-sm">
                    Langkah <span class="tabular-nums">{{ step + 1 }} dari {{ steps.length }}</span>
                    <span class="text-foreground font-medium"> · {{ steps[step].label }}</span>
                </p>
                <Link :href="route('e-booking.admin.venues.index')" class="wp-btn wp-btn-quiet px-4 py-2 text-sm">Batal</Link>
                <button v-if="step > 0" type="button" class="wp-btn wp-btn-quiet px-4 py-2 text-sm" @click="goTo(step - 1)">
                    <FontAwesomeIcon :icon="['fas', 'arrow-left']" class="size-3.5" aria-hidden="true" />
                    Kembali
                </button>
                <button v-if="step < lastStep" type="button" class="wp-btn wp-btn-quiet px-4 py-2 text-sm" @click="nextStep">
                    Lanjut
                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                </button>
                <button type="submit" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" :disabled="form.processing">
                    <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-4 animate-spin" aria-hidden="true" />
                    <FontAwesomeIcon v-else :icon="['fas', 'check']" class="size-4" aria-hidden="true" />
                    {{ isEdit ? 'Simpan perubahan' : 'Simpan venue' }}
                </button>
            </div>
        </form>
    </AdminLayout>
</template>
