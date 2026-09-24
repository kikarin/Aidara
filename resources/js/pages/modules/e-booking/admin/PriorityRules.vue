<script setup lang="ts">
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
import { ListOrdered, LoaderCircle, Plus } from 'lucide-vue-next';
import { ref } from 'vue';

type RuleRow = {
    id: number;
    code: string;
    name: string;
    priority_order: number;
    description: string | null;
    is_active: boolean;
};

defineProps<{
    rules: RuleRow[];
}>();

const dialogOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm<{
    code: string;
    name: string;
    priority_order: number | string;
    description: string;
    is_active: boolean;
}>({
    code: '',
    name: '',
    priority_order: 1,
    description: '',
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

const openEdit = (row: RuleRow) => {
    editingId.value = row.id;
    form.clearErrors();
    form.code = row.code;
    form.name = row.name;
    form.priority_order = row.priority_order;
    form.description = row.description ?? '';
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
        priority_order: data.priority_order === '' ? 1 : Number(data.priority_order),
    });

    if (editingId.value) {
        form.transform(transform).put(route('e-booking.admin.priority-rules.update', editingId.value), options);
    } else {
        form.transform(transform).post(route('e-booking.admin.priority-rules.store'), options);
    }
};

const toggle = (row: RuleRow) => {
    router.post(route('e-booking.admin.priority-rules.toggle', row.id), {}, { preserveScroll: true });
};
</script>

<template>
    <SeoHead title="Prioritas Konflik E-Booking" />

    <AdminLayout active="priority-rules">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Prioritas konflik</h1>
                <p class="text-muted-foreground mt-1 text-sm">Urutan prioritas saat jadwal bentrok. Angka lebih kecil = prioritas lebih tinggi.</p>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tambah prioritas
            </Button>
        </div>

        <Table class="mt-6 min-w-[640px]">
            <TableHeader>
                <TableRow>
                    <TableHead>Urutan</TableHead>
                    <TableHead>Nama</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in rules" :key="row.id">
                    <TableCell>
                        <span class="bg-muted inline-flex size-7 items-center justify-center rounded-full text-xs font-bold">{{
                            row.priority_order
                        }}</span>
                    </TableCell>
                    <TableCell>
                        <p class="font-medium">{{ row.name }}</p>
                        <p class="text-muted-foreground text-xs">{{ row.description || '—' }}</p>
                    </TableCell>
                    <TableCell>
                        <code class="text-xs">{{ row.code }}</code>
                    </TableCell>
                    <TableCell>
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">{{ row.is_active ? 'Aktif' : 'Nonaktif' }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">
                        <div class="flex justify-end gap-2">
                            <button type="button" class="text-xs font-semibold text-slate-600 hover:underline" @click="openEdit(row)">Edit</button>
                            <button
                                type="button"
                                class="text-xs font-semibold hover:underline"
                                :class="row.is_active ? 'text-red-600' : 'text-emerald-700'"
                                @click="toggle(row)"
                            >
                                {{ row.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                            </button>
                        </div>
                    </TableCell>
                </TableRow>
                <TableRow v-if="rules.length === 0">
                    <TableCell colspan="5">
                        <div class="flex flex-col items-center gap-2 py-8 text-center">
                            <ListOrdered class="text-muted-foreground size-8" />
                            <p class="text-muted-foreground text-sm">Belum ada prioritas konflik.</p>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingId ? 'Ubah prioritas' : 'Tambah prioritas' }}</DialogTitle>
                    <DialogDescription>Urutan prioritas saat jadwal bentrok.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Kode</label>
                        <Input v-model="form.code" type="text" maxlength="64" placeholder="umum_komersial" />
                        <InputError :message="form.errors.code" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Nama</label>
                        <Input v-model="form.name" type="text" maxlength="150" placeholder="Umum / komersial" />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium">Urutan prioritas</label>
                        <Input v-model="form.priority_order" type="number" min="1" />
                        <InputError :message="form.errors.priority_order" />
                    </div>
                    <div class="flex items-center gap-2 pt-6">
                        <Checkbox id="priority-active" v-model="form.is_active" />
                        <label for="priority-active" class="text-sm font-medium">Aktif</label>
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
