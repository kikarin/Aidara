<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle, Pencil, Plus, ScrollText, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

type TermRow = {
    key: string;
    title: string | null;
    points: string[];
    description: string | null;
};

defineProps<{
    terms: TermRow[];
}>();

const dialogOpen = ref(false);
const editingKey = ref<string | null>(null);

const form = useForm<{
    slug: string;
    title: string;
    points: string[];
}>({
    slug: '',
    title: '',
    points: [''],
});

const resetForm = () => {
    editingKey.value = null;
    form.reset();
    form.clearErrors();
};

const openCreate = () => {
    resetForm();
    dialogOpen.value = true;
};

const openEdit = (term: TermRow) => {
    editingKey.value = term.key;
    form.clearErrors();
    form.slug = term.key.replace(/^terms_/, '');
    form.title = term.title ?? '';
    form.points = term.points.length ? [...term.points] : [''];
    dialogOpen.value = true;
};

const addPoint = () => form.points.push('');
const removePoint = (index: number) => {
    form.points.splice(index, 1);
    if (form.points.length === 0) form.points.push('');
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            resetForm();
            dialogOpen.value = false;
        },
    };

    if (editingKey.value) {
        form.transform((data) => ({ title: data.title, points: data.points })).put(route('e-booking.admin.terms.update', editingKey.value), options);

        return;
    }

    form.transform((data) => ({
        key: `terms_${data.slug}`.replace(/[^A-Za-z0-9_]/g, '_'),
        title: data.title,
        points: data.points,
    })).post(route('e-booking.admin.terms.store'), options);
};
</script>

<template>
    <SeoHead title="Tata Tertib E-Booking" />

    <AdminLayout active="terms">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Tata tertib</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Teks aturan yang dicentang penyewa. Pilih tata tertib ini di form venue agar tampil di halaman sewa.
                </p>
            </div>
            <Button @click="openCreate">
                <Plus class="size-4" />
                Tambah tata tertib
            </Button>
        </div>

        <div class="mt-6 space-y-4">
            <div v-for="term in terms" :key="term.key" class="border-border rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ term.title || term.key }}</p>
                        <code class="text-muted-foreground text-xs">{{ term.key }}</code>
                    </div>
                    <Button type="button" variant="outline" size="sm" @click="openEdit(term)">
                        <Pencil class="size-3.5" />
                        Edit
                    </Button>
                </div>

                <ul class="text-muted-foreground mt-3 list-disc space-y-1 pl-5 text-sm">
                    <li v-for="(point, i) in term.points" :key="i">{{ point }}</li>
                    <li v-if="term.points.length === 0" class="list-none italic">Belum ada poin.</li>
                </ul>
            </div>

            <div
                v-if="terms.length === 0"
                class="text-muted-foreground flex flex-col items-center gap-2 rounded-2xl border border-dashed py-12 text-center"
            >
                <ScrollText class="size-9" />
                <p class="text-sm">Belum ada tata tertib. Klik "Tambah tata tertib" untuk mulai.</p>
            </div>
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ editingKey ? 'Ubah tata tertib' : 'Tambah tata tertib' }}</DialogTitle>
                    <DialogDescription>Aturan yang akan disetujui penyewa saat mengajukan booking.</DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submit">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Kunci</label>
                            <div class="border-border bg-background flex items-center rounded-lg border pl-3">
                                <span class="text-muted-foreground text-sm">terms_</span>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    maxlength="88"
                                    :disabled="editingKey !== null"
                                    placeholder="tennis"
                                    class="h-10 w-full rounded-r-lg bg-transparent px-1 text-sm outline-none disabled:opacity-60"
                                />
                            </div>
                            <InputError :message="form.errors.key" />
                            <InputError :message="form.errors.slug" />
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium">Judul</label>
                            <Input v-model="form.title" type="text" maxlength="200" placeholder="Tata Tertib ..." />
                            <InputError :message="form.errors.title" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium">Poin aturan</label>
                        <div v-for="(_, index) in form.points" :key="index" class="flex items-start gap-2">
                            <textarea
                                v-model="form.points[index]"
                                rows="1"
                                maxlength="500"
                                class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                            ></textarea>
                            <button type="button" class="mt-2 text-red-600 hover:opacity-70" title="Hapus poin" @click="removePoint(index)">
                                <Trash2 class="size-4" />
                            </button>
                        </div>
                        <InputError :message="form.errors.points" />
                        <button type="button" class="text-xs font-semibold text-sky-700 hover:underline" @click="addPoint">+ Tambah poin</button>
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
