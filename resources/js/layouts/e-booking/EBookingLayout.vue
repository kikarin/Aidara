<script setup lang="ts">
import PublicSiteFooter from '@/components/PublicSiteFooter.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import { Skeleton } from '@/components/ui/skeleton';
import ToastContainer from '@/components/ui/toast/ToastContainer.vue';
import { useToast } from '@/components/ui/toast/useToast';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faArrowRightFromBracket, faMagnifyingGlass, faReceipt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

library.add(faArrowRightFromBracket, faMagnifyingGlass, faReceipt);

const props = defineProps<{
    active?: 'catalog' | 'admin' | 'auth' | 'history';
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
const navItems = computed(() => [
    { key: 'catalog', label: 'Cari venue', href: route('e-booking.catalog'), icon: 'magnifying-glass', show: true },
    { key: 'history', label: 'Pesanan saya', href: route('e-booking.bookings.index'), icon: 'receipt', show: Boolean(bookingAuth.value) },
]);
</script>

<template>
    <div class="welcome-page bg-background text-foreground flex min-h-dvh flex-col">
        <a href="#konten-utama" class="skip-link">Lompat ke konten utama</a>
        <div class="wp-grain" aria-hidden="true"></div>

        <header class="wp-header sticky top-0" data-scrolled="true">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <Link
                    :href="route('e-booking.catalog')"
                    class="group flex min-w-0 items-center gap-3"
                    aria-label="Si Bola, booking venue UPT Dispora"
                >
                    <SiBolaMark class="size-9 transition-transform duration-300 group-hover:-rotate-12" />
                    <span class="min-w-0 leading-tight">
                        <span class="block text-[15px] font-bold tracking-tight">Si Bola</span>
                        <span class="text-muted-foreground block truncate text-xs">Booking venue UPT Dispora</span>
                    </span>
                </Link>

                <nav class="ml-4 hidden items-center gap-1 md:flex" aria-label="Navigasi Si Bola">
                    <template v-for="item in navItems" :key="item.key">
                        <Link
                            v-if="item.show"
                            :href="item.href"
                            class="wp-nav-link text-muted-foreground hover:text-foreground rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :aria-current="active === item.key ? 'page' : undefined"
                        >
                            {{ item.label }}
                        </Link>
                    </template>
                    <Link
                        :href="route('home')"
                        class="text-muted-foreground hover:text-foreground rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                    >
                        AIDARA
                    </Link>
                </nav>

                <div class="ml-auto flex items-center gap-2">
                    <template v-if="bookingAuth">
                        <span class="hidden items-center gap-2 rounded-xl py-1 pr-3 pl-1 text-sm sm:inline-flex" :title="bookingAuth.email">
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
                    </template>
                    <template v-else>
                        <Link
                            :href="route('e-booking.login')"
                            class="rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:text-(--wp-accent)"
                            :class="active === 'auth' ? 'text-foreground' : 'text-muted-foreground'"
                        >
                            Masuk
                        </Link>
                        <Link :href="route('e-booking.register')" class="wp-btn wp-btn-primary px-4 py-2 text-sm">Buat akun</Link>
                    </template>
                </div>
            </div>

            <nav class="flex gap-2 overflow-x-auto px-4 pb-3 md:hidden" aria-label="Navigasi Si Bola (seluler)">
                <template v-for="item in navItems" :key="item.key">
                    <Link v-if="item.show" :href="item.href" class="sb-chip shrink-0" :aria-current="active === item.key ? 'page' : undefined">
                        <FontAwesomeIcon :icon="['fas', item.icon]" class="size-3" aria-hidden="true" />
                        {{ item.label }}
                    </Link>
                </template>
            </nav>

            <div v-if="isNavigating" class="sb-progress" aria-hidden="true"></div>
        </header>

        <main id="konten-utama" class="relative mx-auto w-full max-w-7xl flex-1 px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
            <div
                v-if="isNavigating"
                class="absolute inset-x-4 top-8 z-10 sm:inset-x-6 lg:inset-x-8 lg:top-12"
                aria-busy="true"
                aria-label="Memuat halaman"
            >
                <div class="sb-card p-6">
                    <Skeleton class="h-8 w-64 max-w-full rounded-lg" />
                    <Skeleton class="mt-3 h-4 w-96 max-w-full" />
                    <div class="mt-8 grid gap-4 md:grid-cols-[1.4fr_1fr]">
                        <Skeleton class="h-56 rounded-2xl" />
                        <div class="space-y-4">
                            <Skeleton class="h-26 rounded-2xl" />
                            <Skeleton class="h-26 rounded-2xl" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="transition-opacity duration-200" :class="isNavigating ? 'pointer-events-none opacity-40' : ''">
                <slot />
            </div>
        </main>

        <PublicSiteFooter />

        <ToastContainer />
    </div>
</template>
