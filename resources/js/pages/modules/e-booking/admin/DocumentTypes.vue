<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCircleNotch, faFileLines, faPen, faPlus, faPowerOff } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

library.add(faCircleNotch, faFileLines, faPen, faPlus, faPowerOff);

type DocumentTypeRow = {
    id: number;
    code: string;
    name: string;
    is_required: boolean;
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
    documentTypes: Paginator<DocumentTypeRow>;
}>();

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm<{
    code: string;
    name: string;
    is_required: boolean;
    is_active: boolean;
    sort_order: number | string;
}>({
    code: '',
    name: '',
    is_required: false,
    is_active: true,
    sort_order: 0,
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

const openEdit = (row: DocumentTypeRow) => {
    editingId.value = row.id;
    form.clearErrors();
    form.code = row.code;
    form.name = row.name;
    form.is_required = row.is_required;
    form.is_active = row.is_active;
    form.sort_order = row.sort_order;
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
        sort_order: data.sort_order === '' ? 0 : Number(data.sort_order),
    });

    if (editingId.value) {
        form.transform(transform).put(route('e-booking.admin.document-types.update', editingId.value), options);
    } else {
        form.transform(transform).post(route('e-booking.admin.document-types.store'), options);
    }
};

const toggle = (row: DocumentTypeRow) => {
    router.post(route('e-booking.admin.document-types.toggle', row.id), {}, { preserveScroll: true });
};

const rowActions = (row: DocumentTypeRow) => [
    { label: 'Edit', icon: 'pen', onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: 'power-off',
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan dokumen ini?',
                  description: 'Dokumen tidak lagi diminta saat pengajuan.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggle(row),
    },
];
</script>

<template>
    <SeoHead title="Jenis Dokumen E-Booking" />

    <AdminLayout active="document-types">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Jenis dokumen</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">Dokumen pendukung yang dapat diunggah penyewa saat melengkapi pengajuan.</p>
            </div>
            <button type="button" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" @click="openCreate">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                Tambah dokumen
            </button>
        </div>

        <div class="sb-card mt-6 overflow-x-auto">
            <div v-if="documentTypes.data.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                <span class="wp-icon size-12" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'file-lines']" class="size-5" />
                </span>
                <h2 class="mt-4 text-base font-semibold tracking-tight">Belum ada jenis dokumen</h2>
                <p class="text-muted-foreground mt-1 max-w-sm text-sm">Tentukan dokumen apa saja yang perlu dilampirkan penyewa.</p>
                <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                    Tambah dokumen
                </button>
            </div>
            <table v-else class="sb-table min-w-[40rem]">
                <caption class="sr-only">
                    Daftar jenis dokumen
                </caption>
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Kode</th>
                        <th scope="col">Wajib</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="w-12"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in documentTypes.data" :key="row.id">
                        <td class="text-foreground font-medium">{{ row.name }}</td>
                        <td>
                            <code class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 font-mono text-xs">{{ row.code }}</code>
                        </td>
                        <td>
                            <span v-if="row.is_required" class="sb-badge sb-tone-info">Wajib</span>
                            <span v-else class="text-muted-foreground text-sm">Opsional</span>
                        </td>
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

        <TablePagination
            :links="documentTypes.links"
            :from="documentTypes.from"
            :to="documentTypes.to"
            :total="documentTypes.total"
            label="dokumen"
        />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="bg-card rounded-3xl border-(--wp-hairline) p-6 sm:max-w-lg">
                <DialogHeader class="gap-1.5">
                    <DialogTitle class="text-lg font-semibold tracking-tight">{{ editingId ? 'Ubah dokumen' : 'Tambah dokumen' }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm">Dokumen pendukung untuk pengajuan penyewa.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label for="doc-code" class="sb-label">Kode</label>
                        <input id="doc-code" v-model="form.code" type="text" maxlength="64" placeholder="dokumen" class="sb-input" />
                        <p v-if="form.errors.code" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label for="doc-name" class="sb-label">Nama</label>
                        <input id="doc-name" v-model="form.name" type="text" maxlength="150" placeholder="Dokumen pendukung" class="sb-input" />
                        <p v-if="form.errors.name" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="doc-sort" class="sb-label">Urutan</label>
                        <input id="doc-sort" v-model="form.sort_order" type="number" min="0" class="sb-input tabular-nums" />
                        <p v-if="form.errors.sort_order" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.sort_order }}</p>
                    </div>
                    <fieldset class="flex items-center gap-5 sm:pt-7">
                        <legend class="sr-only">Pengaturan dokumen</legend>
                        <label class="flex items-center gap-2.5 text-sm font-medium">
                            <Checkbox
                                v-model="form.is_required"
                                class="data-[state=checked]:border-(--wp-accent) data-[state=checked]:bg-(--wp-accent) data-[state=checked]:text-(--wp-accent-contrast)"
                            />
                            Wajib
                        </label>
                        <label class="flex items-center gap-2.5 text-sm font-medium">
                            <Checkbox
                                v-model="form.is_active"
                                class="data-[state=checked]:border-(--wp-accent) data-[state=checked]:bg-(--wp-accent) data-[state=checked]:text-(--wp-accent-contrast)"
                            />
                            Aktif
                        </label>
                    </fieldset>
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
