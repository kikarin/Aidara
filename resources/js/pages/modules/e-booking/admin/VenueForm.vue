<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import TimeField from '@/components/TimeField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import {
    Accessibility,
    Armchair,
    ArrowLeft,
    ArrowRight,
    Baby,
    Building2,
    Car,
    Check,
    Circle,
    Clock,
    Flag,
    Grid3x3,
    Landmark,
    LayoutGrid,
    Lightbulb,
    LoaderCircle,
    MapPin,
    Megaphone,
    Monitor,
    Plus,
    Ruler,
    ScrollText,
    Shield,
    ShowerHead,
    Sparkles,
    Tag,
    Target,
    Timer,
    Trash2,
    Trophy,
    Utensils,
    Wifi,
    Wrench,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    terms_key: string | null;
};

type FacilityOption = {
    id: number;
    code: string;
    name: string;
    icon: string | null;
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
    terms: TermOption[];
    options: { satuan: string[]; categories: string[] };
}>();

const facilityIcons: Record<string, unknown> = {
    car: Car,
    utensils: Utensils,
    'shower-head': ShowerHead,
    wifi: Wifi,
    landmark: Landmark,
    shield: Shield,
    accessibility: Accessibility,
    baby: Baby,
    armchair: Armchair,
    zap: Zap,
    monitor: Monitor,
    'grid-3x3': Grid3x3,
    target: Target,
    circle: Circle,
    lightbulb: Lightbulb,
    flag: Flag,
    megaphone: Megaphone,
    timer: Timer,
    trophy: Trophy,
    ruler: Ruler,
    wrench: Wrench,
};
const facilityIcon = (icon: string | null) => (icon && facilityIcons[icon] ? facilityIcons[icon] : null);

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
              { key: 'info', label: 'Info venue', icon: Building2 },
              { key: 'hours', label: 'Jam operasional', icon: Clock },
              { key: 'facilities', label: 'Fasilitas', icon: LayoutGrid },
              { key: 'terms', label: 'Tata tertib', icon: ScrollText },
          ]
        : [
              { key: 'info', label: 'Info venue', icon: Building2 },
              { key: 'hours', label: 'Jam operasional', icon: Clock },
              { key: 'areas', label: 'Area', icon: MapPin },
              { key: 'facilities', label: 'Fasilitas', icon: LayoutGrid },
              { key: 'terms', label: 'Tata tertib', icon: ScrollText },
              { key: 'tarifs', label: 'Harga sewa', icon: Tag },
              { key: 'review', label: 'Ringkasan', icon: Sparkles },
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
        <Link :href="route('e-booking.admin.venues.index')" class="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline">
            <ArrowLeft class="size-4" />
            Kembali ke daftar venue
        </Link>

        <div class="mt-3">
            <h1 class="text-foreground text-2xl font-bold">{{ isEdit ? 'Ubah venue' : 'Tambah venue' }}</h1>
            <p class="text-muted-foreground mt-1 text-sm">
                {{
                    isEdit ? 'Perbarui informasi dan jam operasional venue.' : 'Ikuti langkah berikut untuk membuat venue beserta area dan harganya.'
                }}
            </p>
        </div>

        <!-- Step indicator -->
        <ol class="mt-6 flex flex-wrap items-center gap-x-2 gap-y-3">
            <li v-for="(s, i) in steps" :key="s.key" class="flex items-center gap-2">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                    :class="
                        i === step
                            ? 'border-sky-600 bg-sky-600 text-white'
                            : i < step
                              ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                              : 'border-slate-200 bg-white text-slate-500'
                    "
                    @click="goTo(i)"
                >
                    <Check v-if="i < step" class="size-3.5" />
                    <component :is="s.icon" v-else class="size-3.5" />
                    {{ s.label }}
                </button>
                <ArrowRight v-if="i < lastStep" class="text-muted-foreground size-3.5" />
            </li>
        </ol>

        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full bg-sky-500 transition-all" :style="{ width: `${progress}%` }" />
        </div>

        <form class="mt-5 max-w-3xl" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-semibold">{{ stepError || `Ada ${Object.keys(form.errors).length} isian yang perlu diperbaiki.` }}</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
                </ul>
            </div>
            <!-- Step: Info venue -->
            <section v-if="currentKey === 'info'" class="border-border space-y-4 rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-semibold">Info venue</h2>
                    <p class="text-muted-foreground text-sm">Identitas dasar tempat yang akan disewakan.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama venue</label>
                        <Input v-model="form.name" type="text" maxlength="150" placeholder="Stadion Pakansari" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="form.code" type="text" maxlength="64" placeholder="pakansari" />
                        <p class="text-muted-foreground mt-1 text-xs">Kode unik, huruf kecil & garis bawah. Contoh: pakansari</p>
                        <InputError :message="form.errors.code" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Deskripsi</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            placeholder="Deskripsi singkat (opsional)"
                        ></textarea>
                        <InputError :message="form.errors.description" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan tampil</label>
                        <Input v-model="form.sort_order" type="number" min="0" />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 pb-2 text-sm font-medium">
                            <Checkbox v-model="form.is_active" />
                            Tampilkan di katalog
                        </label>
                    </div>
                </div>

                <div class="border-border/70 rounded-xl border border-dashed p-4">
                    <label class="mb-1.5 block text-sm font-medium">Foto cover (opsional)</label>
                    <input
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp,.svg"
                        class="border-border bg-background w-full rounded-lg border px-3 py-1.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm"
                        @change="onCoverChange"
                    />
                    <InputError :message="form.errors.cover" />
                    <AppImage
                        v-if="coverPreview"
                        :src="coverPreview"
                        alt="Pratinjau cover"
                        class="mt-3 h-36 w-full max-w-xs rounded-xl object-cover"
                    />
                </div>
            </section>

            <!-- Step: Jam operasional -->
            <section v-else-if="currentKey === 'hours'" class="border-border space-y-4 rounded-2xl border bg-white p-5 shadow-sm sm:p-6">
                <div>
                    <h2 class="text-lg font-semibold">Jam operasional</h2>
                    <p class="text-muted-foreground text-sm">Kapan venue ini buka dan hari apa saja.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Jam buka</label>
                        <TimeField v-model="form.operating_start" />
                        <InputError :message="form.errors.operating_start" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Jam tutup</label>
                        <TimeField v-model="form.operating_end" />
                        <InputError :message="form.errors.operating_end" />
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Hari operasional</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="day in days"
                            :key="day"
                            type="button"
                            class="rounded-full border px-3 py-1.5 text-xs font-semibold capitalize transition"
                            :class="
                                form.operating_days.includes(day)
                                    ? 'border-sky-600 bg-sky-50 text-sky-700'
                                    : 'border-slate-200 bg-white text-slate-500'
                            "
                            @click="toggleInArray(form.operating_days, day, !form.operating_days.includes(day))"
                        >
                            {{ day }}
                        </button>
                    </div>
                    <InputError :message="form.errors.operating_days" />
                </div>
            </section>

            <!-- Step: Area -->
            <section v-else-if="currentKey === 'areas'" class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Area yang disewakan</h2>
                        <p class="text-muted-foreground text-sm">Tambahkan setiap area/lapangan. Bisa lebih dari satu.</p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addArea">
                        <Plus class="size-4" />
                        Tambah area
                    </Button>
                </div>

                <div v-for="(area, index) in form.areas" :key="index" class="border-border rounded-2xl border bg-white p-4 shadow-sm sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold">Area {{ index + 1 }}</p>
                        <button
                            type="button"
                            class="flex items-center gap-1 text-xs font-semibold text-red-600 hover:underline"
                            @click="removeArea(index)"
                        >
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Nama area</label>
                            <Input v-model="area.name" type="text" maxlength="150" placeholder="Lapangan Utama" />
                            <InputError :message="areaError(index, 'name')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Kode</label>
                            <Input v-model="area.code" type="text" maxlength="64" placeholder="lapangan_utama" />
                            <InputError :message="areaError(index, 'code')" />
                        </div>
                        <div class="flex items-center gap-5">
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="area.is_tentative" />
                                Tentatif
                            </label>
                            <label class="flex items-center gap-2 text-sm font-medium">
                                <Checkbox v-model="area.is_active" />
                                Aktif
                            </label>
                        </div>
                        <div class="flex items-end justify-end">
                            <button
                                v-if="area.code"
                                type="button"
                                class="text-xs font-semibold text-sky-700 hover:underline"
                                @click="addTarifForArea(area.code)"
                            >
                                + Tambah harga untuk area ini
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step: Fasilitas -->
            <section v-else-if="currentKey === 'facilities'" class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Fasilitas</h2>
                    <p class="text-muted-foreground text-sm">Pilih fasilitas yang tersedia di venue ini (opsional).</p>
                </div>

                <div v-if="facilities.length" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="facility in facilities"
                        :key="facility.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm transition"
                        :class="
                            form.facility_ids.includes(facility.id) ? 'border-sky-500 bg-sky-50' : 'border-slate-200 bg-white hover:border-slate-300'
                        "
                    >
                        <Checkbox
                            :model-value="form.facility_ids.includes(facility.id)"
                            @update:model-value="(value) => toggleFacility(facility.id, !!value)"
                        />
                        <component :is="facilityIcon(facility.icon)" v-if="facilityIcon(facility.icon)" class="size-4 text-sky-700" />
                        <span class="font-medium">{{ facility.name }}</span>
                    </label>
                </div>
                <div v-else class="text-muted-foreground rounded-xl border border-dashed px-4 py-8 text-center text-sm">
                    Belum ada fasilitas. Tambahkan dulu di menu Fasilitas.
                </div>
                <InputError :message="form.errors.facility_ids" />
            </section>

            <!-- Step: Tata tertib -->
            <section v-else-if="currentKey === 'terms'" class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Tata tertib</h2>
                    <p class="text-muted-foreground text-sm">
                        Pilih tata tertib yang berlaku di venue ini. Isinya akan tampil ke penyewa saat mengajukan booking.
                    </p>
                </div>

                <div class="border-border space-y-2 rounded-2xl border bg-white p-5 shadow-sm">
                    <label class="block text-sm font-medium">Tata tertib</label>
                    <SimpleSelect
                        v-model="form.terms_key"
                        :options="terms"
                        placeholder="— Tanpa tata tertib —"
                        trigger-class="border-border bg-background h-10 w-full rounded-lg px-3 text-sm shadow-none"
                    />
                    <InputError :message="form.errors.terms_key" />
                    <p v-if="terms.length" class="text-muted-foreground text-xs">
                        Kelola isi tata tertib di menu
                        <Link :href="route('e-booking.admin.terms.index')" class="font-semibold text-sky-700 hover:underline">Tata tertib</Link>.
                    </p>
                    <p v-else class="text-muted-foreground text-sm">Belum ada tata tertib. Buat dulu di menu Tata tertib.</p>
                </div>
            </section>

            <!-- Step: Harga -->
            <section v-else-if="currentKey === 'tarifs'" class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">Harga sewa</h2>
                        <p class="text-muted-foreground text-sm">
                            Satu kartu = satu jenis tarif. Harga bisa beda per area, siang/malam, atau weekday/weekend.
                        </p>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="addTarif">
                        <Plus class="size-4" />
                        Tambah tarif
                    </Button>
                </div>

                <div v-for="(tarif, index) in form.tarifs" :key="index" class="border-border rounded-2xl border bg-white p-4 shadow-sm sm:p-5">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">{{
                                index + 1
                            }}</span>
                            <p class="truncate text-sm font-semibold">{{ tarif.uraian || 'Tarif baru' }}</p>
                        </div>
                        <button
                            type="button"
                            class="flex shrink-0 items-center gap-1 text-xs font-semibold text-red-600 hover:underline"
                            @click="removeTarif(index)"
                        >
                            <Trash2 class="size-3.5" />
                            Hapus
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium">Uraian / nama tarif</label>
                            <Input v-model="tarif.uraian" type="text" maxlength="255" placeholder="Contoh: Sewa Lapangan – Malam" />
                            <InputError :message="tarifError(index, 'uraian')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Berlaku untuk area</label>
                            <SimpleSelect v-model="tarif.area_code" :options="areaOptions" placeholder="Semua area" />
                            <InputError :message="tarifError(index, 'area_code')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Satuan</label>
                            <SimpleSelect v-model="tarif.satuan" :options="satuanOptions" />
                            <InputError :message="tarifError(index, 'satuan')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Kategori</label>
                            <SimpleSelect v-model="tarif.category" :options="categoryOptions" />
                            <InputError :message="tarifError(index, 'category')" />
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border border-sky-100 bg-sky-50/60 p-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">Harga</p>
                            <p class="text-muted-foreground text-xs">Isi minimal salah satu</p>
                        </div>
                        <div class="mt-3 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Instansi pemerintah</label>
                                <div class="relative">
                                    <span class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs">Rp</span>
                                    <Input v-model="tarif.tarif_pemerintah" type="number" min="0" placeholder="0" class="pl-9" />
                                </div>
                                <InputError :message="tarifError(index, 'tarif_pemerintah')" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium">Umum / non-pemerintah</label>
                                <div class="relative">
                                    <span class="text-muted-foreground pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-xs">Rp</span>
                                    <Input v-model="tarif.tarif_non_pemerintah" type="number" min="0" placeholder="0" class="pl-9" />
                                </div>
                                <InputError :message="tarifError(index, 'tarif_non_pemerintah')" />
                            </div>
                        </div>
                    </div>

                    <button type="button" class="mt-3 text-xs font-semibold text-sky-700 hover:underline" @click="toggleTarifAdvanced(index)">
                        {{ expandedTarif === index ? 'Sembunyikan pengaturan lanjutan' : 'Pengaturan lanjutan (waktu, hari, min/max jam…)' }}
                    </button>

                    <div v-if="expandedTarif === index" class="mt-3 grid gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Waktu</label>
                            <Input v-model="tarif.time_slot" type="text" maxlength="32" placeholder="pagi / siang / malam" />
                            <InputError :message="tarifError(index, 'time_slot')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Hari</label>
                            <SimpleSelect v-model="tarif.day_type" :options="dayTypeOptions" placeholder="Semua hari" />
                            <InputError :message="tarifError(index, 'day_type')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Kode</label>
                            <Input v-model="tarif.code" type="text" maxlength="96" />
                            <InputError :message="tarifError(index, 'code')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Min jam</label>
                            <Input v-model="tarif.min_hours" type="number" min="1" max="24" placeholder="—" />
                            <InputError :message="tarifError(index, 'min_hours')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Max jam</label>
                            <Input v-model="tarif.max_hours" type="number" min="1" max="24" placeholder="—" />
                            <InputError :message="tarifError(index, 'max_hours')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Berlaku sejak</label>
                            <Input v-model="tarif.effective_from" type="date" />
                            <InputError :message="tarifError(index, 'effective_from')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Event level</label>
                            <Input v-model="tarif.event_level" type="text" maxlength="64" placeholder="nasional" />
                            <InputError :message="tarifError(index, 'event_level')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Kelas kendaraan</label>
                            <Input v-model="tarif.vehicle_class" type="text" maxlength="64" />
                            <InputError :message="tarifError(index, 'vehicle_class')" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Audience type</label>
                            <Input v-model="tarif.audience_type" type="text" maxlength="32" />
                            <InputError :message="tarifError(index, 'audience_type')" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step: Ringkasan -->
            <section v-else class="space-y-4">
                <div>
                    <h2 class="text-lg font-semibold">Ringkasan</h2>
                    <p class="text-muted-foreground text-sm">Periksa dulu sebelum menyimpan.</p>
                </div>

                <div class="border-border rounded-2xl border bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <AppImage v-if="coverPreview" :src="coverPreview" alt="Cover" class="size-16 rounded-xl object-cover" />
                        <div v-else class="flex size-16 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                            <Building2 class="size-7" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold">{{ form.name || 'Tanpa nama' }}</p>
                            <p class="text-muted-foreground text-xs">
                                <code>{{ form.code || 'tanpa-kode' }}</code>
                            </p>
                            <p v-if="form.description" class="text-muted-foreground mt-1 line-clamp-2 text-sm">{{ form.description }}</p>
                        </div>
                    </div>
                    <div class="border-border/70 mt-4 grid gap-3 border-t pt-4 text-sm sm:grid-cols-2">
                        <div class="flex items-center gap-2">
                            <Clock class="text-muted-foreground size-4" />
                            Jam {{ form.operating_start }}–{{ form.operating_end }}
                        </div>
                        <div class="text-muted-foreground">{{ form.operating_days.length }} hari operasional</div>
                        <div class="flex items-center gap-2">
                            <MapPin class="text-muted-foreground size-4" />
                            {{ form.areas.length }} area
                        </div>
                        <div class="flex items-center gap-2">
                            <Tag class="text-muted-foreground size-4" />
                            {{ form.tarifs.length }} tarif
                        </div>
                        <div class="flex items-center gap-2">
                            <LayoutGrid class="text-muted-foreground size-4" />
                            {{ form.facility_ids.length }} fasilitas
                        </div>
                        <div class="flex items-center gap-2">
                            <ScrollText class="text-muted-foreground size-4" />
                            {{ terms.find((t) => t.value === form.terms_key)?.label ?? 'Tanpa tata tertib' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="border-border rounded-2xl border bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold">Area</p>
                            <button type="button" class="text-xs font-semibold text-sky-700 hover:underline" @click="goToKey('areas')">Ubah</button>
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li v-for="(area, i) in form.areas" :key="i" class="flex items-center justify-between gap-2">
                                <span>{{ area.name || '(tanpa nama)' }}</span>
                                <code class="text-muted-foreground text-xs">{{ area.code }}</code>
                            </li>
                            <li v-if="form.areas.length === 0" class="text-muted-foreground text-sm">Belum ada area.</li>
                        </ul>
                    </div>

                    <div class="border-border rounded-2xl border bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold">Tarif</p>
                            <button type="button" class="text-xs font-semibold text-sky-700 hover:underline" @click="goToKey('tarifs')">Ubah</button>
                        </div>
                        <ul class="space-y-3 text-sm">
                            <li v-for="(tarif, i) in form.tarifs" :key="i" class="border-border/60 border-b pb-2 last:border-0 last:pb-0">
                                <p class="font-medium">{{ tarif.uraian || '(tanpa uraian)' }}</p>
                                <p class="text-muted-foreground text-xs">
                                    {{ areaLabel(String(tarif.area_code)) }} · {{ satuanLabels[String(tarif.satuan)] ?? tarif.satuan }}
                                </p>
                                <p class="text-muted-foreground text-xs">
                                    Instansi:
                                    {{ tarif.tarif_pemerintah === '' ? '—' : `Rp ${Number(tarif.tarif_pemerintah).toLocaleString('id-ID')}` }} · Umum:
                                    {{ tarif.tarif_non_pemerintah === '' ? '—' : `Rp ${Number(tarif.tarif_non_pemerintah).toLocaleString('id-ID')}` }}
                                </p>
                            </li>
                            <li v-if="form.tarifs.length === 0" class="text-muted-foreground text-sm">Belum ada tarif.</li>
                        </ul>
                    </div>

                    <div class="border-border rounded-2xl border bg-white p-5 shadow-sm">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold">Fasilitas</p>
                            <button type="button" class="text-xs font-semibold text-sky-700 hover:underline" @click="goToKey('facilities')">
                                Ubah
                            </button>
                        </div>
                        <ul v-if="form.facility_ids.length" class="flex flex-wrap gap-1.5">
                            <li
                                v-for="facility in facilities.filter((f) => form.facility_ids.includes(f.id))"
                                :key="facility.id"
                                class="bg-muted rounded-full px-2.5 py-0.5 text-xs font-medium"
                            >
                                {{ facility.name }}
                            </li>
                        </ul>
                        <p v-else class="text-muted-foreground text-sm">Belum ada fasilitas dipilih.</p>
                    </div>
                </div>
            </section>

            <!-- Sticky nav -->
            <div class="border-border bg-background/95 sticky bottom-0 z-10 mt-5 flex flex-wrap items-center gap-2 border-t py-3 backdrop-blur">
                <Button v-if="step > 0" type="button" variant="outline" @click="goTo(step - 1)">
                    <ArrowLeft class="size-4" />
                    Kembali
                </Button>
                <Button v-if="step < lastStep" type="button" variant="outline" @click="nextStep">
                    Lanjut
                    <ArrowRight class="size-4" />
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    <Check v-else class="size-4" />
                    {{ isEdit ? 'Simpan perubahan' : 'Simpan venue' }}
                </Button>
                <span class="text-muted-foreground ml-auto text-xs"> Langkah {{ step + 1 }} dari {{ steps.length }} · {{ steps[step].label }} </span>
            </div>
        </form>
    </AdminLayout>
</template>
