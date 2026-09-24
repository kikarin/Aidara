<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from 'lucide-vue-next';
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

const activeKey = ref<string | null>(null);

const form = useForm<{
    title: string;
    points: string[];
}>({
    title: '',
    points: [''],
});

const startEdit = (term: TermRow) => {
    activeKey.value = term.key;
    form.clearErrors();
    form.title = term.title ?? '';
    form.points = term.points.length ? [...term.points] : [''];
};

const cancelEdit = () => {
    activeKey.value = null;
    form.reset();
    form.clearErrors();
};

const addPoint = () => {
    form.points.push('');
};

const removePoint = (index: number) => {
    form.points.splice(index, 1);
    if (form.points.length === 0) {
        form.points.push('');
    }
};

const save = () => {
    if (!activeKey.value) return;
    form.put(route('e-booking.admin.terms.update', activeKey.value), {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    });
};

const newForm = useForm<{ key: string; title: string }>({ key: '', title: '' });

const createNew = () => {
    newForm.post(route('e-booking.admin.terms.store'), {
        preserveScroll: true,
        onSuccess: () => newForm.reset(),
    });
};
</script>

<template>
    <SeoHead title="Tata Tertib E-Booking" />

    <AdminLayout active="terms">
        <h1 class="text-foreground text-2xl font-bold">Tata tertib</h1>
        <p class="text-muted-foreground mt-1 text-sm">
            Teks aturan yang dicentang penyewa sebelum mengirim pengajuan. Venue memilih key ini lewat tab Aturan.
        </p>

        <div class="mt-6 space-y-4">
            <div v-for="term in terms" :key="term.key" class="border-border rounded-xl border p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ term.title || term.key }}</p>
                        <code class="text-muted-foreground text-xs">{{ term.key }}</code>
                    </div>
                    <Button v-if="activeKey !== term.key" type="button" variant="outline" size="sm" @click="startEdit(term)">Edit</Button>
                </div>

                <template v-if="activeKey === term.key">
                    <div class="mt-4">
                        <label class="mb-1.5 block text-sm font-medium">Judul</label>
                        <Input v-model="form.title" type="text" maxlength="200" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="mt-4 space-y-2">
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

                    <div class="mt-4 flex gap-2">
                        <Button type="button" :disabled="form.processing" @click="save">
                            <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                            Simpan
                        </Button>
                        <Button type="button" variant="ghost" @click="cancelEdit">Batal</Button>
                    </div>
                </template>

                <ul v-else class="text-muted-foreground mt-3 list-disc space-y-1 pl-5 text-sm">
                    <li v-for="(point, i) in term.points" :key="i">{{ point }}</li>
                    <li v-if="term.points.length === 0" class="list-none italic">Belum ada poin.</li>
                </ul>
            </div>

            <p v-if="terms.length === 0" class="text-muted-foreground py-6 text-center text-sm">Belum ada tata tertib.</p>
        </div>

        <form class="border-border mt-8 grid max-w-xl gap-3 rounded-xl border p-4 sm:grid-cols-2" @submit.prevent="createNew">
            <h2 class="text-sm font-semibold sm:col-span-2">Tambah tata tertib baru</h2>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Key</label>
                <Input v-model="newForm.key" type="text" maxlength="96" placeholder="terms_venue_baru" />
                <InputError :message="newForm.errors.key" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Judul</label>
                <Input v-model="newForm.title" type="text" maxlength="200" placeholder="Tata Tertib ..." />
                <InputError :message="newForm.errors.title" />
            </div>
            <div class="sm:col-span-2">
                <Button type="submit" :disabled="newForm.processing">
                    <LoaderCircle v-if="newForm.processing" class="size-4 animate-spin" />
                    <Plus v-else class="size-4" />
                    Buat tata tertib
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
