<script setup lang="ts">
import FacilityIcon from '@/components/e-booking/FacilityIcon.vue';
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faBan, faCircleNotch, faPen, faPlus, faPowerOff, faTableCellsLarge } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

library.add(faBan, faCircleNotch, faPen, faPlus, faPowerOff, faTableCellsLarge);

type FacilityRow = {
    id: number;
    code: string;
    name: string;
    icon: string | null;
    description: string | null;
    is_active: boolean;
    sort_order: number;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};

defineProps<{
    facilities: Paginator<FacilityRow>;
}>();

const iconOptions = [
    { value: '', label: 'Tanpa ikon' },
    { value: 'car', label: 'Parkir' },
    { value: 'utensils', label: 'Kantin' },
    { value: 'shower-head', label: 'Toilet / ruang ganti' },
    { value: 'wifi', label: 'Wifi' },
    { value: 'landmark', label: 'Mushola' },
    { value: 'shield', label: 'Keamanan' },
    { value: 'accessibility', label: 'Akses difabel' },
    { value: 'baby', label: 'Ruang laktasi' },
    { value: 'armchair', label: 'Tribun / ruang tunggu' },
    { value: 'zap', label: 'Listrik' },
    { value: 'monitor', label: 'Papan skor' },
    { value: 'grid-3x3', label: 'Net / jarring' },
    { value: 'target', label: 'Gawang / sasaran' },
    { value: 'circle', label: 'Ring basket' },
    { value: 'lightbulb', label: 'Lampu / penerangan' },
    { value: 'flag', label: 'Garis / bendera lapangan' },
    { value: 'megaphone', label: 'Pengeras suara' },
    { value: 'timer', label: 'Papan waktu' },
    { value: 'trophy', label: 'Podium / trofi' },
    { value: 'ruler', label: 'Ukuran lapangan' },
    { value: 'wrench', label: 'Peralatan' },
];

const iconLabel = (value: string | null) => iconOptions.find((o) => o.value === value)?.label ?? '—';

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm<{
    code: string;
    name: string;
    icon: string;
    description: string;
    sort_order: number | string;
    is_active: boolean;
}>({
    code: '',
    name: '',
    icon: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

const resetForm = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
};

const openCreate = () => {
    resetForm();
    dialogOpen.value = true;
};

const openEdit = (row: FacilityRow) => {
    editingId.value = row.id;
    form.clearErrors();
    form.code = row.code;
    form.name = row.name;
    form.icon = row.icon ?? '';
    form.description = row.description ?? '';
    form.sort_order = row.sort_order;
    form.is_active = row.is_active;
    dialogOpen.value = true;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            dialogOpen.value = false;
        },
    };
    const transform = (data: ReturnType<typeof form.data>) => ({
        ...data,
        icon: data.icon || null,
        sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
    });

    if (editingId.value) {
        form.transform(transform).put(route('e-booking.admin.facilities.update', editingId.value), options);
    } else {
        form.transform(transform).post(route('e-booking.admin.facilities.store'), options);
    }
};

const toggle = (row: FacilityRow) => {
    router.post(route('e-booking.admin.facilities.toggle', row.id), {}, { preserveScroll: true });
};

const rowActions = (row: FacilityRow) => [
    { label: 'Edit', icon: 'pen', onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: 'power-off',
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan fasilitas ini?',
                  description: 'Fasilitas akan disembunyikan dari daftar venue.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggle(row),
    },
];
</script>

<template>
    <SeoHead title="Fasilitas E-Booking" />

    <AdminLayout active="facilities">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Fasilitas</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                    Fasilitas umum yang tersedia di venue, seperti parkir, toilet, dan mushola.
                </p>
            </div>
            <button type="button" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" @click="openCreate">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                Tambah fasilitas
            </button>
        </div>

        <div class="sb-card mt-6 overflow-x-auto">
            <div v-if="facilities.data.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                <span class="wp-icon size-12" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'table-cells-large']" class="size-5" />
                </span>
                <h2 class="mt-4 text-base font-semibold tracking-tight">Belum ada fasilitas</h2>
                <p class="text-muted-foreground mt-1 max-w-sm text-sm">Fasilitas yang ditambahkan bisa dipilih saat mengatur venue.</p>
                <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                    Tambah fasilitas
                </button>
            </div>
            <table v-else class="sb-table min-w-[40rem]">
                <caption class="sr-only">
                    Daftar fasilitas
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Ikon</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="w-12"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in facilities.data" :key="row.id">
                        <td>
                            <div class="flex items-center gap-3">
                                <span class="wp-icon size-9 shrink-0" aria-hidden="true">
                                    <FacilityIcon :icon="row.icon" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-foreground font-medium">{{ row.name }}</p>
                                    <p class="text-muted-foreground mt-0.5 text-xs">{{ row.description || '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 font-mono text-xs">{{ row.code }}</code>
                        </td>
                        <td class="text-muted-foreground text-sm">{{ iconLabel(row.icon) }}</td>
                        <td>
                            <span class="sb-badge" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <RowActionsMenu :items="rowActions(row)" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <TablePagination :links="facilities.links" :from="facilities.from" :to="facilities.to" :total="facilities.total" label="fasilitas" />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="bg-card max-h-[90dvh] overflow-y-auto rounded-3xl border-(--wp-hairline) p-6 sm:max-w-xl">
                <DialogHeader class="gap-1.5">
                    <DialogTitle class="text-lg font-semibold tracking-tight">{{ editingId ? 'Ubah fasilitas' : 'Tambah fasilitas' }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm">Fasilitas umum yang tersedia di venue.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label for="facility-name" class="sb-label">Nama</label>
                        <input id="facility-name" v-model="form.name" type="text" maxlength="150" placeholder="Toilet" class="sb-input" />
                        <p v-if="form.errors.name" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="facility-code" class="sb-label">Kode</label>
                        <input id="facility-code" v-model="form.code" type="text" maxlength="64" placeholder="toilet" class="sb-input" />
                        <p v-if="form.errors.code" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.code }}</p>
                    </div>
                    <fieldset class="sm:col-span-2">
                        <legend class="sb-label">Ikon</legend>
                        <div class="grid grid-cols-6 gap-1.5 sm:grid-cols-11">
                            <button
                                v-for="option in iconOptions"
                                :key="option.value || 'none'"
                                type="button"
                                class="sb-chip aspect-square justify-center p-0"
                                :aria-pressed="form.icon === option.value ? 'true' : 'false'"
                                :aria-label="option.label"
                                :title="option.label"
                                @click="form.icon = option.value"
                            >
                                <FontAwesomeIcon v-if="option.value === ''" :icon="['fas', 'ban']" class="size-4" aria-hidden="true" />
                                <FacilityIcon v-else :icon="option.value" class="size-4" />
                            </button>
                        </div>
                        <p class="sb-hint">
                            Terpilih: <span class="text-foreground font-medium">{{ iconLabel(form.icon) }}</span>
                        </p>
                        <p v-if="form.errors.icon" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.icon }}</p>
                    </fieldset>
                    <div>
                        <label for="facility-sort" class="sb-label">Urutan</label>
                        <input id="facility-sort" v-model="form.sort_order" type="number" min="0" class="sb-input tabular-nums" />
                        <p v-if="form.errors.sort_order" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.sort_order }}</p>
                    </div>
                    <div class="flex items-center gap-2.5 sm:pt-7">
                        <Checkbox
                            id="facility-active"
                            v-model="form.is_active"
                            class="data-[state=checked]:border-(--wp-accent) data-[state=checked]:bg-(--wp-accent) data-[state=checked]:text-(--wp-accent-contrast)"
                        />
                        <label for="facility-active" class="text-sm font-medium">Aktif</label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="facility-description" class="sb-label">Deskripsi</label>
                        <textarea id="facility-description" v-model="form.description" rows="2" class="sb-input resize-y"></textarea>
                        <p v-if="form.errors.description" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.description }}</p>
                    </div>
                </form>

                <DialogFooter class="mt-2 gap-2">
                    <button type="button" class="wp-btn wp-btn-quiet justify-center px-4 py-2 text-sm" @click="dialogOpen = false">Batal</button>
                    <button
                        type="button"
                        class="wp-btn wp-btn-primary justify-center px-5 py-2.5 text-sm"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        <FontAwesomeIcon v-if="form.processing" :icon="['fas', 'circle-notch']" class="size-3.5 animate-spin" aria-hidden="true" />
                        Simpan
                    </button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
