<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import type { PublicNavSection, PublicPageKey } from '@/types/public';
import type { WorldCupSettings } from '@/types/worldcup';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faBars, faXmark } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

library.add(faBars, faXmark);

const props = withDefaults(
    defineProps<{
        current?: PublicPageKey;
        sections?: PublicNavSection[];
        activeSection?: string | null;
    }>(),
    {
        current: 'home',
        sections: undefined,
        activeSection: null,
    },
);

const page = usePage<{
    worldcup?: WorldCupSettings;
    auth?: { user?: unknown };
}>();

const isHome = computed(() => props.current === 'home');
const isLoggedIn = computed(() => Boolean(page.props.auth?.user));
const worldCupEnabled = computed(() => Boolean(page.props.worldcup?.enabled));
const worldCupOnLanding = computed(() => Boolean(page.props.worldcup?.enabled && page.props.worldcup?.show_on_landing));

const defaultSections: PublicNavSection[] = [
    { id: 'si-bola', label: 'Si Bola' },
    { id: 'fitur', label: 'Fitur' },
    { id: 'faq', label: 'FAQ' },
];

const navSections = computed(() => props.sections ?? defaultSections);

const sectionHref = (id: string) => (isHome.value ? `#${id}` : `${route('home')}#${id}`);

const showWorldCupLink = computed(() => worldCupEnabled.value && !(isHome.value && worldCupOnLanding.value));

const isScrolled = ref(false);
const mobileMenuOpen = ref(false);

const onScroll = () => {
    isScrolled.value = window.scrollY > 12;
};

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <header class="wp-header fixed inset-x-0 top-0" :data-scrolled="isScrolled || mobileMenuOpen">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-4 lg:px-8">
            <component
                :is="isHome ? 'a' : Link"
                :href="isHome ? '#beranda' : route('home')"
                class="flex items-center gap-3"
                aria-label="AIDARA — kembali ke beranda"
            >
                <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-8 object-contain" :lazy="false" />
                <AppImage src="/Logo.svg" alt="Logo AIDARA" class="size-8 object-contain" :lazy="false" />
                <span class="hidden leading-none sm:block">
                    <span class="block text-[0.95rem] font-bold tracking-[-0.01em]">AIDARA</span>
                    <span class="text-muted-foreground mt-1 block text-xs font-medium">Dispora Kabupaten Bogor</span>
                </span>
            </component>

            <nav class="hidden items-center gap-6 text-sm font-medium lg:flex" aria-label="Navigasi utama">
                <template v-for="item in navSections" :key="item.id">
                    <a v-if="isHome" :href="sectionHref(item.id)" class="wp-nav-link" :aria-current="activeSection === item.id ? 'true' : undefined">
                        {{ item.label }}
                    </a>
                    <Link v-else :href="sectionHref(item.id)" class="wp-nav-link">{{ item.label }}</Link>
                </template>
                <Link :href="route('event.public.index')" class="wp-nav-link" :aria-current="current === 'event' ? 'page' : undefined"> Event </Link>
                <Link
                    v-if="showWorldCupLink"
                    :href="route('worldcup.index')"
                    class="wp-nav-link"
                    :aria-current="current === 'worldcup' ? 'page' : undefined"
                >
                    Piala Dunia
                </Link>
            </nav>

            <div class="flex items-center gap-2">
                <Link :href="route('e-booking.catalog')" class="wp-btn wp-btn-quiet hidden py-1.5 pr-4 pl-1.5 text-sm xl:inline-flex">
                    <SiBolaMark class="size-7" />
                    Booking venue
                </Link>
                <Link v-if="isLoggedIn" :href="route('dashboard')" class="wp-btn wp-btn-primary px-5 py-2 text-sm">Dasbor</Link>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-muted-foreground hover:text-foreground hidden px-3 py-2 text-sm font-medium transition-colors sm:inline-block"
                    >
                        Masuk
                    </Link>
                    <Link :href="route('register')" class="wp-btn wp-btn-primary px-5 py-2 text-sm">Daftar</Link>
                </template>
                <button
                    type="button"
                    class="wp-btn wp-btn-quiet size-10 justify-center lg:hidden"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="menu-mobile"
                    :aria-label="mobileMenuOpen ? 'Tutup menu' : 'Buka menu'"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                >
                    <FontAwesomeIcon :icon="['fas', mobileMenuOpen ? 'xmark' : 'bars']" class="size-4" />
                </button>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-2 opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="-translate-y-2 opacity-0"
        >
            <nav v-show="mobileMenuOpen" id="menu-mobile" class="px-6 pb-6 lg:hidden" aria-label="Navigasi seluler">
                <Link :href="route('e-booking.catalog')" class="wp-surface mb-2 flex items-center gap-3 rounded-2xl p-3 text-sm font-semibold">
                    <SiBolaMark class="size-9" />
                    <span>
                        Booking venue di Si Bola
                        <span class="text-muted-foreground block text-xs font-normal">Lapangan & fasilitas UPT Dispora</span>
                    </span>
                </Link>
                <ul class="divide-y divide-(--wp-hairline) text-base font-medium">
                    <li v-if="!isHome">
                        <Link :href="route('home')" class="block py-3">Beranda</Link>
                    </li>
                    <li v-for="item in navSections" :key="item.id">
                        <a v-if="isHome" :href="sectionHref(item.id)" class="block py-3" @click="mobileMenuOpen = false">
                            {{ item.label }}
                        </a>
                        <Link v-else :href="sectionHref(item.id)" class="block py-3">{{ item.label }}</Link>
                    </li>
                    <li>
                        <Link
                            :href="route('event.public.index')"
                            class="block py-3"
                            :class="{ 'text-(--wp-accent)': current === 'event' }"
                            :aria-current="current === 'event' ? 'page' : undefined"
                        >
                            Event
                        </Link>
                    </li>
                    <li v-if="showWorldCupLink">
                        <Link :href="route('worldcup.index')" class="block py-3">Piala Dunia</Link>
                    </li>
                    <li v-if="!isLoggedIn">
                        <Link :href="route('login')" class="block py-3 text-(--wp-accent)">Masuk ke akun</Link>
                    </li>
                </ul>
            </nav>
        </Transition>
    </header>
</template>
