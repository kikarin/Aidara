<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { FileText, LoaderCircle, Pencil, Plus, Power } from 'lucide-vue-next';
import { ref } from 'vue';

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
    { label: 'Edit', icon: Pencil, onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
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
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Jenis dokumen</h1>
                <p class="text-muted-foreground mt-1 text-sm">Daftar dokumen pendukung yang dapat diunggah penyewa saat melengkapi pengajuan.</p>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tambah dokumen
            </Button>
        </div>

        <Table class="mt-6 min-w-[640px]">
            <TableHeader>
                <TableRow>
                    <TableHead>Nama</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Wajib</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in documentTypes.data" :key="row.id">
                    <TableCell class="font-medium">{{ row.name }}</TableCell>
                    <TableCell>
                        <code class="text-xs">{{ row.code }}</code>
                    </TableCell>
                    <TableCell>{{ row.is_required ? 'Ya' : '—' }}</TableCell>
                    <TableCell>
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">
                        <RowActionsMenu :items="rowActions(row)" />
                    </TableCell>
                </TableRow>
                <TableRow v-if="documentTypes.data.length === 0">
                    <TableCell colspan="5">
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <FileText class="text-muted-foreground size-8" />
                            <p class="text-muted-foreground text-sm">Belum ada jenis dokumen. Klik "Tambah dokumen" untuk mulai.</p>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination
            :links="documentTypes.links"
            :from="documentTypes.from"
            :to="documentTypes.to"
            :total="documentTypes.total"
            label="dokumen"
        />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Ubah dokumen' : 'Tambah dokumen' }}</DialogTitle>
                    <DialogDescription>Dokumen pendukung untuk pengajuan penyewa.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="form.code" type="text" maxlength="64" placeholder="dokumen" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama</label>
                        <Input v-model="form.name" type="text" maxlength="150" placeholder="Dokumen pendukung" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan</label>
                        <Input v-model="form.sort_order" type="number" min="0" />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <Checkbox v-model="form.is_required" />
                            Wajib
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <Checkbox v-model="form.is_active" />
                            Aktif
                        </label>
                    </div>
                </form>

                <DialogFooter>
                    <Button variant="ghost" type="button" @click="dialogOpen = false">Batal</Button>
                    <Button type="button" :disabled="form.processing" @click="submit">
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AdminLayout>
</template>
