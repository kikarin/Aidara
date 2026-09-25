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
import { LoaderCircle, Package, Pencil, Plus, Power } from 'lucide-vue-next';
import { ref } from 'vue';

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
    { label: 'Edit', icon: Pencil, onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        onClick: () => toggle(row),
    },
];

const formatRupiah = (value: number | null) => (value === null || value === undefined ? '—' : new Intl.NumberFormat('id-ID').format(value));
</script>

<template>
    <SeoHead title="Tambahan Layanan E-Booking" />

    <AdminLayout active="addons">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Tambahan layanan</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Layanan tambahan (mis. loading/closing) yang bisa dipilih penyewa saat mengajukan booking.
                </p>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tambah layanan
            </Button>
        </div>

        <Table class="mt-6 min-w-[640px]">
            <TableHeader>
                <TableRow>
                    <TableHead>Nama</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Harga</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in addons.data" :key="row.id">
                    <TableCell>
                        <p class="font-medium">{{ row.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ row.description || '—' }}</p>
                    </TableCell>
                    <TableCell>
                        <code class="text-xs">{{ row.code }}</code>
                    </TableCell>
                    <TableCell class="whitespace-nowrap">{{ formatRupiah(row.harga) }}</TableCell>
                    <TableCell>
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">
                        <RowActionsMenu :items="rowActions(row)" />
                    </TableCell>
                </TableRow>
                <TableRow v-if="addons.data.length === 0">
                    <TableCell colspan="5">
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <Package class="text-muted-foreground size-8" />
                            <p class="text-muted-foreground text-sm">Belum ada layanan. Klik "Tambah layanan" untuk mulai.</p>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination :links="addons.links" :from="addons.from" :to="addons.to" :total="addons.total" label="layanan" />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Ubah layanan' : 'Tambah layanan' }}</DialogTitle>
                    <DialogDescription>Layanan tambahan yang bisa dipilih penyewa.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="form.code" type="text" maxlength="64" placeholder="loading" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama</label>
                        <Input v-model="form.name" type="text" maxlength="150" placeholder="Loading" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium">Deskripsi</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                        ></textarea>
                        <InputError :message="form.errors.description" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Harga</label>
                        <Input v-model="form.harga" type="number" min="0" placeholder="0" />
                        <InputError :message="form.errors.harga" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan</label>
                        <Input v-model="form.sort_order" type="number" min="0" />
                        <InputError :message="form.errors.sort_order" />
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="addon-active" v-model="form.is_active" />
                        <label for="addon-active" class="text-sm font-medium">Aktif</label>
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
