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

// Add icons to the library
library.add(faFutbol, faBasketballBall, faTableTennis, faVolleyballBall, faRunning, faUser, faClipboardList, faStethoscope, faChartBar);
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-5xl">
            <nav class="flex items-center justify-end gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <Link
                    v-else
                    :href="route('login')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Login
                </Link>
                <Link
                    v-if="!$page.props.auth.user"
                    :href="route('register')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Register
                </Link>
            </nav>
        </header>
        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col overflow-hidden rounded-lg lg:max-w-5xl lg:flex-row">
                <div
                    class="flex-1 rounded-lg bg-white p-6 pb-12 text-[13px] leading-[20px] shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] lg:p-20 dark:bg-[#161615] dark:text-[#EDEDEC] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full">
                            <img src="/Logo.png" alt="Logo" class="size-15 object-contain" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-[#1b1b18] dark:text-[#EDEDEC]">DISPORA</h1>
                            <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Dinas Pemuda dan Olahraga</p>
                        </div>
                    </div>

                    <p class="mb-6 text-base text-[#706f6c] dark:text-[#A1A09A]">
                        Portal manajemen cabang olahraga. Kelola atlet, pelatih, dan fasilitas olahraga dengan mudah dan efisien.
                    </p>

                    <div class="mb-6">
                        <h2 class="mb-4 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Cabang Olahraga</h2>
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                            <!-- Sepak Bola -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900">
                                    <FontAwesomeIcon :icon="['fas', 'futbol']" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Sepak Bola</span>
                            </div>

                            <!-- Basket -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                    <FontAwesomeIcon :icon="['fas', 'basketball-ball']" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Basket</span>
                            </div>

                            <!-- Badminton -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 3c-1 0-2 .5-2.5 1.5L8 7c-.5 1-1.5 1-2.5 1s-2-.5-2.5-1.5L2 4c-.5-1-.5-2 0-3s1.5-1 2.5-1h15c1 0 2 .5 2.5 1.5s.5 2 0 3l-1 2.5c-.5 1-1.5 1.5-2.5 1.5s-2 0-2.5-1L14.5 4.5C14 3.5 13 3 12 3zm0 8c-2.5 0-4.5 2-4.5 4.5v6c0 1.4 1.1 2.5 2.5 2.5h4c1.4 0 2.5-1.1 2.5-2.5v-6C16.5 13 14.5 11 12 11z"
                                        />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Badminton</span>
                            </div>

                            <!-- Tenis Meja -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                                    <FontAwesomeIcon :icon="['fas', 'table-tennis']" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Tenis Meja</span>
                            </div>

                            <!-- Voli -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900">
                                    <FontAwesomeIcon :icon="['fas', 'volleyball-ball']" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Voli</span>
                            </div>

                            <!-- Dan Lain-lain -->
                            <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-2 dark:border-gray-700">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                    <svg class="h-5 w-5 text-gray-600 dark:text-gray-400" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
                                        />
                                    </svg>
                                </div>
                                <span class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC]">Dan Lain-lain</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="mb-3 text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC]">Fitur Utama</h2>
                        <ul class="space-y-3">
                            <li class="flex items-center gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                    <FontAwesomeIcon :icon="['fas', 'user']" class="h-3 w-3 text-blue-600 dark:text-blue-400" />
                                </div>
                                <span class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"> Kelola Atlet, Pelatih & Tenaga Pendukung </span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                    <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="h-3 w-3 text-green-600 dark:text-green-400" />
                                </div>
                                <span class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"> Program Latihan </span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900">
                                    <FontAwesomeIcon :icon="['fas', 'stethoscope']" class="h-3 w-3 text-purple-600 dark:text-purple-400" />
                                </div>
                                <span class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"> Pemeriksaan Kesehatan </span>
                            </li>
                            <li class="flex items-center gap-3">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                                    <FontAwesomeIcon :icon="['fas', 'chart-bar']" class="h-3 w-3 text-yellow-600 dark:text-yellow-400" />
                                </div>
                                <span class="text-sm text-[#1b1b18] dark:text-[#EDEDEC]"> Laporan Statistik </span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex gap-3 text-sm leading-normal">
                        <Link
                            v-if="!$page.props.auth.user"
                            :href="route('login')"
                            class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                        >
                            Masuk ke Sistem
                        </Link>
                        <Link
                            v-else
                            :href="route('dashboard')"
                            class="inline-block rounded-sm border border-black bg-[#1b1b18] px-5 py-1.5 text-sm leading-normal text-white hover:border-black hover:bg-black dark:border-[#eeeeec] dark:bg-[#eeeeec] dark:text-[#1C1C1A] dark:hover:border-white dark:hover:bg-white"
                        >
                            Ke Dashboard
                        </Link>
                    </div>
                </div>

                <div
                    class="relative aspect-square w-full shrink-0 overflow-hidden rounded-r-lg bg-gradient-to-br from-green-50 via-blue-50 to-orange-50 lg:w-[400px] dark:from-green-950 dark:via-blue-950 dark:to-orange-950"
                >
                    <!-- Sports themed illustration -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="relative">
                            <!-- Sports equipment floating elements -->
                            <div class="absolute -top-12 -left-12 h-8 w-8 rounded-full bg-orange-200 opacity-70 dark:bg-orange-800">
                                <!-- Football -->
                                <div class="absolute inset-1 rounded-full border-2 border-orange-400 dark:border-orange-400">
                                    <div class="absolute top-0 left-1/2 h-full w-0.5 -translate-x-1/2 bg-orange-400 dark:bg-orange-400"></div>
                                    <div class="absolute top-1/2 left-0 h-0.5 w-full -translate-y-1/2 bg-orange-400 dark:bg-orange-400"></div>
                                </div>
                            </div>

                            <div class="absolute -right-8 -bottom-8 h-6 w-12 rounded-full bg-green-200 opacity-70 dark:bg-green-800">
                                <!-- Shuttlecock -->
                            </div>

                            <div
                                class="absolute -top-9 right-12 h-4 w-4 bg-yellow-200 opacity-70 dark:bg-yellow-800"
                                style="clip-path: polygon(50% 0%, 0% 100%, 100% 100%)"
                            >
                                <!-- Trophy -->
                            </div>

                            <!-- Central sports icon -->
                            <div class="flex h-36 w-36 items-center justify-center rounded-3xl bg-white shadow-2xl dark:bg-gray-800">
                                <div class="relative">
                                    <!-- Medal/Trophy icon -->
                                    <img src="/Logo.png" alt="Logo" class="size-24 object-contain" />
                                    <!-- Small sports icons around -->
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

                            <!-- Achievement rings -->
                            <div
                                class="absolute -inset-8 rounded-full border-2 border-dashed border-yellow-300 opacity-30 dark:border-yellow-700"
                            ></div>
                            <div class="absolute -inset-12 rounded-full border border-dashed border-blue-300 opacity-20 dark:border-blue-700"></div>
                        </div>
                    </div>

                    <!-- Sports field pattern -->
                    <div
                        class="absolute right-0 bottom-0 left-0 h-1/3 bg-gradient-to-t from-green-100 to-transparent opacity-30 dark:from-green-900"
                    ></div>

                    <!-- Decorative elements -->
                    <div class="absolute bottom-0 left-0 h-20 w-20 rounded-tr-full bg-gradient-to-tr from-transparent to-white/20"></div>
                    <div class="absolute top-0 right-0 h-20 w-20 rounded-bl-full bg-gradient-to-bl from-transparent to-white/20"></div>

                    <div
                        class="absolute inset-0 rounded-r-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d]"
                    />
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>
