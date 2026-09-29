<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import RowActionsMenu from '@/components/e-booking/RowActionsMenu.vue';
import TablePagination from '@/components/e-booking/TablePagination.vue';
import SeoHead from '@/components/SeoHead.vue';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import type { IconName } from '@fortawesome/fontawesome-svg-core';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRight, faBuilding, faEye, faLayerGroup, faPen, faPlus, faPowerOff, faTag } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router } from '@inertiajs/vue3';
import { h, type FunctionalComponent } from 'vue';

library.add(faArrowRight, faBuilding, faEye, faLayerGroup, faPen, faPlus, faPowerOff, faTag);

const faAction =
    (name: IconName): FunctionalComponent =>
    (_, { attrs }) =>
        h(FontAwesomeIcon, { ...attrs, icon: ['fas', name], 'aria-hidden': 'true' });
const Eye = faAction('eye');
const Pencil = faAction('pen');
const Power = faAction('power-off');

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

defineProps<{
    venues: Paginator<VenueRow>;
}>();

const toggle = (row: VenueRow) => {
    router.post(route('e-booking.admin.venues.toggle', row.id), {}, { preserveScroll: true });
};

const rowActions = (row: VenueRow) => [
    { label: 'Kelola', icon: Eye, href: route('e-booking.admin.venues.show', row.id) },
    { label: 'Edit', icon: Pencil, href: route('e-booking.admin.venues.edit', row.id) },
    {
        label: row.is_active ? 'Nonaktifkan' : 'Aktifkan',
        icon: Power,
        variant: row.is_active ? ('destructive' as const) : ('default' as const),
        confirm: row.is_active
            ? {
                  title: 'Nonaktifkan venue ini?',
                  description: 'Venue akan disembunyikan dari katalog.',
                  confirmText: 'Nonaktifkan',
                  variant: 'destructive' as const,
              }
            : undefined,
        onClick: () => toggle(row),
    },
];
</script>

<template>
    <SeoHead title="Venue E-Booking" />

    <AdminLayout active="venues">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl">
                <p class="wp-eyebrow">Katalog sewa</p>
                <h1 class="text-foreground mt-2 text-2xl font-bold tracking-tight sm:text-3xl">Venue</h1>
                <p class="text-muted-foreground mt-2 text-sm">
                    Kelola venue, area, dan tarif sewa. Nonaktifkan venue untuk menyembunyikannya dari katalog.
                </p>
            </div>
            <Link :href="route('e-booking.admin.venues.create')" class="wp-btn wp-btn-primary px-5 py-2.5 text-sm">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-4" aria-hidden="true" />
                Tambah venue
            </Link>
        </header>

        <ul v-if="venues.data.length" class="mt-8 grid gap-4 lg:grid-cols-2" role="list">
            <li v-for="row in venues.data" :key="row.id" class="sb-card flex gap-4 p-4 sm:p-5">
                <AppImage
                    v-if="row.cover_url"
                    :src="row.cover_url"
                    :alt="row.name"
                    class="size-20 shrink-0 rounded-xl object-cover sm:size-24"
                    :class="row.is_active ? '' : 'opacity-60 grayscale'"
                />
                <div v-else class="wp-icon size-20 shrink-0 rounded-xl sm:size-24" aria-hidden="true">
                    <FontAwesomeIcon :icon="['fas', 'building']" class="size-6" />
                </div>

                <div class="flex min-w-0 flex-1 flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold tracking-tight">
                                <Link
                                    :href="route('e-booking.admin.venues.show', row.id)"
                                    class="text-foreground rounded-sm hover:text-(--wp-accent) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                                >
                                    {{ row.name }}
                                </Link>
                            </h2>
                            <p class="text-muted-foreground mt-0.5 font-mono text-xs">{{ row.code }}</p>
                        </div>
                        <div class="-mt-1 -mr-2 flex shrink-0 items-center gap-1">
                            <span class="sb-badge" :class="row.is_active ? 'sb-tone-success' : 'sb-tone-neutral'">
                                {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                            <RowActionsMenu :items="rowActions(row)" />
                        </div>
                    </div>

                    <p class="text-muted-foreground mt-2 line-clamp-2 text-sm">{{ row.description || 'Belum ada deskripsi.' }}</p>

                    <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-3">
                        <dl class="text-muted-foreground flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1.5">
                                <dt class="sr-only">Jumlah area</dt>
                                <FontAwesomeIcon :icon="['fas', 'layer-group']" class="size-3.5" aria-hidden="true" />
                                <dd>
                                    <span class="text-foreground font-semibold tabular-nums">{{ row.areas_count }}</span> area
                                </dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt class="sr-only">Jumlah tarif</dt>
                                <FontAwesomeIcon :icon="['fas', 'tag']" class="size-3.5" aria-hidden="true" />
                                <dd>
                                    <span class="text-foreground font-semibold tabular-nums">{{ row.tarifs_count }}</span> tarif
                                </dd>
                            </div>
                        </dl>
                        <Link
                            :href="route('e-booking.admin.venues.show', row.id)"
                            class="wp-link-arrow inline-flex items-center gap-1.5 rounded-sm text-sm font-semibold text-(--wp-accent) hover:text-(--wp-accent-strong) focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-(--wp-accent)"
                            :aria-label="`Kelola ${row.name}`"
                        >
                            Kelola
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </li>
        </ul>

        <div v-else class="sb-card mt-8 flex flex-col items-center px-6 py-14 text-center">
            <span class="wp-icon size-12" aria-hidden="true">
                <FontAwesomeIcon :icon="['fas', 'building']" class="size-5" />
            </span>
            <h2 class="mt-4 text-base font-semibold tracking-tight">Belum ada venue</h2>
            <p class="text-muted-foreground mt-1 max-w-sm text-sm">Tambahkan venue beserta area dan harga sewanya agar muncul di katalog.</p>
            <Link :href="route('e-booking.admin.venues.create')" class="wp-btn wp-btn-primary mt-5 px-5 py-2.5 text-sm">
                <FontAwesomeIcon :icon="['fas', 'plus']" class="size-4" aria-hidden="true" />
                Tambah venue
            </Link>
        </div>

        <TablePagination :links="venues.links" :from="venues.from" :to="venues.to" :total="venues.total" label="venue" />
    </AdminLayout>
</template>
