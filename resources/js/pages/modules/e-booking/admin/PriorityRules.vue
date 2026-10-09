<script setup lang="ts">
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCircleNotch, faListOl, faPen, faPlus, faPowerOff } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

library.add(faCircleNotch, faListOl, faPen, faPlus, faPowerOff);

type RuleRow = {
    id: number;
    code: string;
    name: string;
    priority_order: number;
    description: string | null;
    is_active: boolean;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};

defineProps<{
    rules: Paginator<RuleRow>;
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

const rowActions = (row: RuleRow) => [
    { label: 'Edit', icon: 'pen', onClick: () => openEdit(row) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: 'power-off',
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan prioritas ini?',
                  description: 'Aturan prioritas tidak lagi dipakai saat konflik jadwal.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggle(row),
    },
];
</script>

<template>
    <SeoHead title="Prioritas Konflik E-Booking" />

    <AdminLayout active="priority-rules">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Prioritas konflik</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                    Urutan prioritas saat jadwal bentrok. Angka yang lebih kecil berarti prioritas lebih tinggi.
                </p>
            </div>
            <button type="button" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" @click="openCreate">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                Tambah prioritas
            </button>
        </div>

        <section class="sb-card mt-6" aria-labelledby="priority-list-title">
            <h2 id="priority-list-title" class="sr-only">Daftar prioritas</h2>
            <div v-if="rules.data.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                <span class="wp-icon size-12" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'list-ol']" class="size-5" />
                </span>
                <h3 class="mt-4 text-base font-semibold tracking-tight">Belum ada prioritas konflik</h3>
                <p class="text-muted-foreground mt-1 max-w-sm text-sm">Susun urutan kelompok penyewa yang didahulukan saat jadwal bentrok.</p>
                <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                    Tambah prioritas
                </button>
            </div>
            <ol v-else class="divide-y divide-(--wp-hairline)">
                <li
                    v-for="row in rules.data"
                    :key="row.id"
                    class="hover:bg-muted/40 flex items-start gap-4 px-4 py-4 transition-colors first:rounded-t-3xl last:rounded-b-3xl sm:px-5"
                    :class="row.is_active ? '' : 'opacity-70'"
                >
                    <span class="wp-icon size-8 shrink-0 text-sm font-bold tabular-nums">
                        <span class="sr-only">Urutan</span>
                        {{ row.priority_order }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                            <p class="text-foreground font-medium">{{ row.name }}</p>
                            <code class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 font-mono text-xs">{{ row.code }}</code>
                        </div>
                        <p class="text-muted-foreground mt-1 text-sm">{{ row.description || 'Tanpa deskripsi.' }}</p>
                    </div>
                    <span class="sb-badge mt-1 shrink-0" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <div class="shrink-0">
                        <RowActionsMenu :items="rowActions(row)" />
                    </div>
                </li>
            </ol>
        </section>

        <TablePagination :links="rules.links" :from="rules.from" :to="rules.to" :total="rules.total" label="prioritas" />

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="bg-card rounded-3xl border-(--wp-hairline) p-6 sm:max-w-lg">
                <DialogHeader class="gap-1.5">
                    <DialogTitle class="text-lg font-semibold tracking-tight">{{ editingId ? 'Ubah prioritas' : 'Tambah prioritas' }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm">Urutan prioritas saat jadwal bentrok.</DialogDescription>
                </DialogHeader>

                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label for="priority-code" class="sb-label">Kode</label>
                        <input id="priority-code" v-model="form.code" type="text" maxlength="64" placeholder="umum_komersial" class="sb-input" />
                        <p v-if="form.errors.code" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label for="priority-name" class="sb-label">Nama</label>
                        <input id="priority-name" v-model="form.name" type="text" maxlength="150" placeholder="Umum / komersial" class="sb-input" />
                        <p v-if="form.errors.name" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label for="priority-order" class="sb-label">Urutan prioritas</label>
                        <input id="priority-order" v-model="form.priority_order" type="number" min="1" class="sb-input tabular-nums" />
                        <p v-if="form.errors.priority_order" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.priority_order }}</p>
                    </div>
                    <div class="flex items-center gap-2.5 sm:pt-7">
                        <Checkbox
                            id="priority-active"
                            v-model="form.is_active"
                            class="data-[state=checked]:border-(--wp-accent) data-[state=checked]:bg-(--wp-accent) data-[state=checked]:text-(--wp-accent-contrast)"
                        />
                        <label for="priority-active" class="text-sm font-medium">Aktif</label>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="priority-description" class="sb-label">Deskripsi</label>
                        <textarea id="priority-description" v-model="form.description" rows="2" class="sb-input resize-y"></textarea>
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
