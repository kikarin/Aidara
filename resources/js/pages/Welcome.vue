<script setup lang="ts">
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faBasketballBall,
    faChartBar,
    faClipboardList,
    faFutbol,
    faRunning,
    faStethoscope,
    faTableTennis,
    faUser,
    faVolleyballBall,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Head, Link } from '@inertiajs/vue3';

library.add(faFutbol, faBasketballBall, faTableTennis, faVolleyballBall, faRunning, faUser, faClipboardList, faStethoscope, faChartBar);
</script>

<template>
    <Head title="Selamat Datang" />
    <div class="welcome-page bg-background text-foreground flex min-h-screen flex-col items-center p-6 lg:justify-center lg:p-8">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-5xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="border-border hover:bg-accent inline-block rounded-md border px-5 py-1.5 text-sm leading-normal transition-colors"
                >
                    Dashboard
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="border-border hover:bg-accent inline-block rounded-md border px-5 py-1.5 text-sm leading-normal transition-colors"
                >
                    Login
                </Link>
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="border-border hover:bg-accent inline-block rounded-md border px-5 py-1.5 text-sm leading-normal transition-colors"
                >
                    Register
                </Link>
            </nav>
        </header>

        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="welcome-main flex w-full max-w-[335px] flex-col overflow-hidden rounded-xl lg:max-w-5xl lg:flex-row lg:shadow-lg">
                <!-- Panel kiri: konten -->
                <div class="welcome-content bg-card flex-1 rounded-xl p-6 pb-12 text-[13px] leading-[20px] lg:rounded-r-none lg:p-20">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full">
                            <img src="/Logo.png" alt="Logo DISPORA" class="size-15 object-contain" />
                        </div>
                        <div>
                            <h1 class="welcome-title text-3xl font-bold">DISPORA</h1>
                            <p class="text-muted-foreground text-sm">Dinas Pemuda dan Olahraga</p>
                        </div>
                    </div>

                    <p class="text-muted-foreground mb-6 text-base">
                        Portal manajemen cabang olahraga. Kelola atlet, pelatih, dan fasilitas olahraga dengan mudah dan efisien.
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
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
