<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import type { WorldCupSettings } from '@/types/worldcup';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faEnvelope, faLocationDot, faPhone } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

library.add(faEnvelope, faLocationDot, faPhone);

const props = withDefaults(defineProps<{ onHome?: boolean }>(), { onHome: false });

const page = usePage<{ worldcup?: WorldCupSettings }>();

const showWorldCup = computed(() => Boolean(page.props.worldcup?.enabled));

const faqHref = computed(() => (props.onHome ? '#faq' : `${route('home')}#faq`));

const socialLinks = [
    {
        name: 'Facebook',
        href: 'https://www.facebook.com/people/Dispora-Sportif/100090561789480/?mibextid=wwXIfr',
        icon: 'M22 12a10 10 0 1 0-11.5 9.87v-6.99H7.9v-2.88h2.6V9.84c0-2.57 1.53-3.99 3.87-3.99 1.12 0 2.3.2 2.3.2v2.53h-1.3c-1.28 0-1.68.8-1.68 1.62v1.95h2.86l-.46 2.88h-2.4v6.99A10 10 0 0 0 22 12Z',
    },
    {
        name: 'Instagram',
        href: 'https://www.instagram.com/disporasportif_kab.bogor',
        icon: 'M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 0 1 12 7.5Zm0 2A2.5 2.5 0 1 0 14.5 12 2.5 2.5 0 0 0 12 9.5ZM17.8 6a1 1 0 1 1-1 1 1 1 0 0 1 1-1Z',
    },
    {
        name: 'YouTube',
        href: 'https://www.youtube.com/@disporasportifkabupatenbog98',
        icon: 'M23.5 6.2a3 3 0 0 0-2.1-2.1C19.6 3.5 12 3.5 12 3.5s-7.6 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.8.6 9.4.6 9.4.6s7.6 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.7 15.5V8.5L16 12Z',
    },
];
</script>

<template>
    <footer class="mt-auto border-t border-(--wp-hairline)" aria-label="Footer situs">
        <div class="mx-auto max-w-7xl px-6 pt-16 pb-10 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-12 lg:gap-8">
                <div class="lg:col-span-5">
                    <div class="flex items-center gap-3">
                        <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" />
                        <AppImage src="/Logo.svg" alt="Logo AIDARA" class="size-9 object-contain" />
                        <div class="leading-none">
                            <p class="font-bold tracking-[-0.01em]">AIDARA</p>
                            <p class="text-muted-foreground mt-1 text-xs">Aplikasi Informasi Data Olahraga</p>
                        </div>
                    </div>
                    <p class="text-muted-foreground mt-6 max-w-[40ch] text-sm leading-relaxed">
                        Sistem informasi olahraga resmi Dinas Pemuda dan Olahraga Kabupaten Bogor.
                    </p>
                    <div class="mt-6 flex items-center gap-2">
                        <a
                            v-for="social in socialLinks"
                            :key="social.name"
                            :href="social.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="`${social.name} Dispora Kabupaten Bogor`"
                            class="wp-btn wp-btn-quiet size-10 justify-center rounded-xl"
                        >
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path :d="social.icon" />
                            </svg>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4 lg:col-start-7">
                    <h2 class="text-sm font-semibold">Kontak</h2>
                    <ul class="text-muted-foreground mt-5 space-y-4 text-sm">
                        <li class="flex items-start gap-3">
                            <FontAwesomeIcon :icon="['fas', 'location-dot']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                            <span>Jalan Tegar Beriman, Cibinong, Kabupaten Bogor</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <FontAwesomeIcon :icon="['fas', 'phone']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                            <a href="tel:+622517503524" class="hover:text-foreground tabular-nums transition-colors">(0251) 7503524</a>
                        </li>
                        <li class="flex items-start gap-3">
                            <FontAwesomeIcon :icon="['fas', 'envelope']" class="mt-0.5 size-4 shrink-0 text-(--wp-accent)" aria-hidden="true" />
                            <a href="mailto:dispora@bogorkab.go.id" class="hover:text-foreground transition-colors">dispora@bogorkab.go.id</a>
                        </li>
                    </ul>
                </div>

                <div class="lg:col-span-2">
                    <h2 class="text-sm font-semibold">Jelajahi</h2>
                    <ul class="text-muted-foreground mt-5 space-y-3 text-sm">
                        <li v-if="!onHome">
                            <Link :href="route('home')" class="hover:text-foreground transition-colors">Beranda</Link>
                        </li>
                        <li>
                            <Link :href="route('event.public.index')" class="hover:text-foreground transition-colors">Event & kegiatan</Link>
                        </li>
                        <li>
                            <Link :href="route('e-booking.catalog')" class="hover:text-foreground transition-colors">Si Bola · booking venue</Link>
                        </li>
                        <li v-if="showWorldCup">
                            <Link :href="route('worldcup.index')" class="hover:text-foreground transition-colors">Piala Dunia 2026</Link>
                        </li>
                        <li>
                            <a v-if="onHome" :href="faqHref" class="hover:text-foreground transition-colors">FAQ</a>
                            <Link v-else :href="faqHref" class="hover:text-foreground transition-colors">FAQ</Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="text-muted-foreground mt-16 flex flex-col gap-4 border-t border-(--wp-hairline) pt-8 text-xs sm:flex-row sm:items-center sm:justify-between"
            >
                <p>&copy; {{ new Date().getFullYear() }} Dinas Pemuda dan Olahraga (DISPORA) Kabupaten Bogor.</p>
                <ul class="flex flex-wrap items-center gap-x-5 gap-y-2">
                    <li>
                        <Link :href="route('legal.show', { slug: 'terms' })" class="hover:text-foreground transition-colors">Syarat & ketentuan</Link>
                    </li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="hover:text-foreground transition-colors"
                            >Kebijakan privasi</Link
                        >
                    </li>
                    <li>
                        <Link :href="route('legal.show', { slug: 'pdp' })" class="hover:text-foreground transition-colors">
                            Perlindungan data pribadi
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </footer>
</template>
