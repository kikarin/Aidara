<script setup lang="ts">
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faCalendarCheck,
    faChartBar,
    faClipboardList,
    faDatabase,
    faEnvelope,
    faMapMarkerAlt,
    faPhone,
    faShieldAlt,
    faStethoscope,
    faTrophy,
    faUser,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import AppImage from '@/components/AppImage.vue';
import WorldCupPreviewSection from '@/components/worldcup/WorldCupPreviewSection.vue';
import SeoHead from '@/components/SeoHead.vue';
import type { WorldCupPreview, WorldCupSettings } from '@/types/worldcup';
import { SEO_DEFAULT_KEYWORDS, SEO_HOME_DESCRIPTION, SEO_WORLDCUP_HOME_SNIPPET, SEO_WORLDCUP_KEYWORDS } from '@/lib/seo';
import {
    buildFaqSchema,
    buildOrganizationSchema,
    buildWebPageSchema,
    buildWebSiteSchema,
} from '@/lib/schema';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

library.add(
    faUser,
    faClipboardList,
    faStethoscope,
    faChartBar,
    faDatabase,
    faUsers,
    faShieldAlt,
    faTrophy,
    faCalendarCheck,
    faMapMarkerAlt,
    faPhone,
    faEnvelope,
);

const keunggulan = [
    {
        icon: 'database',
        title: 'Data Terpusat',
        text: 'Semua data cabang olahraga, atlet, pelatih, dan tenaga pendukung tersimpan dalam satu sistem terintegrasi.',
    },
    {
        icon: 'users',
        title: 'Kolaborasi Tim',
        text: 'Admin, pelatih, dan tenaga pendukung bekerja bersama melalui portal yang aman dan terstruktur per cabang olahraga.',
    },
    {
        icon: 'shield-alt',
        title: 'Akuntabel & Transparan',
        text: 'Laporan statistik dan riwayat pemeriksaan kesehatan mendukung pengambilan keputusan berbasis data.',
    },
];

const caraKerja = [
    {
        step: '01',
        title: 'Daftar & Verifikasi',
        text: 'Buat akun dan lengkapi proses verifikasi untuk mengakses portal AIDARA.',
    },
    {
        step: '02',
        title: 'Kelola Cabang Olahraga',
        text: 'Atur data cabang olahraga beserta atlet, pelatih, dan tenaga pendukung di dalam sistem.',
    },
    {
        step: '03',
        title: 'Jalankan Program',
        text: 'Catat program latihan, pemeriksaan kesehatan, dan kegiatan event secara terstruktur.',
    },
    {
        step: '04',
        title: 'Pantau Laporan',
        text: 'Akses statistik dan rekap data untuk evaluasi kinerja olahraga Kabupaten Bogor.',
    },
];

const fitur = [
    {
        icon: 'user',
        title: 'Manajemen Peserta',
        text: 'Kelola data atlet, pelatih, dan tenaga pendukung dalam satu dashboard.',
    },
    {
        icon: 'clipboard-list',
        title: 'Program Latihan',
        text: 'Rencanakan dan pantau program latihan beserta absensi peserta.',
    },
    {
        icon: 'stethoscope',
        title: 'Pemeriksaan Kesehatan',
        text: 'Catat riwayat pemeriksaan dan parameter kesehatan peserta olahraga.',
    },
    {
        icon: 'chart-bar',
        title: 'Laporan Statistik',
        text: 'Lihat rekap data dan statistik untuk mendukung evaluasi kebijakan olahraga.',
    },
    {
        icon: 'trophy',
        title: 'Prestasi & Turnamen',
        text: 'Dokumentasikan prestasi atlet dan kelola data turnamen olahraga.',
    },
    {
        icon: 'database',
        title: 'Event Publik',
        text: 'Informasi kegiatan olahraga dapat diakses masyarakat melalui halaman event.',
        link: 'event',
    },
    {
        icon: 'calendar-check',
        title: 'E-Booking Fasilitas',
        text: 'Sewa lapangan dan fasilitas olahraga UPT secara online — jadwal, approval, dan pembayaran.',
        link: 'ebooking',
    },
];

const faq = [
    {
        question: 'Apa itu AIDARA?',
        answer: 'AIDARA (Aplikasi Informasi Data Olahraga) adalah sistem terpadu milik Dispora Kabupaten Bogor untuk pengelolaan data cabang olahraga, atlet, pelatih, dan tenaga pendukung.',
    },
    {
        question: 'Siapa yang dapat menggunakan AIDARA?',
        answer: 'AIDARA digunakan oleh admin Dispora, pelatih, tenaga pendukung, dan pihak terkait yang telah terdaftar dan terverifikasi. Masyarakat dapat mengakses informasi event publik dan E-Booking fasilitas tanpa masuk dashboard Dispora.',
    },
    {
        question: 'Bagaimana cara mendaftar?',
        answer: 'Klik tombol Daftar di halaman ini, isi formulir pendaftaran, lalu ikuti proses verifikasi akun. Setelah disetujui, Anda dapat masuk ke dashboard.',
    },
    {
        question: 'Apakah data saya aman?',
        answer: 'AIDARA menerapkan kontrol akses berbasis peran, enkripsi HTTPS, dan kebijakan privasi sesuai standar perlindungan data pribadi.',
    },
    {
        question: 'Di mana saya bisa melihat event olahraga?',
        answer: 'Daftar event dan kegiatan olahraga tersedia di halaman Event Publik yang dapat diakses tanpa perlu login.',
    },
    {
        question: 'Apakah AIDARA punya info Piala Dunia 2026?',
        answer: 'Ya. AIDARA menyajikan jadwal pertandingan, skor live, klasemen grup, dan bracket knockout FIFA World Cup 2026 (USA, Canada & Mexico). Cek section Piala Dunia di beranda atau buka halaman lengkapnya.',
    },
];

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

defineProps<{
    worldcupPreview?: WorldCupPreview;
}>();

const page = usePage<{
    worldcup?: WorldCupSettings;
    auth?: { user?: unknown };
}>();

const homeUrl = computed(() => route('home', undefined, true));
const siteBaseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const showWorldCupNav = () => Boolean(page.props.worldcup?.enabled);

const showWorldCupSection = () => Boolean(page.props.worldcup?.enabled && page.props.worldcup?.show_on_landing);

const homeDescription = computed(() =>
    showWorldCupSection()
        ? `${SEO_HOME_DESCRIPTION} ${SEO_WORLDCUP_HOME_SNIPPET}`
        : SEO_HOME_DESCRIPTION,
);

const homeKeywords = computed(() =>
    showWorldCupSection() ? `${SEO_DEFAULT_KEYWORDS}, ${SEO_WORLDCUP_KEYWORDS}` : SEO_DEFAULT_KEYWORDS,
);

const homeSchema = computed(() => [
    buildOrganizationSchema(siteBaseUrl.value),
    buildWebSiteSchema(siteBaseUrl.value),
    buildWebPageSchema({
        baseUrl: siteBaseUrl.value,
        name: 'AIDARA — Aplikasi Informasi Data Olahraga Dispora Kabupaten Bogor',
        description: homeDescription.value,
        url: homeUrl.value,
    }),
    buildFaqSchema(faq),
]);

const isLoggedIn = computed(() => Boolean(page.props.auth?.user));

const colorMap: Record<string, { wrap: string; icon: string }> = {
    database: { wrap: 'bg-green-100 dark:bg-green-950/50', icon: 'text-green-600 dark:text-green-400' },
    users: { wrap: 'bg-blue-100 dark:bg-blue-950/50', icon: 'text-blue-600 dark:text-blue-400' },
    'shield-alt': { wrap: 'bg-purple-100 dark:bg-purple-950/50', icon: 'text-purple-600 dark:text-purple-400' },
    user: { wrap: 'bg-blue-100 dark:bg-blue-950/50', icon: 'text-blue-600 dark:text-blue-400' },
    'clipboard-list': { wrap: 'bg-green-100 dark:bg-green-950/50', icon: 'text-green-600 dark:text-green-400' },
    stethoscope: { wrap: 'bg-purple-100 dark:bg-purple-950/50', icon: 'text-purple-600 dark:text-purple-400' },
    'chart-bar': { wrap: 'bg-yellow-100 dark:bg-yellow-950/50', icon: 'text-yellow-600 dark:text-yellow-400' },
    trophy: { wrap: 'bg-orange-100 dark:bg-orange-950/50', icon: 'text-orange-600 dark:text-orange-400' },
    'calendar-check': { wrap: 'bg-teal-100 dark:bg-teal-950/50', icon: 'text-teal-600 dark:text-teal-400' },
};

const iconStyle = (icon: string) => colorMap[icon] ?? { wrap: 'bg-muted', icon: 'text-muted-foreground' };
</script>

<template>
    <SeoHead
        title="AIDARA — Aplikasi Informasi Data Olahraga Dispora Kabupaten Bogor"
        :description="homeDescription"
        :keywords="homeKeywords"
        :canonical="homeUrl"
        :schema="homeSchema"
    />

    <div class="welcome-page bg-background text-foreground min-h-screen">
        <a href="#beranda" class="skip-link">Lompat ke konten utama</a>

        <header class="border-border/60 bg-background/80 sticky top-0 z-50 border-b backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
                <a href="#beranda" class="flex items-center gap-3" aria-label="AIDARA — kembali ke beranda">
                    <div class="flex items-center gap-2">
                        <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" :lazy="false" />
                        <span class="bg-border/80 h-8 w-px" aria-hidden="true"></span>
                        <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-9 object-contain" :lazy="false" />
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-foreground text-sm leading-tight font-bold tracking-wide">AIDARA</p>
                        <p class="text-muted-foreground text-xs leading-tight">Dispora Kabupaten Bogor</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-5 text-sm font-medium lg:flex" aria-label="Navigasi utama">
                    <a href="#keunggulan" class="text-muted-foreground hover:text-foreground transition-colors">Keunggulan</a>
                    <a href="#cara-kerja" class="text-muted-foreground hover:text-foreground transition-colors">Cara Kerja</a>
                    <a href="#fitur" class="text-muted-foreground hover:text-foreground transition-colors">Fitur</a>
                    <a
                        v-if="showWorldCupSection()"
                        href="#piala-dunia"
                        class="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Piala Dunia
                    </a>
                    <a href="#faq" class="text-muted-foreground hover:text-foreground transition-colors">FAQ</a>
                    <Link :href="route('event.public.index')" class="text-muted-foreground hover:text-foreground transition-colors">Event</Link>
                    <Link
                        v-if="showWorldCupNav() && !showWorldCupSection()"
                        :href="route('worldcup.index')"
                        class="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Piala Dunia
                    </Link>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="isLoggedIn"
                        :href="route('dashboard')"
                        class="welcome-cta inline-block rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="border-border hover:bg-accent hidden rounded-md border px-4 py-1.5 text-sm transition-colors sm:inline-block"
                        >
                            Login
                        </Link>
                        <Link
                            :href="route('register')"
                            class="welcome-cta inline-block rounded-md px-4 py-1.5 text-sm font-medium transition-colors"
                        >
                            Daftar
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <!-- Hero -->
            <section id="beranda" class="relative overflow-hidden">
                <div class="welcome-hero absolute inset-0 opacity-40" aria-hidden="true"></div>
                <div class="relative mx-auto max-w-6xl px-6 py-16 lg:py-24">
                    <div class="grid items-center gap-12 lg:grid-cols-2">
                        <div>
                            <p class="text-muted-foreground mb-4 text-xs font-semibold tracking-widest uppercase">
                                Dispora Kabupaten Bogor
                            </p>
                            <h1 class="mb-4 text-4xl leading-tight font-bold tracking-tight lg:text-5xl">
                                <span class="text-[var(--brand-green,#2e7d32)]">AIDARA</span>
                            </h1>
                            <p class="text-muted-foreground mb-2 text-xl font-medium">Aplikasi Informasi Data Olahraga</p>
                            <p class="text-muted-foreground mb-8 max-w-xl text-base leading-relaxed">
                                Sistem terpadu untuk pengelolaan data cabang olahraga, atlet, pelatih, dan tenaga pendukung —
                                modern, terstruktur, dan mudah diakses.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <Link
                                    v-if="!isLoggedIn"
                                    :href="route('register')"
                                    class="welcome-cta inline-flex items-center rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                                >
                                    Mulai Sekarang
                                </Link>
                                <Link
                                    v-else
                                    :href="route('dashboard')"
                                    class="welcome-cta inline-flex items-center rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                                >
                                    Ke Dasbor
                                </Link>
                                <a
                                    href="#fitur"
                                    class="border-border hover:bg-accent inline-flex items-center rounded-md border px-6 py-2.5 text-sm font-medium transition-colors"
                                >
                                    Lihat Fitur
                                </a>
                            </div>
                        </div>

                        <div class="flex justify-center lg:justify-end">
                            <div class="welcome-hero flex h-64 w-64 items-center justify-center rounded-3xl shadow-xl lg:h-72 lg:w-72">
                                <div class="flex items-center gap-3 rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                                    <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-16 object-contain lg:size-20" :lazy="false" />
                                    <span class="bg-border/80 h-16 w-px lg:h-20" aria-hidden="true"></span>
                                    <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-16 object-contain lg:size-20" :lazy="false" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <WorldCupPreviewSection
                v-if="showWorldCupSection()"
                :matches="worldcupPreview?.matches ?? []"
                :title="page.props.worldcup?.section_title ?? 'Piala Dunia 2026'"
            />

            <!-- Keunggulan -->
            <section id="keunggulan" class="border-border/60 border-t bg-muted/20">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <div class="mb-10 text-center">
                        <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Mengapa AIDARA</p>
                        <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Keunggulan Platform</h2>
                        <p class="text-muted-foreground mx-auto mt-3 max-w-2xl text-base">
                            Solusi digital resmi Dispora Kabupaten Bogor untuk pengelolaan data olahraga yang efisien.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        <article v-for="item in keunggulan" :key="item.title" class="content-panel p-6">
                            <div
                                class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
                                :class="iconStyle(item.icon).wrap"
                            >
                                <FontAwesomeIcon :icon="['fas', item.icon]" class="h-5 w-5" :class="iconStyle(item.icon).icon" />
                            </div>
                            <h3 class="text-foreground mb-2 font-semibold">{{ item.title }}</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">{{ item.text }}</p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- Cara Kerja -->
            <section id="cara-kerja">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <div class="mb-10 text-center">
                        <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Alur Penggunaan</p>
                        <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Cara Kerja</h2>
                        <p class="text-muted-foreground mx-auto mt-3 max-w-2xl text-base">
                            Empat langkah sederhana untuk mulai mengelola data olahraga di Kabupaten Bogor.
                        </p>
                    </div>

                    <ol class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <li v-for="item in caraKerja" :key="item.step" class="content-panel relative p-6">
                            <span class="text-[var(--brand-green,#2e7d32)] mb-3 block text-2xl font-bold">{{ item.step }}</span>
                            <h3 class="text-foreground mb-2 font-semibold">{{ item.title }}</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">{{ item.text }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Fitur -->
            <section id="fitur" class="border-border/60 border-t bg-muted/20">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <div class="mb-10 text-center">
                        <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Modul Sistem</p>
                        <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Fitur Utama</h2>
                        <p class="text-muted-foreground mx-auto mt-3 max-w-2xl text-base">
                            Semua kebutuhan pengelolaan data olahraga dalam satu platform terintegrasi.
                        </p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <article v-for="item in fitur" :key="item.title" class="content-panel p-6">
                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl" :class="iconStyle(item.icon).wrap">
                                <FontAwesomeIcon :icon="['fas', item.icon]" class="h-5 w-5" :class="iconStyle(item.icon).icon" />
                            </div>
                            <h3 class="text-foreground mb-2 font-semibold">{{ item.title }}</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">{{ item.text }}</p>
                            <Link
                                v-if="item.link === 'event'"
                                :href="route('event.public.index')"
                                class="text-[var(--brand-green,#2e7d32)] mt-3 inline-block text-sm font-semibold hover:underline"
                            >
                                Lihat Event →
                            </Link>
                            <Link
                                v-else-if="item.link === 'ebooking'"
                                :href="route('e-booking.catalog')"
                                class="text-[var(--brand-green,#2e7d32)] mt-3 inline-block text-sm font-semibold hover:underline"
                            >
                                Buka E-Booking →
                            </Link>
                        </article>
                    </div>
                </div>
            </section>

            <!-- FAQ -->
            <section id="faq">
                <div class="mx-auto max-w-3xl px-6 py-16">
                    <div class="mb-10 text-center">
                        <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Pertanyaan Umum</p>
                        <h2 class="text-foreground text-2xl font-bold lg:text-3xl">FAQ</h2>
                    </div>

                    <div class="space-y-3">
                        <details
                            v-for="item in faq"
                            :key="item.question"
                            class="content-panel group rounded-xl"
                        >
                            <summary class="text-foreground cursor-pointer list-none px-5 py-4 font-semibold marker:content-none [&::-webkit-details-marker]:hidden">
                                {{ item.question }}
                            </summary>
                            <p class="text-muted-foreground border-border/60 border-t px-5 pt-3 pb-4 text-sm leading-relaxed">
                                {{ item.answer }}
                            </p>
                        </details>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section id="cta" class="mx-auto max-w-6xl px-6 pb-16">
                <div class="welcome-hero overflow-hidden rounded-2xl">
                    <div class="relative px-6 py-12 text-center lg:px-16 lg:py-16">
                        <h2 class="text-foreground mb-3 text-2xl font-bold">Siap Mengelola Data Olahraga?</h2>
                        <p class="text-muted-foreground mx-auto mb-8 max-w-xl text-base">
                            Bergabung dengan AIDARA — platform resmi Dispora Kabupaten Bogor untuk pengelolaan data olahraga yang
                            lebih efisien.
                        </p>
                        <div class="flex flex-wrap justify-center gap-3">
                            <Link
                                v-if="!isLoggedIn"
                                :href="route('register')"
                                class="welcome-cta inline-block rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                            >
                                Daftar Sekarang
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="welcome-cta inline-block rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                            >
                                Buka Dasbor
                            </Link>
                            <Link
                                v-if="!isLoggedIn"
                                :href="route('login')"
                                class="border-border hover:bg-accent inline-block rounded-md border bg-white/80 px-6 py-2.5 text-sm font-medium transition-colors dark:bg-gray-900/80"
                            >
                                Sudah Punya Akun? Login
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-border bg-card border-t" role="contentinfo" aria-label="Footer situs">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="grid gap-10 md:grid-cols-4">
                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <AppImage src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" :lazy="false" />
                            <AppImage src="/Logo.svg" alt="Logo Aidara" class="size-9 object-contain" :lazy="false" />
                            <div>
                                <p class="text-foreground font-bold">AIDARA</p>
                                <p class="text-muted-foreground text-xs">Aplikasi Integrasi Data Olahraga</p>
                            </div>
                        </div>
                        <p class="text-muted-foreground mb-4 text-sm leading-relaxed">
                            Sistem informasi olahraga resmi Dinas Pemuda dan Olahraga Kabupaten Bogor.
                        </p>
                        <div class="flex items-center gap-2">
                            <a
                                v-for="social in socialLinks"
                                :key="social.name"
                                :href="social.href"
                                target="_blank"
                                rel="noopener noreferrer"
                                :aria-label="social.name"
                                class="border-border hover:bg-accent flex h-8 w-8 items-center justify-center rounded-full border transition-colors"
                            >
                                <svg class="h-3.5 w-3.5 text-foreground" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path :d="social.icon" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-foreground mb-3 text-sm font-semibold">Navigasi</h2>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#keunggulan" class="text-muted-foreground hover:text-foreground transition-colors">Keunggulan</a></li>
                            <li><a href="#cara-kerja" class="text-muted-foreground hover:text-foreground transition-colors">Cara Kerja</a></li>
                            <li><a href="#fitur" class="text-muted-foreground hover:text-foreground transition-colors">Fitur</a></li>
                            <li v-if="showWorldCupSection()">
                                <a href="#piala-dunia" class="text-muted-foreground hover:text-foreground transition-colors">Piala Dunia 2026</a>
                            </li>
                            <li><a href="#faq" class="text-muted-foreground hover:text-foreground transition-colors">FAQ</a></li>
                            <li><Link :href="route('event.public.index')" class="text-muted-foreground hover:text-foreground transition-colors">Event & Kegiatan</Link></li>
                            <li v-if="showWorldCupNav() && !showWorldCupSection()">
                                <Link :href="route('worldcup.index')" class="text-muted-foreground hover:text-foreground transition-colors">Piala Dunia</Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-foreground mb-3 text-sm font-semibold">Kontak</h2>
                        <ul class="text-muted-foreground space-y-3 text-sm">
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'map-marker-alt']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" aria-hidden="true" />
                                <span>Jalan Tegar Beriman, Cibinong, Kabupaten Bogor</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'phone']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" aria-hidden="true" />
                                <a href="tel:+622517503524" class="hover:text-foreground transition-colors">(0251) 7503524</a>
                            </li>
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" aria-hidden="true" />
                                <a href="mailto:dispora@bogorkab.go.id" class="hover:text-foreground transition-colors">dispora@bogorkab.go.id</a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h2 class="text-foreground mb-3 text-sm font-semibold">Legal</h2>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link :href="route('legal.show', { slug: 'terms' })" class="text-muted-foreground hover:text-foreground transition-colors">
                                    Syarat & Ketentuan
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('legal.show', { slug: 'privacy' })" class="text-muted-foreground hover:text-foreground transition-colors">
                                    Kebijakan Privasi
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('legal.show', { slug: 'pdp' })" class="text-muted-foreground hover:text-foreground transition-colors">
                                    Perlindungan Data Pribadi (PDP)
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="border-border text-muted-foreground mt-10 flex flex-col items-center justify-between gap-3 border-t pt-6 text-center text-xs sm:flex-row sm:text-left"
                >
                    <p>&copy; {{ new Date().getFullYear() }} Dinas Pemuda dan Olahraga (DISPORA) Kabupaten Bogor — AIDARA.</p>
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                        <Link :href="route('legal.show', { slug: 'terms' })" class="hover:text-foreground transition-colors">Syarat & Ketentuan</Link>
                        <span class="text-muted-foreground/50" aria-hidden="true">•</span>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="hover:text-foreground transition-colors">Privasi</Link>
                        <span class="text-muted-foreground/50" aria-hidden="true">•</span>
                        <Link :href="route('legal.show', { slug: 'pdp' })" class="hover:text-foreground transition-colors">PDP</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
