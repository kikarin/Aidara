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
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { LayoutGrid, LoaderCircle, Pencil, Plus, Power } from 'lucide-vue-next';
import { ref } from 'vue';

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
    { label: 'Edit', icon: Pencil, onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        onClick: () => toggle(row),
    },
];
</script>

<template>
    <SeoHead title="Fasilitas E-Booking" />

    <AdminLayout active="facilities">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Fasilitas</h1>
                <p class="text-muted-foreground mt-1 text-sm">Daftar fasilitas umum (parkir, toilet, mushola, dll) yang tersedia di venue.</p>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tambah fasilitas
            </Button>
        </div>

        <Table class="mt-6 min-w-[640px]">
            <TableHeader>
                <TableRow>
                    <TableHead>Nama</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Ikon</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in facilities.data" :key="row.id">
                    <TableCell>
                        <p class="font-medium">{{ row.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ row.description || '—' }}</p>
                    </TableCell>
                    <TableCell>
                        <code class="text-xs">{{ row.code }}</code>
                    </TableCell>
                    <TableCell class="text-muted-foreground text-xs">{{ iconLabel(row.icon) }}</TableCell>
                    <TableCell>
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">
                        <RowActionsMenu :items="rowActions(row)" />
                    </TableCell>
                </TableRow>
                <TableRow v-if="facilities.data.length === 0">
                    <TableCell colspan="5">
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <LayoutGrid class="text-muted-foreground size-8" />
                            <p class="text-muted-foreground text-sm">Belum ada fasilitas. Klik "Tambah fasilitas" untuk mulai.</p>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <TablePagination :links="facilities.links" :from="facilities.from" :to="facilities.to" :total="facilities.total" label="fasilitas" />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Ubah fasilitas' : 'Tambah fasilitas' }}</DialogTitle>
                    <DialogDescription>Fasilitas umum yang tersedia di venue.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama</label>
                        <Input v-model="form.name" type="text" maxlength="150" placeholder="Toilet" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="form.code" type="text" maxlength="64" placeholder="toilet" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Ikon</label>
                        <SimpleSelect v-model="form.icon" :options="iconOptions" placeholder="Tanpa ikon" />
                        <InputError :message="form.errors.icon" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan</label>
                        <Input v-model="form.sort_order" type="number" min="0" />
                        <InputError :message="form.errors.sort_order" />
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
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="facility-active" v-model="form.is_active" />
                        <label for="facility-active" class="text-sm font-medium">Aktif</label>
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
