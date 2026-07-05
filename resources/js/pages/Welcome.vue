<script setup lang="ts">
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faBasketballBall,
    faBuilding,
    faChartBar,
    faClipboardList,
    faDatabase,
    faEnvelope,
    faFutbol,
    faGavel,
    faListCheck,
    faMapMarkerAlt,
    faPhone,
    faRunning,
    faShieldAlt,
    faStethoscope,
    faTableTennis,
    faBullseye,
    faUser,
    faUsers,
    faVolleyballBall,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import WorldCupPreviewSection from '@/components/worldcup/WorldCupPreviewSection.vue';
import EventPreviewSection from '@/components/event/EventPreviewSection.vue';
import type { PublicEventSummary } from '@/types/event';
import type { WorldCupPreview, WorldCupSettings } from '@/types/worldcup';
import { Head, Link, usePage } from '@inertiajs/vue3';

library.add(
    faFutbol,
    faBasketballBall,
    faTableTennis,
    faVolleyballBall,
    faRunning,
    faUser,
    faClipboardList,
    faStethoscope,
    faChartBar,
    faDatabase,
    faUsers,
    faShieldAlt,
    faMapMarkerAlt,
    faPhone,
    faEnvelope,
    faBullseye,
    faListCheck,
    faBuilding,
    faGavel,
);

const misi = [
    {
        label: 'Misi Pertama',
        text: 'Meningkatkan penyadaran, pemberdayaan, pengembangan, dan kemandirian pemuda.',
    },
    {
        label: 'Misi Kedua',
        text: 'Meningkatkan pemberdayaan dan peningkatan prestasi olahraga.',
    },
    {
        label: 'Misi Ketiga',
        text: 'Meningkatkan pembangunan sarana dan prasarana olahraga.',
    },
    {
        label: 'Misi Keempat',
        text: 'Meningkatkan kinerja sumberdaya aparatur dan pelaku olahraga.',
    },
];

const fungsi = [
    'Perumusan kebijakan di bidang kepemudaan dan olahraga.',
    'Pelaksanaan kebijakan di bidang kepemudaan dan olahraga.',
    'Pelaksanaan evaluasi dan pelaporan di bidang kepemudaan dan olahraga.',
    'Pelaksanaan administrasi dinas.',
    'Pelaksanaan fungsi lain yang diberikan oleh Bupati sesuai dengan bidang tugasnya.',
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
    eventPreview?: PublicEventSummary[];
}>();

const page = usePage<{ worldcup?: WorldCupSettings }>();

const showWorldCupNav = () => Boolean(page.props.worldcup?.enabled);
const showWorldCupSection = () => Boolean(page.props.worldcup?.enabled && page.props.worldcup?.show_on_landing);
</script>

<template>
    <Head title="AIDARA — Aplikasi Intregasi Data Olahraga Dispora Kabupaten Bogor" />

    <div class="welcome-page bg-background text-foreground min-h-screen">
        <!-- Navbar -->
        <header class="border-border/60 bg-background/80 sticky top-0 z-50 border-b backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-3">
                <a href="#beranda" class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <img src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" />
                        <span class="bg-border/80 h-8 w-px"></span>
                        <img src="/Logo.png" alt="Logo Dispora Kabupaten Bogor" class="size-9 object-contain" />
                    </div>
                    <div class="hidden sm:block">
                        <p class="text-foreground text-sm leading-tight font-bold tracking-wide">AIDARA</p>
                        <p class="text-muted-foreground text-xs leading-tight">Dispora Kabupaten Bogor</p>
                    </div>
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
                    <a href="#beranda" class="text-muted-foreground hover:text-foreground transition-colors">Beranda</a>
                    <a href="#profil" class="text-muted-foreground hover:text-foreground transition-colors">Profil</a>
                    <a href="#tentang" class="text-muted-foreground hover:text-foreground transition-colors">Tentang</a>
                    <a href="#event" class="text-muted-foreground hover:text-foreground transition-colors">Event</a>
                    <a href="#portal" class="text-muted-foreground hover:text-foreground transition-colors">Portal</a>
                    <a
                        v-if="showWorldCupNav()"
                        href="#piala-dunia"
                        class="text-muted-foreground hover:text-foreground transition-colors"
                    >
                        Piala Dunia
                    </a>
                    <a href="#kontak" class="text-muted-foreground hover:text-foreground transition-colors">Kontak</a>
                </nav>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="$page.props.auth.user"
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

        <!-- Hero -->
        <section id="beranda" class="relative overflow-hidden">
            <div class="welcome-hero absolute inset-0 opacity-40"></div>
            <div class="relative mx-auto max-w-6xl px-6 py-16 lg:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-border/60 bg-card/80 px-3 py-1.5 shadow-sm">
                            <img src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-5 object-contain" />
                            <p class="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Website Resmi Dinas Pemuda dan Olahraga Kabupaten Bogor
                            </p>
                        </div>
                        <h1 class="mb-4 text-4xl leading-tight font-bold tracking-tight lg:text-5xl">
                            <span class="text-[var(--brand-green,#2e7d32)]">AIDARA</span>
                        </h1>
                        <p class="text-muted-foreground mb-2 text-xl font-medium">Aplikasi Intregasi Data Olahraga</p>
                        <p class="text-muted-foreground mb-8 max-w-xl text-base leading-relaxed">
                            Sistem terpadu milik Dispora Kabupaten Bogor untuk pengelolaan data cabang olahraga, atlet, pelatih, dan
                            tenaga pendukung — modern, terstruktur, dan mudah diakses guna mendukung terwujudnya kemandirian pemuda
                            dan peningkatan prestasi olahraga.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <Link
                                v-if="!$page.props.auth.user"
                                :href="route('login')"
                                class="welcome-cta inline-flex items-center rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                            >
                                Masuk ke Sistem
                            </Link>
                            <Link
                                v-else
                                :href="route('dashboard')"
                                class="welcome-cta inline-flex items-center rounded-md px-6 py-2.5 text-sm font-medium transition-colors"
                            >
                                Ke Dasbor
                            </Link>
                            <a
                                href="#profil"
                                class="border-border hover:bg-accent inline-flex items-center rounded-md border px-6 py-2.5 text-sm font-medium transition-colors"
                            >
                                Profil Dispora
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center lg:justify-end">
                        <div class="relative">
                            <div class="welcome-hero flex h-64 w-64 items-center justify-center rounded-3xl shadow-xl lg:h-72 lg:w-72">
                                <div class="flex items-center gap-3 rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-800">
                                    <img src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-16 object-contain lg:size-20" />
                                    <span class="bg-border/80 h-16 w-px lg:h-20"></span>
                                    <img src="/Logo.png" alt="Logo DISPORA Kabupaten Bogor" class="size-16 object-contain lg:size-20" />
                                </div>
                            </div>
                            <div class="absolute -top-4 -right-4 h-10 w-10 rounded-full bg-orange-400/80"></div>
                            <div class="absolute -bottom-2 -left-4 h-8 w-16 rounded-full bg-green-400/70"></div>
                            <div
                                class="absolute top-8 -left-6 h-5 w-5 bg-yellow-400/80"
                                style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%)"
                            ></div>
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

        <EventPreviewSection :events="eventPreview ?? []" />

        <!-- Profil Instansi: Visi, Misi, Tugas & Fungsi -->
        <section id="profil">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="mb-10 text-center">
                    <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Profil Instansi</p>
                    <h2 class="text-foreground mb-3 text-2xl font-bold lg:text-3xl">Visi, Misi, Tugas &amp; Fungsi</h2>
                    <p class="text-muted-foreground mx-auto max-w-2xl text-base">
                        Dinas Pemuda dan Olahraga (DISPORA) Kabupaten Bogor berkomitmen mendukung kepemudaan dan keolahragaan
                        melalui arah kebijakan berikut.
                    </p>
                </div>

                <!-- Visi -->
                <div class="welcome-hero mb-6 rounded-2xl px-6 py-10 text-center lg:px-16">
                    <div class="mb-4 flex justify-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/80 shadow-sm dark:bg-gray-900/60">
                            <FontAwesomeIcon :icon="['fas', 'bullseye']" class="h-5 w-5 text-[var(--brand-green,#2e7d32)]" />
                        </div>
                    </div>
                    <p class="text-muted-foreground mb-2 text-xs font-semibold tracking-widest uppercase">Visi</p>
                    <p class="text-foreground mx-auto max-w-3xl text-xl font-bold italic lg:text-2xl">
                        "Terwujudnya Kemandirian Pemuda dan Peningkatan Prestasi Olahraga"
                    </p>
                </div>

                <!-- Misi -->
                <div class="mb-10 grid gap-4 md:grid-cols-2">
                    <div v-for="item in misi" :key="item.label" class="content-panel flex gap-4 p-6">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 font-bold text-green-700 dark:bg-green-950/50 dark:text-green-400"
                        >
                            <FontAwesomeIcon :icon="['fas', 'list-check']" class="h-4 w-4" />
                        </div>
                        <div>
                            <h3 class="text-foreground mb-1 text-sm font-semibold">{{ item.label }}</h3>
                            <p class="text-muted-foreground text-sm leading-relaxed">{{ item.text }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tugas & Fungsi -->
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="content-panel p-6 lg:p-8">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/50">
                            <FontAwesomeIcon :icon="['fas', 'building']" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-foreground mb-2 text-lg font-semibold">Tugas Pokok</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            Dinas Pemuda dan Olahraga mempunyai tugas pokok membantu Bupati dalam melaksanakan urusan pemerintahan
                            di bidang kepemudaan dan keolahragaan.
                        </p>
                    </div>
                    <div class="content-panel p-6 lg:p-8">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 dark:bg-orange-950/50">
                            <FontAwesomeIcon :icon="['fas', 'gavel']" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <h3 class="text-foreground mb-3 text-lg font-semibold">Fungsi</h3>
                        <ul class="text-muted-foreground space-y-2 text-sm leading-relaxed">
                            <li v-for="(item, index) in fungsi" :key="item" class="flex gap-2">
                                <span class="text-foreground font-semibold">{{ String.fromCharCode(97 + index) }})</span>
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tentang -->
        <section id="tentang">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="mb-10 text-center">
                    <h2 class="text-foreground mb-3 text-2xl font-bold lg:text-3xl">Tentang AIDARA</h2>
                    <p class="text-muted-foreground mx-auto max-w-2xl text-base">
                        AIDARA dikembangkan untuk mendukung Dispora Kabupaten Bogor dalam digitalisasi pengelolaan data
                        olahraga secara terpusat dan akuntabel.
                    </p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-950/50">
                            <FontAwesomeIcon :icon="['fas', 'database']" class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Data Terpusat</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            Semua data cabang olahraga, atlet, pelatih, dan tenaga pendukung tersimpan dalam satu sistem
                            yang terintegrasi.
                        </p>
                    </div>
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/50">
                            <FontAwesomeIcon :icon="['fas', 'users']" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Kolaborasi Tim</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            Admin, pelatih, dan tenaga pendukung dapat bekerja bersama melalui portal yang aman dan
                            terstruktur per cabang olahraga.
                        </p>
                    </div>
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-950/50">
                            <FontAwesomeIcon :icon="['fas', 'shield-alt']" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Akuntabel & Transparan</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            Laporan statistik dan riwayat pemeriksaan kesehatan membantu pengambilan keputusan berbasis
                            data di Dispora Kabupaten Bogor.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portal overview — section dari screenshot, dipertahankan -->
        <section id="portal">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="mb-10 text-center">
                    <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Portal Manajemen</p>
                    <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Kelola Olahraga dengan Mudah</h2>
                </div>

                <div class="flex w-full items-center justify-center">
                    <main class="welcome-main flex w-full max-w-[335px] flex-col overflow-hidden rounded-xl lg:max-w-5xl lg:flex-row lg:shadow-lg">
                        <!-- Panel kiri: konten -->
                        <div class="welcome-content bg-card flex-1 rounded-xl p-6 pb-12 text-[13px] leading-[20px] lg:rounded-r-none lg:p-20">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-14 w-14 items-center justify-center rounded-full">
                                    <img src="/Logo.png" alt="Logo DISPORA" class="size-15 object-contain" />
                                </div>
                                <div>
                                    <h1 class="welcome-title text-3xl font-bold">AIDARA</h1>
                                    <p class="text-muted-foreground text-sm">Aplikasi Integrasi Data Olahraga</p>
                                </div>
                            </div>

                            <p class="text-muted-foreground mb-6 text-base">
                                Portal manajemen cabang olahraga. Kelola atlet, pelatih, dan fasilitas olahraga dengan mudah
                                dan efisien.
                            </p>

                            <div class="mb-6">
                                <h2 class="text-foreground mb-4 text-lg font-semibold">Cabang Olahraga</h2>
                                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'futbol']" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Sepak Bola</span>
                                    </div>
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'basketball-ball']" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Basket</span>
                                    </div>
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-950/50">
                                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M12 3c-1 0-2 .5-2.5 1.5L8 7c-.5 1-1.5 1-2.5 1s-2-.5-2.5-1.5L2 4c-.5-1-.5-2 0-3s1.5-1 2.5-1h15c1 0 2 .5 2.5 1.5s.5 2 0 3l-1 2.5c-.5 1-1.5 1.5-2.5 1.5s-2 0-2.5-1L14.5 4.5C14 3.5 13 3 12 3zm0 8c-2.5 0-4.5 2-4.5 4.5v6c0 1.4 1.1 2.5 2.5 2.5h4c1.4 0 2.5-1.1 2.5-2.5v-6C16.5 13 14.5 11 12 11z"
                                                />
                                            </svg>
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Badminton</span>
                                    </div>
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'table-tennis']" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Tenis Meja</span>
                                    </div>
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'volleyball-ball']" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Voli</span>
                                    </div>
                                    <div class="welcome-sport-card flex items-center gap-3 rounded-lg border border-border/80 p-2">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                            <FontAwesomeIcon :icon="['fas', 'running']" class="text-muted-foreground h-5 w-5" />
                                        </div>
                                        <span class="text-foreground text-sm font-medium">Dan Lain-lain</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-6">
                                <h2 class="text-foreground mb-3 text-lg font-semibold">Fitur Utama</h2>
                                <ul class="space-y-3">
                                    <li class="flex items-center gap-3">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'user']" class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                        </div>
                                        <span class="text-foreground text-sm">Kelola Atlet, Pelatih & Tenaga Pendukung</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 dark:bg-green-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-3 w-3 text-green-600 dark:text-green-400" />
                                        </div>
                                        <span class="text-foreground text-sm">Program Latihan</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'stethoscope']" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                        </div>
                                        <span class="text-foreground text-sm">Pemeriksaan Kesehatan</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-950/50">
                                            <FontAwesomeIcon :icon="['fas', 'chart-bar']" class="h-3 w-3 text-yellow-600 dark:text-yellow-400" />
                                        </div>
                                        <span class="text-foreground text-sm">Laporan Statistik</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="flex flex-wrap gap-3 text-sm leading-normal">
                                <Link
                                    v-if="!$page.props.auth.user"
                                    :href="route('login')"
                                    class="welcome-cta inline-block rounded-md px-5 py-1.5 text-sm font-medium leading-normal transition-colors"
                                >
                                    Masuk ke Sistem
                                </Link>
                                <Link
                                    v-else
                                    :href="route('dashboard')"
                                    class="welcome-cta inline-block rounded-md px-5 py-1.5 text-sm font-medium leading-normal transition-colors"
                                >
                                    Ke Dasbor
                                </Link>
                            </div>
                        </div>

                        <!-- Panel kanan: ilustrasi -->
                        <div class="welcome-hero relative aspect-square w-full shrink-0 overflow-hidden rounded-b-xl lg:w-[400px] lg:rounded-b-none lg:rounded-r-xl">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <div class="relative">
                                    <div class="absolute -top-12 -left-12 h-8 w-8 rounded-full bg-orange-200/80 opacity-70 dark:bg-orange-800/60">
                                        <div class="absolute inset-1 rounded-full border-2 border-orange-400">
                                            <div class="absolute top-0 left-1/2 h-full w-0.5 -translate-x-1/2 bg-orange-400"></div>
                                            <div class="absolute top-1/2 left-0 h-0.5 w-full -translate-y-1/2 bg-orange-400"></div>
                                        </div>
                                    </div>

                                    <div class="absolute -right-8 -bottom-8 h-6 w-12 rounded-full bg-green-200/80 opacity-70 dark:bg-green-800/60"></div>

                                    <div
                                        class="absolute -top-9 right-12 h-4 w-4 bg-yellow-200/90 opacity-70 dark:bg-yellow-700/60"
                                        style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%)"
                                    ></div>

                                    <div class="flex h-36 w-36 items-center justify-center rounded-3xl bg-white shadow-2xl dark:bg-gray-800">
                                        <div class="relative">
                                            <img src="/Logo.png" alt="Logo DISPORA" class="size-24 object-contain" />
                                            <div class="absolute -top-4 left-10 flex h-4 w-4 items-center justify-center rounded-full bg-yellow-300">
                                                <div class="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <div class="absolute -bottom-1 -left-2 flex h-4 w-4 items-center justify-center rounded-full bg-green-600">
                                                <div class="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                            <div class="absolute -right-2 -bottom-1 flex h-4 w-4 items-center justify-center rounded-full bg-orange-400">
                                                <div class="h-2 w-2 rounded-full bg-white"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="absolute -inset-8 rounded-full border-2 border-dashed border-yellow-300/50 opacity-40 dark:border-yellow-700/40"></div>
                                    <div class="absolute -inset-12 rounded-full border border-dashed border-blue-300/40 opacity-30 dark:border-blue-700/30"></div>
                                </div>
                            </div>

                            <div class="absolute right-0 bottom-0 left-0 h-1/3 bg-gradient-to-t from-green-100/50 to-transparent dark:from-green-900/30"></div>
                            <div class="absolute bottom-0 left-0 h-20 w-20 rounded-tr-full bg-gradient-to-tr from-transparent to-white/20"></div>
                            <div class="absolute top-0 right-0 h-20 w-20 rounded-bl-full bg-gradient-to-bl from-transparent to-white/20"></div>
                        </div>
                    </main>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="welcome-hero mx-6 mt-16 mb-16 max-w-6xl overflow-hidden rounded-2xl lg:mx-auto">
            <div class="relative px-6 py-12 text-center lg:px-16 lg:py-16">
                <h2 class="text-foreground mb-3 text-2xl font-bold">Siap Mengelola Data Olahraga?</h2>
                <p class="text-muted-foreground mx-auto mb-8 max-w-xl text-base">
                    Bergabung dengan AIDARA — platform resmi Dispora Kabupaten Bogor untuk pengelolaan data olahraga yang
                    lebih efisien.
                </p>
                <div class="flex flex-wrap justify-center gap-3">
                    <Link
                        v-if="!$page.props.auth.user"
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
                        v-if="!$page.props.auth.user"
                        :href="route('login')"
                        class="border-border hover:bg-accent inline-block rounded-md border bg-white/80 px-6 py-2.5 text-sm font-medium transition-colors dark:bg-gray-900/80"
                    >
                        Sudah Punya Akun? Login
                    </Link>
                </div>
            </div>
        </section>

        <!-- Kontak -->
        <section id="kontak" class="border-border/60 bg-muted/30 border-y">
            <div class="mx-auto max-w-6xl px-6 py-16">
                <div class="mb-10 text-center">
                    <p class="text-muted-foreground mb-2 text-sm font-medium tracking-widest uppercase">Hubungi Kami</p>
                    <h2 class="text-foreground text-2xl font-bold lg:text-3xl">Kontak Dispora Kabupaten Bogor</h2>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-green-100 dark:bg-green-950/50">
                            <FontAwesomeIcon :icon="['fas', 'map-marker-alt']" class="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Alamat</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">Jalan Tegar Beriman, Cibinong, Kabupaten Bogor</p>
                    </div>
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-950/50">
                            <FontAwesomeIcon :icon="['fas', 'phone']" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Kontak Kami</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            <a href="tel:+622517503524" class="hover:text-foreground transition-colors">(0251) 7503524</a>
                        </p>
                    </div>
                    <div class="content-panel p-6">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-950/50">
                            <FontAwesomeIcon :icon="['fas', 'envelope']" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <h3 class="text-foreground mb-2 font-semibold">Email</h3>
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            <a href="mailto:dispora@bogorkab.go.id" class="hover:text-foreground transition-colors">dispora@bogorkab.go.id</a>
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col items-center justify-center gap-4 sm:flex-row">
                    <p class="text-muted-foreground text-sm font-medium">Ikuti media sosial kami:</p>
                    <div class="flex items-center gap-3">
                        <a
                            v-for="social in socialLinks"
                            :key="social.name"
                            :href="social.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            :aria-label="social.name"
                            class="border-border hover:bg-accent flex h-10 w-10 items-center justify-center rounded-full border bg-card transition-colors"
                        >
                            <svg class="h-4 w-4 text-foreground" viewBox="0 0 24 24" fill="currentColor">
                                <path :d="social.icon" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-border bg-card border-t">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="grid gap-10 md:grid-cols-4">
                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <img src="/kabupaten_bogor.webp" alt="Logo Kabupaten Bogor" class="size-9 object-contain" />
                            <img src="/Logo.png" alt="Logo DISPORA" class="size-9 object-contain" />
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
                                <svg class="h-3.5 w-3.5 text-foreground" viewBox="0 0 24 24" fill="currentColor">
                                    <path :d="social.icon" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-foreground mb-3 text-sm font-semibold">Navigasi</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#beranda" class="text-muted-foreground hover:text-foreground transition-colors">Beranda</a></li>
                            <li><a href="#profil" class="text-muted-foreground hover:text-foreground transition-colors">Profil</a></li>
                            <li><a href="#tentang" class="text-muted-foreground hover:text-foreground transition-colors">Tentang</a></li>
                            <li><a href="#portal" class="text-muted-foreground hover:text-foreground transition-colors">Portal</a></li>
                            <li>
                                <Link :href="route('login')" class="text-muted-foreground hover:text-foreground transition-colors">Login</Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-foreground mb-3 text-sm font-semibold">Kontak</h3>
                        <ul class="text-muted-foreground space-y-3 text-sm">
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'map-marker-alt']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                                <span>Jalan Tegar Beriman, Cibinong, Kabupaten Bogor</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'phone']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                                <a href="tel:+622517503524" class="hover:text-foreground transition-colors">(0251) 7503524</a>
                            </li>
                            <li class="flex items-start gap-2">
                                <FontAwesomeIcon :icon="['fas', 'envelope']" class="mt-0.5 h-4 w-4 shrink-0 text-green-600" />
                                <a href="mailto:dispora@bogorkab.go.id" class="hover:text-foreground transition-colors">dispora@bogorkab.go.id</a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-foreground mb-3 text-sm font-semibold">Legal</h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link
                                    :href="route('legal.show', { slug: 'terms' })"
                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    Syarat & Ketentuan
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('legal.show', { slug: 'privacy' })"
                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    Kebijakan Privasi
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="route('legal.show', { slug: 'pdp' })"
                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                >
                                    Perlindungan Data Pribadi (PDP)
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="border-border text-muted-foreground mt-10 flex flex-col items-center justify-between gap-3 border-t pt-6 text-center text-xs sm:flex-row sm:text-left"
                >
                    <p>&copy; {{ new Date().getFullYear() }} Dinas Pemuda dan Olahraga (DISPORA) Kabupaten Bogor — AIDARA. Hak cipta dilindungi.</p>
                    <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1">
                        <Link :href="route('legal.show', { slug: 'terms' })" class="hover:text-foreground transition-colors">
                            Syarat & Ketentuan
                        </Link>
                        <span class="text-muted-foreground/50">•</span>
                        <Link :href="route('legal.show', { slug: 'privacy' })" class="hover:text-foreground transition-colors">
                            Privasi
                        </Link>
                        <span class="text-muted-foreground/50">•</span>
                        <Link :href="route('legal.show', { slug: 'pdp' })" class="hover:text-foreground transition-colors"> PDP </Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
