<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CalendarOff, ClipboardList, LayoutDashboard, LogOut, Settings2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

defineProps<{
    active?: 'dashboard' | 'bookings' | 'settings' | 'closures';
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);
const flashSuccess = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const flashError = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

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

const navItems = [
    { key: 'dashboard' as const, label: 'Ringkasan', href: () => route('e-booking.admin.dashboard'), icon: LayoutDashboard },
    { key: 'bookings' as const, label: 'Pengajuan', href: () => route('e-booking.admin.bookings.index'), icon: ClipboardList },
    { key: 'closures' as const, label: 'Blok jadwal', href: () => route('e-booking.admin.closures.index'), icon: CalendarOff },
    { key: 'settings' as const, label: 'Pengaturan', href: () => route('e-booking.admin.settings'), icon: Settings2 },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="sticky top-0 z-50 px-4 pt-4 sm:px-6">
            <div class="mx-auto max-w-6xl">
                <div
                    class="rounded-[24px] border border-white/80 bg-white/95 px-4 py-3 shadow-[0_12px_40px_rgba(15,23,42,0.08)] backdrop-blur-md sm:px-5"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <Link :href="route('e-booking.admin.dashboard')" class="flex items-center gap-3">
                            <AppImage src="/Logo.svg" alt="Aidara" class="size-9 object-contain" :lazy="false" />
                            <div>
                                <p class="text-sm font-bold tracking-tight text-slate-900">Pengelolaan Sewa</p>
                                <p class="text-xs text-slate-500">UPT · halaman khusus pengelola</p>
                            </div>
                        </Link>

                        <div v-if="bookingAuth" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <nav class="flex flex-wrap items-center gap-1.5 text-sm" aria-label="Navigasi pengelola">
                                <Link
                                    v-for="item in navItems"
                                    :key="item.key"
                                    :href="item.href()"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-2 transition-colors"
                                    :class="
                                        active === item.key
                                            ? 'bg-sky-100 font-semibold text-sky-800'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    <component :is="item.icon" class="size-3.5 opacity-70" />
                                    {{ item.label }}
                                </Link>
                            </nav>

                            <div class="flex items-center gap-2 border-t border-slate-100 pt-3 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-3">
                                <span
                                    class="hidden max-w-[9rem] truncate rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 sm:inline"
                                    :title="bookingAuth.email"
                                >
                                    {{ bookingAuth.name }}
                                </span>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
                                    :disabled="logoutForm.processing"
                                    @click="logout"
                                >
                                    <LogOut class="size-3.5" />
                                    Keluar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar saat navigasi Inertia -->
                    <div
                        v-if="isNavigating"
                        class="mt-3 h-1 overflow-hidden rounded-full bg-slate-100"
                        aria-hidden="true"
                    >
                        <div class="h-full w-1/3 animate-pulse rounded-full bg-sky-500" />
                    </div>
                </div>
            </div>
        </header>

        <div class="px-4 pt-4 sm:px-6">
            <div v-if="flashSuccess" class="mx-auto max-w-6xl">
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                    {{ flashSuccess }}
                </div>
            </div>
            <div v-if="flashError" class="mx-auto mt-3 max-w-6xl">
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 shadow-sm">
                    {{ flashError }}
                </div>
            </div>
        </div>

        <main class="relative mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 lg:py-8">
            <!-- Overlay skeleton saat pindah halaman admin -->
            <div
                v-if="isNavigating"
                class="absolute inset-x-4 top-6 z-10 space-y-4 rounded-[24px] bg-slate-50/80 p-2 backdrop-blur-[1px] sm:inset-x-6"
                aria-busy="true"
                aria-label="Memuat halaman"
            >
                <div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
                    <Skeleton class="h-6 w-48" />
                    <Skeleton class="mt-3 h-4 w-72 max-w-full" />
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <Skeleton v-for="n in 4" :key="n" class="h-24 rounded-2xl" />
                    </div>
                    <div class="mt-4 space-y-3">
                        <Skeleton v-for="n in 3" :key="`row-${n}`" class="h-16 rounded-2xl" />
                    </div>
                </div>
            </div>

            <div :class="isNavigating ? 'pointer-events-none opacity-40' : ''">
                <slot />
            </div>
        </main>
    </div>
</template>
