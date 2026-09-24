<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SeoHead from '@/components/SeoHead.vue';
import SimpleSelect from '@/components/ui/select/SimpleSelect.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, watch } from 'vue';

type VenueOpt = { id: number; code: string; name: string };
type AreaOpt = { id: number; venue_id: number; code: string; name: string };
type ClosureRow = {
    id: number;
    venue_id: number;
    venue_name: string | null;
    area_id: number | null;
    area_name: string | null;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    reason: string | null;
    is_active: boolean;
};

const props = defineProps<{
    closures: { data: ClosureRow[]; links: { url: string | null; label: string; active: boolean }[] };
    venues: VenueOpt[];
    areas: AreaOpt[];
    filters: { venue_id: string; include_inactive: boolean };
}>();

const filterVenueId = computed({
    get: () => props.filters.venue_id,
    set: (value: string) => {
        router.get(
            route('e-booking.admin.closures.index'),
            {
                venue_id: value || undefined,
                include_inactive: props.filters.include_inactive ? 1 : undefined,
            },
            { preserveState: true, replace: true },
        );
    },
});

const form = useForm({
    venue_id: props.filters.venue_id || (props.venues[0] ? String(props.venues[0].id) : ''),
    area_id: '',
    starts_at: '',
    ends_at: '',
    reason: '',
});

const filteredAreas = computed(() => {
    const vid = Number(form.venue_id);
    if (!vid) return props.areas;
    return props.areas.filter((a) => a.venue_id === vid);
});

const venueOptions = computed(() => props.venues.map((v) => ({ value: String(v.id), label: v.name })));
const areaOptions = computed(() => [
    { value: 'all', label: 'Seluruh venue' },
    ...filteredAreas.value.map((a) => ({ value: String(a.id), label: a.name })),
]);
const areaSelectValue = computed({
    get: () => form.area_id || 'all',
    set: (val: string | number) => {
        form.area_id = val === 'all' ? '' : String(val);
    },
});
const filterVenueOptions = computed(() => [
    { value: 'all', label: 'Semua venue' },
    ...props.venues.map((v) => ({ value: String(v.id), label: v.name })),
]);
const filterVenueSelect = computed({
    get: () => filterVenueId.value || 'all',
    set: (val: string | number) => {
        filterVenueId.value = val === 'all' ? '' : String(val);
    },
});

watch(
    () => form.venue_id,
    () => {
        form.area_id = '';
    },
);

const submit = () => {
    form.transform((data) => ({
        ...data,
        venue_id: Number(data.venue_id),
        area_id: data.area_id ? Number(data.area_id) : null,
    })).post(route('e-booking.admin.closures.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('starts_at', 'ends_at', 'reason', 'area_id');
        },
    });
};

const remove = (id: number) => {
    if (!confirm('Hapus blok jadwal ini?')) return;
    router.delete(route('e-booking.admin.closures.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <SeoHead title="Blok Jadwal E-Booking" />

    <AdminLayout active="closures">
        <h1 class="text-foreground text-2xl font-bold">Blok jadwal</h1>
        <p class="text-muted-foreground mt-1 text-sm">
            Tutup slot tanggal/jam per venue atau area — slot tertutup tampil merah di ketersediaan.
        </p>

        <form class="border-border mt-6 grid max-w-2xl gap-3 rounded-xl border p-4 sm:grid-cols-2" @submit.prevent="submit">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium">Venue</label>
                <SimpleSelect
                    v-model="form.venue_id"
                    :options="venueOptions"
                    placeholder="Pilih venue"
                    required
                    trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                />
                <InputError :message="form.errors.venue_id" />
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium">Area (opsional — kosong = seluruh venue)</label>
                <SimpleSelect
                    v-model="areaSelectValue"
                    :options="areaOptions"
                    placeholder="Seluruh venue"
                    trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Mulai</label>
                <input v-model="form.starts_at" type="datetime-local" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" required />
                <InputError :message="form.errors.starts_at" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium">Selesai</label>
                <input v-model="form.ends_at" type="datetime-local" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" required />
                <InputError :message="form.errors.ends_at" />
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium">Alasan</label>
                <input v-model="form.reason" type="text" maxlength="255" class="border-border bg-background w-full rounded-lg border px-3 py-2 text-sm" placeholder="Libur nasional / maintenance…" />
            </div>
            <div class="sm:col-span-2">
                <button
                    type="submit"
                    class="bg-[var(--brand-green,#2e7d32)] inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
                    :disabled="form.processing"
                >
                    <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                    Tambah blok
                </button>
            </div>
        </form>

        <div class="mt-8 flex flex-wrap items-end gap-3">
            <div class="min-w-[220px]">
                <label class="mb-1.5 block text-sm font-medium">Filter venue</label>
                <SimpleSelect
                    v-model="filterVenueSelect"
                    :options="filterVenueOptions"
                    placeholder="Semua venue"
                    trigger-class="h-10 w-full rounded-lg border-border bg-background px-3 text-sm shadow-none"
                />
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-border text-muted-foreground border-b">
                    <tr>
                        <th class="py-2 pr-3 font-medium">Venue / area</th>
                        <th class="py-2 pr-3 font-medium">Mulai</th>
                        <th class="py-2 pr-3 font-medium">Selesai</th>
                        <th class="py-2 pr-3 font-medium">Alasan</th>
                        <th class="py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in closures.data" :key="row.id" class="border-border/60 border-b">
                        <td class="py-3 pr-3">
                            <p class="font-medium">{{ row.venue_name }}</p>
                            <p class="text-muted-foreground text-xs">{{ row.area_name || 'Seluruh venue' }}</p>
                        </td>
                        <td class="py-3 pr-3 whitespace-nowrap">{{ row.starts_at_label }}</td>
                        <td class="py-3 pr-3 whitespace-nowrap">{{ row.ends_at_label }}</td>
                        <td class="text-muted-foreground py-3 pr-3">{{ row.reason || '—' }}</td>
                        <td class="py-3 text-right">
                            <button type="button" class="text-red-600 text-xs font-semibold hover:underline" @click="remove(row.id)">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    <tr v-if="closures.data.length === 0">
                        <td colspan="5" class="text-muted-foreground py-8 text-center">Belum ada blok jadwal.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="closures.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
            <template v-for="(link, i) in closures.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="border-border rounded-md border px-3 py-1 text-xs"
                    :class="link.active ? 'bg-muted font-semibold' : ''"
                    v-html="link.label"
                />
            </template>
        </div>
    </AdminLayout>
</template>
