<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faCircleNotch, faPen, faPlus, faScroll, faTrashCan } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

library.add(faCircleNotch, faPen, faPlus, faScroll, faTrashCan);

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

// Server validates the transformed payload, so some error keys aren't form fields.
const serverError = (key: string) => (form.errors as Record<string, string | undefined>)[key];
</script>

<template>
    <SeoHead title="Tata Tertib E-Booking" />

    <AdminLayout active="terms">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="min-w-0">
                <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">Tata tertib</h1>
                <p class="text-muted-foreground mt-1.5 max-w-2xl text-sm">
                    Aturan yang dicentang penyewa. Pilih tata tertib di form venue agar tampil di halaman sewa.
                </p>
            </div>
            <button type="button" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm" @click="openCreate">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                Tambah tata tertib
            </button>
        </div>

        <section class="sb-card mt-6" aria-labelledby="terms-list-title">
            <h2 id="terms-list-title" class="sr-only">Daftar tata tertib</h2>
            <div v-if="terms.length === 0" class="flex flex-col items-center px-6 py-14 text-center">
                <span class="wp-icon size-12" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'scroll']" class="size-5" />
                </span>
                <h3 class="mt-4 text-base font-semibold tracking-tight">Belum ada tata tertib</h3>
                <p class="text-muted-foreground mt-1 max-w-sm text-sm">Tulis aturan pemakaian venue yang perlu disetujui penyewa.</p>
                <button type="button" class="wp-btn wp-btn-quiet mt-5 px-4 py-2 text-sm" @click="openCreate">
                    <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3.5" aria-hidden="true" />
                    Tambah tata tertib
                </button>
            </div>
            <ul v-else class="divide-y divide-(--wp-hairline)">
                <li
                    v-for="term in terms"
                    :key="term.key"
                    class="hover:bg-muted/40 px-5 py-5 transition-colors first:rounded-t-3xl last:rounded-b-3xl sm:px-6"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold tracking-tight">{{ term.title || term.key }}</h3>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                                <code class="bg-muted text-muted-foreground rounded-md px-1.5 py-0.5 font-mono text-xs">{{ term.key }}</code>
                                <span class="text-muted-foreground tabular-nums">{{ term.points.length }} poin</span>
                            </p>
                        </div>
                        <button type="button" class="wp-btn wp-btn-quiet px-4 py-2 text-sm" @click="openEdit(term)">
                            <FontAwesomeIcon :icon="['fas', 'pen']" class="size-3" aria-hidden="true" />
                            Edit
                            <span class="sr-only">{{ term.title || term.key }}</span>
                        </button>
                    </div>

                    <ol
                        v-if="term.points.length"
                        class="text-muted-foreground mt-4 max-w-3xl list-decimal space-y-1.5 pl-5 text-sm marker:tabular-nums"
                    >
                        <li v-for="(point, i) in term.points" :key="i">{{ point }}</li>
                    </ol>
                    <p v-else class="text-muted-foreground mt-4 text-sm">Belum ada poin.</p>
                </li>
            </ul>
        </section>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="bg-card max-h-[90dvh] overflow-y-auto rounded-3xl border-(--wp-hairline) p-6 sm:max-w-xl">
                <DialogHeader class="gap-1.5">
                    <DialogTitle class="text-lg font-semibold tracking-tight">{{
                        editingKey ? 'Ubah tata tertib' : 'Tambah tata tertib'
                    }}</DialogTitle>
                    <DialogDescription class="text-muted-foreground text-sm"
                        >Aturan yang akan disetujui penyewa saat mengajukan booking.</DialogDescription
                    >
                </DialogHeader>

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="term-slug" class="sb-label">Kunci</label>
                            <div
                                class="bg-background flex items-center rounded-xl pl-3.5 ring-1 ring-(--wp-hairline) transition-shadow ring-inset focus-within:ring-2 focus-within:ring-(--wp-accent)"
                            >
                                <span class="text-muted-foreground font-mono text-sm" aria-hidden="true">terms_</span>
                                <input
                                    id="term-slug"
                                    v-model="form.slug"
                                    type="text"
                                    maxlength="88"
                                    :disabled="editingKey !== null"
                                    placeholder="tennis"
                                    aria-describedby="term-slug-hint"
                                    class="h-10 w-full rounded-r-xl bg-transparent px-1 font-mono text-sm outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                />
                            </div>
                            <p id="term-slug-hint" class="sb-hint">Diawali <code class="font-mono">terms_</code> otomatis.</p>
                            <p v-if="serverError('key')" class="text-destructive mt-1.5 text-xs font-medium">{{ serverError('key') }}</p>
                            <p v-if="form.errors.slug" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.slug }}</p>
                        </div>
                        <div>
                            <label for="term-title" class="sb-label">Judul</label>
                            <input id="term-title" v-model="form.title" type="text" maxlength="200" placeholder="Tata tertib ..." class="sb-input" />
                            <p v-if="form.errors.title" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.title }}</p>
                        </div>
                    </div>

                    <fieldset class="space-y-2">
                        <legend class="sb-label">Poin aturan</legend>
                        <div v-for="(_, index) in form.points" :key="index" class="flex items-start gap-2">
                            <span class="text-muted-foreground w-5 shrink-0 pt-2.5 text-right text-sm tabular-nums" aria-hidden="true"
                                >{{ index + 1 }}.</span
                            >
                            <textarea
                                v-model="form.points[index]"
                                rows="1"
                                maxlength="500"
                                :aria-label="`Poin ${index + 1}`"
                                class="sb-input resize-y"
                            ></textarea>
                            <button
                                type="button"
                                class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive focus-visible:ring-ring mt-1 inline-flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                :aria-label="`Hapus poin ${index + 1}`"
                                @click="removePoint(index)"
                            >
                                <FontAwesomeIcon :icon="['fas', 'trash-can']" class="size-3.5" aria-hidden="true" />
                            </button>
                        </div>
                        <p v-if="form.errors.points" class="text-destructive mt-1.5 text-xs font-medium">{{ form.errors.points }}</p>
                        <button type="button" class="wp-btn wp-btn-quiet mt-1 px-3.5 py-1.5 text-sm" @click="addPoint">
                            <FontAwesomeIcon :icon="['fas', 'plus']" class="size-3" aria-hidden="true" />
                            Tambah poin
                        </button>
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
