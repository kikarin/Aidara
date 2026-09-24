<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Building2, Pencil, Plus } from 'lucide-vue-next';

type VenueRow = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    cover_url: string | null;
    is_active: boolean;
    sort_order: number;
    areas_count: number;
    tarifs_count: number;
};

type Paginator<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
    from: number | null;
    to: number | null;
};

const props = defineProps<{
    venues: Paginator<VenueRow>;
}>();

const toggle = (row: VenueRow) => {
    router.post(route('e-booking.admin.venues.toggle', row.id), {}, { preserveScroll: true });
};
</script>

<template>
    <SeoHead title="Venue E-Booking" />

    <AdminLayout active="venues">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-foreground text-2xl font-bold">Venue</h1>
                <p class="text-muted-foreground mt-1 text-sm">
                    Kelola venue, area, dan tarif sewa. Nonaktifkan venue untuk menyembunyikannya dari katalog.
                </p>
            </div>
            <Button as-child>
                <Link :href="route('e-booking.admin.venues.create')">
                    <Plus class="size-4" />
                    Tambah venue
                </Link>
            </Button>
        </div>

        <p class="text-muted-foreground mt-6 text-sm">
            Menampilkan {{ props.venues.from ?? 0 }}–{{ props.venues.to ?? 0 }} dari {{ props.venues.total }} venue
        </p>

        <Table class="mt-2 min-w-[720px]">
            <TableHeader>
                <TableRow>
                    <TableHead>Venue</TableHead>
                    <TableHead>Kode</TableHead>
                    <TableHead>Area</TableHead>
                    <TableHead>Tarif</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead></TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                <TableRow v-for="row in venues.data" :key="row.id">
                    <TableCell>
                        <div class="flex items-center gap-3">
                            <AppImage v-if="row.cover_url" :src="row.cover_url" :alt="row.name" class="size-10 rounded-lg object-cover" />
                            <div v-else class="size-10 rounded-lg bg-slate-100"></div>
                            <div>
                                <p class="font-medium">{{ row.name }}</p>
                                <p class="text-muted-foreground text-xs">{{ row.description || '—' }}</p>
                            </div>
                        </div>
                    </TableCell>
                    <TableCell>
                        <code class="text-xs">{{ row.code }}</code>
                    </TableCell>
                    <TableCell>{{ row.areas_count }}</TableCell>
                    <TableCell>{{ row.tarifs_count }}</TableCell>
                    <TableCell>
                        <Badge :variant="row.is_active ? 'default' : 'secondary'">
                            {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </TableCell>
                    <TableCell>
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            <Link :href="route('e-booking.admin.venues.show', row.id)" class="text-xs font-semibold text-sky-700 hover:underline">
                                Kelola
                            </Link>
                            <Link
                                :href="route('e-booking.admin.venues.edit', row.id)"
                                class="inline-flex items-center gap-1 text-xs font-semibold text-slate-600 hover:underline"
                            >
                                <Pencil class="size-3" />
                                Edit
                            </Link>
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
                <TableRow v-if="venues.data.length === 0">
                    <TableCell colspan="6">
                        <div class="flex flex-col items-center gap-3 py-10 text-center">
                            <Building2 class="text-muted-foreground size-9" />
                            <div>
                                <p class="text-sm font-medium">Belum ada venue</p>
                                <p class="text-muted-foreground text-xs">Tambahkan venue, area, dan harga sewanya.</p>
                            </div>
                            <Button as-child size="sm">
                                <Link :href="route('e-booking.admin.venues.create')">
                                    <Plus class="size-4" />
                                    Tambah venue
                                </Link>
                            </Button>
                        </div>
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <div v-if="venues.links?.length > 3" class="mt-4 flex flex-wrap gap-2">
            <template v-for="(link, i) in venues.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="border-border rounded-md border px-3 py-1 text-xs"
                    :class="link.active ? 'bg-muted font-semibold' : ''"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </div>
    </AdminLayout>
</template>
