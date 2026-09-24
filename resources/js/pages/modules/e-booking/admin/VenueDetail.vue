<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle, Plus } from 'lucide-vue-next';
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
    is_active: boolean;
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
    tarifs: Paginator<TarifRow>;
    options: { satuan: string[]; categories: string[] };
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
const areaOptions = computed(() => [
    { value: '', label: '— Tanpa area (level venue)' },
    ...props.areas.data.map((a) => ({ value: String(a.id), label: a.name })),
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
    const options = {
        preserveScroll: true,
        onSuccess: () => resetAreaForm(),
    };

    if (areaEditingId.value) {
        areaForm.put(route('e-booking.admin.areas.update', areaEditingId.value), options);
    } else {
        areaForm.post(route('e-booking.admin.areas.store', props.venue.id), options);
    }
};

const toggleArea = (row: AreaRow) => {
    router.post(route('e-booking.admin.areas.toggle', row.id), {}, { preserveScroll: true });
};

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
    is_active: boolean;
}>({
    area_id: '',
    code: '',
    uraian: '',
    satuan: props.options.satuan[0] ?? 'per_hour',
    tarif_pemerintah: '',
    tarif_non_pemerintah: '',
    time_slot: '',
    event_level: '',
    category: 'olahraga',
    is_active: true,
});

const startEditTarif = (row: TarifRow) => {
    tarifEditingId.value = row.id;
    tarifForm.clearErrors();
    tarifForm.area_id = row.area_id ? String(row.area_id) : '';
    tarifForm.code = row.code ?? '';
    tarifForm.uraian = row.uraian;
    tarifForm.satuan = row.satuan;
    tarifForm.tarif_pemerintah = row.tarif_pemerintah ?? '';
    tarifForm.tarif_non_pemerintah = row.tarif_non_pemerintah ?? '';
    tarifForm.time_slot = row.time_slot ?? '';
    tarifForm.event_level = row.event_level ?? '';
    tarifForm.category = row.category ?? 'olahraga';
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
        area_id: data.area_id ? Number(data.area_id) : null,
        tarif_pemerintah: data.tarif_pemerintah === '' ? null : Number(data.tarif_pemerintah),
        tarif_non_pemerintah: data.tarif_non_pemerintah === '' ? null : Number(data.tarif_non_pemerintah),
        code: data.code || null,
        time_slot: data.time_slot || null,
        event_level: data.event_level || null,
    });

    const options = {
        preserveScroll: true,
        onSuccess: () => resetTarifForm(),
    };

    if (tarifEditingId.value) {
        tarifForm.transform(transform).put(route('e-booking.admin.tarifs.update', tarifEditingId.value), options);
    } else {
        tarifForm.transform(transform).post(route('e-booking.admin.tarifs.store', props.venue.id), options);
    }
};

const toggleTarif = (row: TarifRow) => {
    router.post(route('e-booking.admin.tarifs.toggle', row.id), {}, { preserveScroll: true });
};
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
                            <input v-model="areaForm.is_tentative" type="checkbox" class="size-4" />
                            Tentatif
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input v-model="areaForm.is_active" type="checkbox" class="size-4" />
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

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[560px] text-left text-sm">
                        <thead class="border-border text-muted-foreground border-b">
                            <tr>
                                <th class="py-2 pr-3 font-medium">Nama</th>
                                <th class="py-2 pr-3 font-medium">Kode</th>
                                <th class="py-2 pr-3 font-medium">Tentatif</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                <th class="py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in areas.data" :key="row.id" class="border-border/60 border-b">
                                <td class="py-3 pr-3 font-medium">{{ row.name }}</td>
                                <td class="py-3 pr-3">
                                    <code class="text-xs">{{ row.code }}</code>
                                </td>
                                <td class="py-3 pr-3">{{ row.is_tentative ? 'Ya' : '—' }}</td>
                                <td class="py-3 pr-3">
                                    <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-slate-600 hover:underline"
                                            @click="startEditArea(row)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold hover:underline"
                                            :class="row.is_active ? 'text-red-600' : 'text-emerald-700'"
                                            @click="toggleArea(row)"
                                        >
                                            {{ row.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="areas.data.length === 0">
                                <td colspan="5" class="text-muted-foreground py-8 text-center">Belum ada area.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="areas.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <template v-for="(link, i) in areas.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="border-border rounded-md border px-3 py-1 text-xs"
                            :class="link.active ? 'bg-muted font-semibold' : ''"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </template>
                </div>
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
                        <label class="mb-1.5 block text-sm font-medium">Tarif pemerintah</label>
                        <Input v-model="tarifForm.tarif_pemerintah" type="number" min="0" placeholder="0" />
                        <InputError :message="tarifForm.errors.tarif_pemerintah" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Tarif non-pemerintah</label>
                        <Input v-model="tarifForm.tarif_non_pemerintah" type="number" min="0" placeholder="0" />
                        <InputError :message="tarifForm.errors.tarif_non_pemerintah" />
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
                        <input id="tarif-active" v-model="tarifForm.is_active" type="checkbox" class="size-4" />
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

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-border text-muted-foreground border-b">
                            <tr>
                                <th class="py-2 pr-3 font-medium">Uraian</th>
                                <th class="py-2 pr-3 font-medium">Area</th>
                                <th class="py-2 pr-3 font-medium">Satuan</th>
                                <th class="py-2 pr-3 font-medium">Pemerintah</th>
                                <th class="py-2 pr-3 font-medium">Non-pem.</th>
                                <th class="py-2 pr-3 font-medium">Status</th>
                                <th class="py-2 font-medium"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in tarifs.data" :key="row.id" class="border-border/60 border-b">
                                <td class="py-3 pr-3">
                                    <p class="font-medium">{{ row.uraian }}</p>
                                    <p class="text-muted-foreground text-xs">
                                        {{ row.category ? (categoryLabels[row.category] ?? row.category) : '—' }}
                                    </p>
                                </td>
                                <td class="py-3 pr-3">{{ row.area_name || 'Venue' }}</td>
                                <td class="py-3 pr-3">{{ satuanLabels[row.satuan] ?? row.satuan }}</td>
                                <td class="py-3 pr-3 whitespace-nowrap">{{ formatRupiah(row.tarif_pemerintah) }}</td>
                                <td class="py-3 pr-3 whitespace-nowrap">{{ formatRupiah(row.tarif_non_pemerintah) }}</td>
                                <td class="py-3 pr-3">
                                    <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                                </td>
                                <td class="py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-slate-600 hover:underline"
                                            @click="startEditTarif(row)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="text-xs font-semibold hover:underline"
                                            :class="row.is_active ? 'text-red-600' : 'text-emerald-700'"
                                            @click="toggleTarif(row)"
                                        >
                                            {{ row.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="tarifs.data.length === 0">
                                <td colspan="7" class="text-muted-foreground py-8 text-center">Belum ada tarif.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="tarifs.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
                    <template v-for="(link, i) in tarifs.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="border-border rounded-md border px-3 py-1 text-xs"
                            :class="link.active ? 'bg-muted font-semibold' : ''"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </template>
                </div>
            </TabsContent>
        </Tabs>
    </AdminLayout>
</template>
