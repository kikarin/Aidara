<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type Venue = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    is_active: boolean;
    sort_order: number;
};

const props = defineProps<{
    venue: Venue | null;
}>();

const isEdit = computed(() => props.venue !== null);

const form = useForm<{
    code: string;
    name: string;
    description: string;
    sort_order: number | string;
    is_active: boolean;
    cover: File | null;
}>({
    code: props.venue?.code ?? '',
    name: props.venue?.name ?? '',
    description: props.venue?.description ?? '',
    sort_order: props.venue?.sort_order ?? 0,
    is_active: props.venue?.is_active ?? true,
    cover: null,
});

const coverPreview = ref<string | null>(props.venue?.cover_url ?? null);

const onCoverChange = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    form.cover = files && files.length > 0 ? files[0] : null;
    if (form.cover) {
        coverPreview.value = URL.createObjectURL(form.cover);
    }
};

const submit = () => {
    const options = { forceFormData: true };

    if (props.venue) {
        form.put(route('e-booking.admin.venues.update', props.venue.id), options);
    } else {
        form.post(route('e-booking.admin.venues.store'), options);
    }
};
</script>

<template>
    <SeoHead :title="isEdit ? `Ubah Venue — E-Booking` : 'Tambah Venue — E-Booking'" />

    <AdminLayout active="venues">
        <Link :href="route('e-booking.admin.venues.index')" class="text-muted-foreground inline-flex items-center gap-1 text-sm hover:underline">
            <ArrowLeft class="size-4" />
            Kembali ke daftar venue
        </Link>

        <h1 class="text-foreground mt-3 text-2xl font-bold">{{ isEdit ? 'Ubah venue' : 'Tambah venue' }}</h1>
        <p class="text-muted-foreground mt-1 text-sm">
            {{ isEdit ? 'Perbarui informasi venue.' : 'Buat venue baru, lalu kelola area dan tarifnya.' }}
        </p>

        <form class="border-border mt-6 grid max-w-2xl gap-4 rounded-xl border p-5 sm:grid-cols-2" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-sm font-medium">Kode</label>
                <Input v-model="form.code" type="text" maxlength="64" placeholder="pakansari" />
                <InputError :message="form.errors.code" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Nama</label>
                <Input v-model="form.name" type="text" maxlength="150" placeholder="Stadion Pakansari" />
                <InputError :message="form.errors.name" />
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium">Deskripsi</label>
                <textarea
                    v-model="form.description"
                    rows="3"
                    class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm"
                    placeholder="Deskripsi singkat (opsional)"
                ></textarea>
                <InputError :message="form.errors.description" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Urutan</label>
                <Input v-model="form.sort_order" type="number" min="0" />
                <InputError :message="form.errors.sort_order" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Cover (opsional)</label>
                <input
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,.svg"
                    class="border-border bg-background w-full rounded-lg border px-3 py-1.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm"
                    @change="onCoverChange"
                />
                <InputError :message="form.errors.cover" />
            </div>
            <div v-if="coverPreview" class="sm:col-span-2">
                <p class="text-muted-foreground mb-1.5 text-xs">Pratinjau cover</p>
                <AppImage :src="coverPreview" alt="Pratinjau cover" class="h-32 w-full max-w-xs rounded-xl object-cover" />
            </div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input id="venue-active" v-model="form.is_active" type="checkbox" class="size-4" />
                <label for="venue-active" class="text-sm font-medium">Aktif</label>
            </div>
            <div class="flex gap-2 sm:col-span-2">
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    {{ isEdit ? 'Simpan perubahan' : 'Simpan venue' }}
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="route('e-booking.admin.venues.index')">Batal</Link>
                </Button>
            </div>
        </form>
    </AdminLayout>
</template>
