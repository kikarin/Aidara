<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faBoxOpen, faCircleNotch, faPen, faPlus, faPowerOff } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

library.add(faBoxOpen, faCircleNotch, faPen, faPlus, faPowerOff);

type AddonRow = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    harga: number | null;
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
    addons: Paginator<AddonRow>;
}>();

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm<{
    code: string;
    name: string;
    description: string;
    harga: number | string;
    sort_order: number | string;
    is_active: boolean;
}>({
    code: '',
    name: '',
    description: '',
    harga: '',
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

const openEdit = (row: AddonRow) => {
    editingId.value = row.id;
    form.clearErrors();
    form.code = row.code;
    form.name = row.name;
    form.description = row.description ?? '';
    form.harga = row.harga ?? '';
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
        harga: data.harga === '' ? null : Number(data.harga),
        sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
    });

    if (editingId.value) {
        form.transform(transform).put(route('e-booking.admin.addons.update', editingId.value), options);
    } else {
        form.transform(transform).post(route('e-booking.admin.addons.store'), options);
    }
};

const toggle = (row: AddonRow) => {
    router.post(route('e-booking.admin.addons.toggle', row.id), {}, { preserveScroll: true });
};

const rowActions = (row: AddonRow) => [
    { label: 'Edit', icon: 'pen', onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: 'power-off',
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan layanan ini?',
                  description: 'Layanan akan disembunyikan dari pilihan penyewa.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggle(row),
    },
];

const formatRupiah = (value: number | null) => (value === null || value === undefined ? '—' : new Intl.NumberFormat('id-ID').format(value));
</script>

<template>
    <SeoHead title="Tambahan Layanan E-Booking" />

    <AdminLayout active="addons">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Tambahan layanan</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                    Layanan tambahan, misalnya loading atau closing, yang bisa dipilih penyewa saat mengajukan booking.
                </p>
            </div>
            <button type="button" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" @click="openCreate">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                Tambah layanan
            </button>
        </div>

        <div class="sb-card mt-6 overflow-x-auto">
            <div v-if="addons.data.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                <span class="wp-icon size-12" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'box-open']" class="size-5" />
                </span>
                <h2 class="mt-4 text-base font-semibold tracking-tight">Belum ada layanan tambahan</h2>
                <p class="text-muted-foreground mt-1 max-w-sm text-sm">Tambahkan layanan yang bisa dipilih penyewa bersama sewa venue.</p>
                <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                    Tambah layanan
                </button>
            </div>
            <table v-else class="sb-table min-w-[40rem]">
                <caption class="sr-only">
                    Daftar tambahan layanan
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Kode</th>
                        <th scope="col" class="text-right">Harga (Rp)</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="w-12"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in addons.data" :key="row.id">
                        <td>
                            <p class="text-foreground font-medium">{{ row.name }}</p>
                            <p class="text-muted-foreground mt-0.5 text-xs">{{ row.description || '—' }}</p>
                        </td>
                        <td>
                            <code class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 font-mono text-xs">{{ row.code }}</code>
                        </td>
                        <td class="text-right whitespace-nowrap tabular-nums">{{ formatRupiah(row.harga) }}</td>
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

        <TablePagination :links="addons.links" :from="addons.from" :to="addons.to" :total="addons.total" label="layanan" />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="bg-card rounded-3xl border-(--wp-hairline) p-6 sm:max-w-lg">
                <DialogHeader class="gap-1.5">
                    <DialogTitle class="text-lg font-semibold tracking-tight">{{ editingId ? 'Ubah layanan' : 'Tambah layanan' }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm">Layanan tambahan yang bisa dipilih penyewa.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label for="addon-code" class="sb-label">Kode</label>
                        <input id="addon-code" v-model="form.code" type="text" maxlength="64" placeholder="loading" class="sb-input" />
                        <p v-if="form.errors.code" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label for="addon-name" class="sb-label">Nama</label>
                        <input id="addon-name" v-model="form.name" type="text" maxlength="150" placeholder="Loading" class="sb-input" />
                        <p v-if="form.errors.name" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.name }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="addon-description" class="sb-label">Deskripsi</label>
                        <textarea id="addon-description" v-model="form.description" rows="2" class="sb-input resize-y"></textarea>
                        <p v-if="form.errors.description" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.description }}</p>
                    </div>
                    <div>
                        <label for="addon-harga" class="sb-label">Harga (Rp)</label>
                        <input id="addon-harga" v-model="form.harga" type="number" min="0" placeholder="0" class="sb-input tabular-nums" />
                        <p v-if="form.errors.harga" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.harga }}</p>
                    </div>
                    <div>
                        <label for="addon-sort" class="sb-label">Urutan</label>
                        <input id="addon-sort" v-model="form.sort_order" type="number" min="0" class="sb-input tabular-nums" />
                        <p v-if="form.errors.sort_order" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.sort_order }}</p>
                    </div>
                    <div class="flex items-center gap-2.5 sm:col-span-2">
                        <Checkbox
                            id="addon-active"
                            v-model="form.is_active"
                            class="data-[state=checked]:border-(--wp-accent) data-[state=checked]:bg-(--wp-accent) data-[state=checked]:text-(--wp-accent-contrast)"
                        />
                        <label for="addon-active" class="text-sm font-medium">Aktif</label>
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
