<script setup lang="ts">
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import { Skeleton } from '@/components/ui/skeleton';
import ToastContainer from '@/components/ui/toast/ToastContainer.vue';
import { useToast } from '@/components/ui/toast/useToast';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowDownWideShort,
    faArrowRightFromBracket,
    faBoxOpen,
    faBuilding,
    faCalendarXmark,
    faChartPie,
    faClipboardList,
    faFileLines,
    faScroll,
    faSliders,
    faTableCellsLarge,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

library.add(
    faArrowDownWideShort,
    faArrowRightFromBracket,
    faBoxOpen,
    faBuilding,
    faCalendarXmark,
    faChartPie,
    faClipboardList,
    faFileLines,
    faScroll,
    faSliders,
    faTableCellsLarge,
);

defineProps<{
    active?: 'dashboard' | 'bookings' | 'settings' | 'closures' | 'venues' | 'addons' | 'document-types' | 'facilities' | 'terms' | 'priority-rules';
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);
const initial = computed(() => bookingAuth.value?.name.trim().charAt(0).toUpperCase() ?? '');
const { toast } = useToast();

watch(
    () => page.props.flash,
    (flash) => {
        const messages = flash as { success?: string; error?: string } | undefined;
        const message = messages?.success ?? messages?.error;
        if (!message) return;
        toast({ title: message, variant: messages?.error ? 'destructive' : 'success' });
    },
    { immediate: true },
);

const isNavigating = ref(false);
let removeStart: (() => void) | undefined;
let removeFinish: (() => void) | undefined;
let removeError: (() => void) | undefined;

onMounted(() => {
    removeStart = router.on('start', (event) => {
        const visit = event.detail.visit;
        // Partial / deferred reload: biarkan halaman pakai skeleton sendiri
        if (visit.only.length > 0 || visit.except.length > 0 || visit.async) {
            return;
        }
        isNavigating.value = true;
    });
    removeFinish = router.on('finish', (event) => {
        const visit = event.detail.visit;
        if (visit.only.length > 0 || visit.except.length > 0 || visit.async) {
            return;
        }
        isNavigating.value = false;
    });
    removeError = router.on('error', () => {
        isNavigating.value = false;
    });
});

onBeforeUnmount(() => {
    removeStart?.();
    removeFinish?.();
    removeError?.();
});

const logoutForm = useForm({});
const logout = () => logoutForm.post(route('e-booking.admin.logout'));

const navGroups = [
    [
        { key: 'dashboard' as const, label: 'Ringkasan', href: () => route('e-booking.admin.dashboard'), icon: 'chart-pie' },
        { key: 'bookings' as const, label: 'Pengajuan', href: () => route('e-booking.admin.bookings.index'), icon: 'clipboard-list' },
        { key: 'closures' as const, label: 'Blok jadwal', href: () => route('e-booking.admin.closures.index'), icon: 'calendar-xmark' },
    ],
    [
        { key: 'venues' as const, label: 'Venue', href: () => route('e-booking.admin.venues.index'), icon: 'building' },
        { key: 'addons' as const, label: 'Layanan', href: () => route('e-booking.admin.addons.index'), icon: 'box-open' },
        { key: 'facilities' as const, label: 'Fasilitas', href: () => route('e-booking.admin.facilities.index'), icon: 'table-cells-large' },
        { key: 'document-types' as const, label: 'Dokumen', href: () => route('e-booking.admin.document-types.index'), icon: 'file-lines' },
        { key: 'terms' as const, label: 'Tata tertib', href: () => route('e-booking.admin.terms.index'), icon: 'scroll' },
        {
            key: 'priority-rules' as const,
            label: 'Prioritas',
            href: () => route('e-booking.admin.priority-rules.index'),
            icon: 'arrow-down-wide-short',
        },
        { key: 'settings' as const, label: 'Pengaturan', href: () => route('e-booking.admin.settings'), icon: 'sliders' },
    ],
];
</script>

<template>
    <div class="welcome-page bg-background text-foreground flex min-h-dvh flex-col">
        <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>

        <header class="wp-header sticky top-0" data-scrolled="true">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('e-booking.admin.dashboard')"
                    class="group flex min-w-0 items-center gap-3"
                    aria-label="Si Bola, halaman pengelola"
                >
                    <SiBolaMark class="size-9 transition-transform duration-300 group-hover:-rotate-12" />
                    <span class="min-w-0 leading-tight">
                        <span class="flex items-center gap-2 text-[15px] font-bold tracking-tight">
                            Si Bola
                            <span class="rounded-md bg-(--wp-accent-soft) px-1.5 py-0.5 text-[11px] font-semibold text-(--wp-accent-strong)"
                                >Pengelola</span
                            >
                        </span>
                        <span class="text-muted-foreground block truncate text-xs">UPT Dispora Kabupaten Bogor</span>
                    </span>
                </Link>

                <div v-if="bookingAuth" class="ml-auto flex items-center gap-2">
                    <span class="hidden items-center gap-2 py-1 pr-2 text-sm sm:inline-flex" :title="bookingAuth.email">
                        <span
                            class="grid size-7 place-items-center rounded-lg bg-(--wp-accent-soft) text-xs font-bold text-(--wp-accent-strong)"
                            aria-hidden="true"
                        >
                            {{ initial }}
                        </span>
                        <span class="max-w-[10rem] truncate font-medium">{{ bookingAuth.name }}</span>
                    </span>
                    <button type="button" class="wp-btn wp-btn-quiet px-3.5 py-2 text-sm" :disabled="logoutForm.processing" @click="logout">
                        <FontAwesomeIcon :icon="['fas', 'arrow-right-from-bracket']" class="size-3.5" aria-hidden="true" />
                        <span>Keluar</span>
                    </button>
                </div>
            </div>

            <nav v-if="bookingAuth" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" aria-label="Navigasi pengelola">
                <div class="-mx-1 flex items-center gap-1 overflow-x-auto pb-2.5">
                    <template v-for="(group, index) in navGroups" :key="index">
                        <span v-if="index > 0" class="mx-2 h-5 w-px shrink-0 bg-(--wp-hairline)" aria-hidden="true"></span>
                        <Link
                            v-for="item in group"
                            :key="item.key"
                            :href="item.href()"
                            class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                            :class="
                                active === item.key
                                    ? 'bg-(--wp-accent-soft) text-(--wp-accent-strong)'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            "
                            :aria-current="active === item.key ? 'page' : undefined"
                        >
                            <FontAwesomeIcon
                                :icon="['fas', item.icon]"
                                class="size-3.5"
                                :class="active === item.key ? '' : 'opacity-60'"
                                aria-hidden="true"
                            />
                            {{ item.label }}
                        </Link>
                    </template>
                </div>
            </nav>

            <div v-if="isNavigating" class="sb-progress" aria-hidden="true"></div>
        </header>

        <main id="konten-utama" class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                v-if="isNavigating"
                class="absolute inset-x-4 top-8 z-10 sm:inset-x-6 lg:inset-x-8 lg:top-10"
                aria-busy="true"
                aria-label="Memuat halaman"
            >
                <div class="sb-card p-6">
                    <Skeleton class="h-7 w-52 rounded-lg" />
                    <Skeleton class="mt-3 h-4 w-80 max-w-full" />
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Skeleton v-for="n in 4" :key="n" class="h-24 rounded-2xl" />
                    </div>
                    <div class="mt-4 space-y-3">
                        <Skeleton v-for="n in 3" :key="`row-${n}`" class="h-14 rounded-2xl" />
                    </div>
                </div>
            </div>

            <div class="transition-opacity duration-200" :class="isNavigating ? 'pointer-events-none opacity-40' : ''">
                <slot />
            </div>
        </main>

        <footer class="text-muted-foreground border-t border-(--wp-hairline) px-4 py-5 text-xs sm:px-6 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2">
                <span>Si Bola · layanan booking venue UPT Dispora Kabupaten Bogor</span>
                <Link :href="route('e-booking.catalog')" class="transition-colors hover:text-(--wp-accent)">Lihat halaman penyewa</Link>
            </div>
        </footer>

        <ToastContainer />
    </div>
</template>
