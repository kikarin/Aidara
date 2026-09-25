<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import { Skeleton } from '@/components/ui/skeleton';
import ToastContainer from '@/components/ui/toast/ToastContainer.vue';
import { useToast } from '@/components/ui/toast/useToast';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    active?: 'catalog' | 'admin' | 'auth' | 'history';
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);
const { toast } = useToast();

watch(
    () => page.props.flash,
    (flash) => {
        const messages = flash as { success?: string; error?: string } | undefined;
        const message = messages?.success ?? messages?.error;
        if (!message) return;
        if (!messages?.success && props.active === 'catalog') return;
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
const logout = () => logoutForm.post(route('e-booking.logout'));
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>

        <header class="sticky top-0 z-50 px-4 pt-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div
                    class="rounded-[28px] border border-white/70 bg-white/90 px-4 py-4 shadow-[0_12px_40px_rgba(15,23,42,0.08)] backdrop-blur-md sm:px-6"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <Link :href="route('e-booking.catalog')" class="flex items-center gap-3" aria-label="E-Booking fasilitas olahraga">
                            <div class="flex items-center gap-2">
                                <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" :lazy="false" />
                                <span class="h-8 w-px bg-slate-200" aria-hidden="true"></span>
                                <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-9 object-contain" :lazy="false" />
                            </div>
                            <div>
                                <p class="text-sm font-bold tracking-tight text-slate-900">Sewa Fasilitas Olahraga</p>
                                <p class="text-xs text-slate-500">Cari tempat, cek jadwal, lalu kirim pengajuan dengan mudah.</p>
                            </div>
                        </Link>

                        <div class="flex flex-col gap-3 lg:items-end">
                            <nav class="flex flex-wrap items-center gap-2 text-sm" aria-label="Navigasi E-Booking">
                                <Link
                                    :href="route('e-booking.catalog')"
                                    class="rounded-full px-4 py-2 transition-colors"
                                    :class="
                                        active === 'catalog'
                                            ? 'bg-sky-100 font-semibold text-sky-800'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    Cari tempat
                                </Link>
                                <Link
                                    v-if="bookingAuth"
                                    :href="route('e-booking.bookings.index')"
                                    class="rounded-full px-4 py-2 transition-colors"
                                    :class="
                                        active === 'history'
                                            ? 'bg-sky-100 font-semibold text-sky-800'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                    "
                                >
                                    Pesanan saya
                                </Link>
                                <template v-if="bookingAuth">
                                    <span class="rounded-full bg-slate-100 px-4 py-2 text-slate-600">
                                        {{ bookingAuth.name }}
                                    </span>
                                    <button
                                        type="button"
                                        class="rounded-full bg-slate-900 px-4 py-2 font-semibold text-white transition hover:bg-slate-700"
                                        :disabled="logoutForm.processing"
                                        @click="logout"
                                    >
                                        Keluar
                                    </button>
                                </template>
                                <template v-else>
                                    <Link
                                        :href="route('e-booking.login')"
                                        class="rounded-full px-4 py-2 transition-colors"
                                        :class="
                                            active === 'auth'
                                                ? 'bg-sky-100 font-semibold text-sky-800'
                                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
                                        "
                                    >
                                        Masuk
                                    </Link>
                                    <Link
                                        :href="route('e-booking.register')"
                                        class="rounded-full bg-[var(--brand-green,#2e7d32)] px-4 py-2 font-semibold text-white shadow-sm transition hover:opacity-95"
                                    >
                                        Buat akun
                                    </Link>
                                </template>
                            </nav>

                            <div class="flex flex-wrap gap-2 text-xs text-slate-500">
                                <span class="rounded-full bg-slate-100 px-3 py-1">Tanpa perlu datang dulu</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">Cek jadwal lebih cepat</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1">Status pengajuan bisa dipantau</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="isNavigating" class="mt-3 h-1 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                        <div class="h-full w-1/3 animate-pulse rounded-full bg-sky-500" />
                    </div>
                </div>
            </div>
        </header>

        <main id="konten-utama" class="relative mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <div
                v-if="isNavigating"
                class="absolute inset-x-4 top-8 z-10 space-y-4 sm:inset-x-6 lg:inset-x-8"
                aria-busy="true"
                aria-label="Memuat halaman"
            >
                <div class="rounded-[28px] border border-slate-200 bg-white/95 p-6 shadow-sm backdrop-blur-sm">
                    <Skeleton class="h-7 w-56" />
                    <Skeleton class="mt-3 h-4 w-80 max-w-full" />
                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <Skeleton v-for="n in 3" :key="n" class="h-48 rounded-[24px]" />
                    </div>
                </div>
            </div>

            <div :class="isNavigating ? 'pointer-events-none opacity-40' : ''">
                <slot />
            </div>
        </main>

        <footer class="border-t border-slate-200 bg-white/80 px-4 py-6 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">Layanan pemesanan fasilitas olahraga UPT Dispora Kabupaten Bogor.</div>
        </footer>

        <ToastContainer />
    </div>
</template>
