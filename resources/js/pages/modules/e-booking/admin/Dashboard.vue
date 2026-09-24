<script setup lang="ts">
import SeoHead from '@/components/SeoHead.vue';
import { Skeleton } from '@/components/ui/skeleton';
import AdminLayout from '@/layouts/e-booking/AdminLayout.vue';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BadgeCheck,
    CalendarOff,
    CircleAlert,
    Clock3,
    FileSearch,
    Settings2,
    ShieldCheck,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

type DashboardStats = {
    pending_approval: number;
    awaiting_payment: number;
    awaiting_verification: number;
    confirmed_today: number;
    needs_attention: number;
};

type RecentItem = {
    id: number;
    nomor: string;
    status: string;
    venue: string | null;
    penyewa: string | null;
    starts_at: string | null;
    ends_at: string | null;
    grand_total: number;
};

defineProps<{
    stats?: DashboardStats;
    recent?: RecentItem[];
}>();

const page = usePage();
const bookingAuth = computed(() => page.props.bookingAuth as { name: string; email: string } | null);

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 11) {
        return 'Selamat pagi';
    }
    if (hour < 15) {
        return 'Selamat siang';
    }
    if (hour < 18) {
        return 'Selamat sore';
    }

    return 'Selamat malam';
});

const formatRp = (n: number) =>
    new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n);

const formatSchedule = (starts: string | null, ends: string | null) => {
    if (!starts) {
        return 'Jadwal belum diisi';
    }
    if (!ends) {
        return starts;
    }
    const sameDay = starts.slice(0, 10) === ends.slice(0, 10);
    if (sameDay) {
        return `${starts} – ${ends.slice(11, 16)}`;
    }

    return `${starts} – ${ends}`;
};

const statusLabel = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'Perlu ditinjau',
        awaiting_payment: 'Menunggu bayar',
        approved: 'Disetujui',
        paid: 'Sudah bayar',
        confirmed: 'Dikonfirmasi',
        perlu_klarifikasi: 'Perlu klarifikasi',
        rejected: 'Ditolak',
        cancelled: 'Dibatalkan',
        forfeited: 'Hangus',
        expired: 'Kedaluwarsa',
        completed: 'Selesai',
    };

    return map[status] ?? status;
};

const statusTone = (status: string) => {
    const map: Record<string, string> = {
        menunggu_approval: 'bg-amber-100 text-amber-800',
        perlu_klarifikasi: 'bg-orange-100 text-orange-800',
        awaiting_payment: 'bg-sky-100 text-sky-800',
        approved: 'bg-emerald-100 text-emerald-800',
        paid: 'bg-emerald-100 text-emerald-800',
        confirmed: 'bg-emerald-100 text-emerald-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-slate-100 text-slate-600',
        expired: 'bg-slate-100 text-slate-600',
        forfeited: 'bg-red-100 text-red-800',
        completed: 'bg-slate-100 text-slate-700',
    };

    return map[status] ?? 'bg-slate-100 text-slate-700';
};

const statCards = (stats: DashboardStats) => [
    {
        key: 'pending',
        label: 'Perlu ditinjau',
        value: stats.pending_approval,
        hint: 'Pengajuan baru / klarifikasi',
        href: route('e-booking.admin.bookings.index', { tab: 'review' }),
        icon: FileSearch,
        tone: 'bg-amber-50 text-amber-700 ring-amber-100',
        accent: stats.pending_approval > 0 ? 'border-amber-200' : 'border-slate-200',
    },
    {
        key: 'pay',
        label: 'Menunggu bayar',
        value: stats.awaiting_payment,
        hint: 'Sudah disetujui, belum lunas',
        href: route('e-booking.admin.bookings.index', { tab: 'payment' }),
        icon: Wallet,
        tone: 'bg-sky-50 text-sky-700 ring-sky-100',
        accent: stats.awaiting_payment > 0 ? 'border-sky-200' : 'border-slate-200',
    },
    {
        key: 'verify',
        label: 'Bukti perlu dicek',
        value: stats.awaiting_verification,
        hint: 'Transfer menunggu verifikasi',
        href: route('e-booking.admin.bookings.index', { tab: 'verify' }),
        icon: ShieldCheck,
        tone: 'bg-violet-50 text-violet-700 ring-violet-100',
        accent: stats.awaiting_verification > 0 ? 'border-violet-200' : 'border-slate-200',
    },
    {
        key: 'today',
        label: 'Dikonfirmasi hari ini',
        value: stats.confirmed_today,
        hint: 'Pesanan yang sudah beres',
        href: route('e-booking.admin.bookings.index', { tab: 'all', status: 'confirmed' }),
        icon: BadgeCheck,
        tone: 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        accent: 'border-slate-200',
    },
];
</script>

<template>
    <SeoHead title="Ringkasan Pengelola" />

    <AdminLayout active="dashboard">
        <!-- Header -->
        <section
            class="overflow-hidden rounded-[28px] bg-[linear-gradient(135deg,#0f3d87_0%,#1479d1_55%,#2e7d32_120%)] p-6 text-white shadow-[0_18px_50px_rgba(20,121,209,0.22)] sm:p-8"
        >
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-sm font-medium text-white/75">{{ greeting }}, {{ bookingAuth?.name || 'Pengelola' }}</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Ringkasan kerja hari ini</h1>
                    <p class="mt-3 text-sm leading-6 text-white/85 sm:text-base">
                        Pantau pengajuan yang perlu ditindak, cek bukti bayar, lalu konfirmasi pesanan — semua dari satu
                        halaman.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('e-booking.admin.bookings.index')"
                        class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-sm transition hover:bg-slate-50"
                    >
                        Semua pengajuan
                        <ArrowRight class="size-4" />
                    </Link>
                    <Link
                        :href="route('e-booking.admin.closures.index')"
                        class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-white/25"
                    >
                        <CalendarOff class="size-4" />
                        Blok jadwal
                    </Link>
                </div>
            </div>
        </section>

        <Deferred :data="['stats', 'recent']">
            <template #fallback>
                <!-- Skeleton: attention + stats + list -->
                <div class="mt-6 space-y-6" aria-busy="true" aria-label="Memuat ringkasan">
                    <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-4">
                            <Skeleton class="size-12 rounded-2xl" />
                            <div class="flex-1 space-y-2">
                                <Skeleton class="h-4 w-40" />
                                <Skeleton class="h-3 w-64 max-w-full" />
                            </div>
                            <Skeleton class="hidden h-10 w-28 rounded-full sm:block" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div
                            v-for="n in 4"
                            :key="n"
                            class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-3">
                                    <Skeleton class="h-3 w-24" />
                                    <Skeleton class="h-9 w-14" />
                                    <Skeleton class="h-3 w-32" />
                                </div>
                                <Skeleton class="size-11 rounded-2xl" />
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                        <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="mb-5 flex items-center justify-between">
                                <Skeleton class="h-5 w-36" />
                                <Skeleton class="h-4 w-24" />
                            </div>
                            <div class="space-y-3">
                                <div
                                    v-for="n in 5"
                                    :key="n"
                                    class="rounded-2xl border border-slate-100 p-4"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-2">
                                            <Skeleton class="h-4 w-40" />
                                            <Skeleton class="h-3 w-56 max-w-full" />
                                            <Skeleton class="h-3 w-44" />
                                        </div>
                                        <div class="space-y-2 text-right">
                                            <Skeleton class="ml-auto h-6 w-24 rounded-full" />
                                            <Skeleton class="ml-auto h-4 w-20" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                <Skeleton class="mb-4 h-5 w-28" />
                                <div class="space-y-3">
                                    <Skeleton v-for="n in 3" :key="n" class="h-14 w-full rounded-2xl" />
                                </div>
                            </div>
                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                                <Skeleton class="mb-3 h-5 w-32" />
                                <Skeleton class="h-3 w-full" />
                                <Skeleton class="mt-2 h-3 w-4/5" />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-if="stats && recent">
                <div>
                <!-- Attention banner -->
                <div
                    v-if="stats.needs_attention > 0"
                    class="mt-6 flex flex-col gap-3 rounded-[24px] border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-3">
                        <div class="rounded-2xl bg-amber-100 p-2.5 text-amber-700">
                            <CircleAlert class="size-5" />
                        </div>
                        <div>
                            <p class="font-semibold text-amber-950">
                                {{ stats.needs_attention }} item perlu perhatian Anda
                            </p>
                            <p class="mt-0.5 text-sm text-amber-800/80">
                                Ada pengajuan untuk ditinjau atau bukti transfer yang menunggu dicek.
                            </p>
                        </div>
                    </div>
                    <Link
                        :href="route('e-booking.admin.bookings.index', { tab: 'review' })"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700"
                    >
                        Kerjakan sekarang
                        <ArrowRight class="size-4" />
                    </Link>
                </div>
                <div
                    v-else
                    class="mt-6 flex items-start gap-3 rounded-[24px] border border-emerald-200 bg-emerald-50 px-5 py-4"
                >
                    <div class="rounded-2xl bg-emerald-100 p-2.5 text-emerald-700">
                        <BadgeCheck class="size-5" />
                    </div>
                    <div>
                        <p class="font-semibold text-emerald-950">Antrian kritis kosong</p>
                        <p class="mt-0.5 text-sm text-emerald-800/80">
                            Tidak ada pengajuan mendesak. Anda bisa cek jadwal blokir atau pengaturan.
                        </p>
                    </div>
                </div>

                <!-- Stat cards -->
                <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Link
                        v-for="card in statCards(stats)"
                        :key="card.key"
                        :href="card.href"
                        class="group rounded-[24px] border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        :class="card.accent"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-500">{{ card.label }}</p>
                                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ card.hint }}</p>
                            </div>
                            <div class="rounded-2xl p-3 ring-1" :class="card.tone">
                                <component :is="card.icon" class="size-5" />
                            </div>
                        </div>
                        <p
                            class="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-sky-700 opacity-0 transition group-hover:opacity-100"
                        >
                            Lihat daftar
                            <ArrowRight class="size-3.5" />
                        </p>
                    </Link>
                </div>

                <div class="mt-6 grid gap-6 lg:grid-cols-[1.4fr_0.8fr]">
                    <!-- Recent queue -->
                    <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-5 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Antrian terbaru</h2>
                                <p class="mt-0.5 text-sm text-slate-500">Pengajuan yang masih perlu diproses</p>
                            </div>
                            <Link
                                :href="route('e-booking.admin.bookings.index')"
                                class="text-sm font-semibold text-sky-700 hover:underline"
                            >
                                Semua →
                            </Link>
                        </div>

                        <div
                            v-if="recent.length === 0"
                            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center"
                        >
                            <div class="rounded-2xl bg-white p-3 text-slate-400 shadow-sm">
                                <Clock3 class="size-6" />
                            </div>
                            <p class="mt-4 font-semibold text-slate-800">Belum ada antrian</p>
                            <p class="mt-1 max-w-sm text-sm text-slate-500">
                                Saat penyewa mengirim pengajuan baru, daftarnya akan muncul di sini.
                            </p>
                        </div>

                        <div v-else class="space-y-3">
                            <Link
                                v-for="item in recent"
                                :key="item.id"
                                :href="route('e-booking.admin.bookings.show', item.id)"
                                class="block rounded-2xl border border-slate-200 p-4 transition hover:border-sky-200 hover:bg-sky-50/40"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold text-slate-900">{{ item.nomor }}</p>
                                            <span
                                                class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                                :class="statusTone(item.status)"
                                            >
                                                {{ statusLabel(item.status) }}
                                            </span>
                                        </div>
                                        <p class="mt-1 truncate text-sm text-slate-600">
                                            {{ item.penyewa || 'Penyewa' }} · {{ item.venue || 'Tempat' }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ formatSchedule(item.starts_at, item.ends_at) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-900">{{ formatRp(item.grand_total) }}</p>
                                        <p class="mt-1 text-xs font-medium text-sky-700">Buka detail →</p>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </section>

                    <!-- Side: shortcuts + tips -->
                    <aside class="space-y-4">
                        <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-bold text-slate-900">Aksi cepat</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Jalan pintas yang sering dipakai</p>

                            <div class="mt-4 space-y-2">
                                <Link
                                    :href="route('e-booking.admin.bookings.index')"
                                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm transition hover:bg-sky-50"
                                >
                                    <span class="rounded-xl bg-amber-100 p-2 text-amber-700">
                                        <FileSearch class="size-4" />
                                    </span>
                                    <span class="flex-1 font-medium text-slate-800">Tinjau pengajuan</span>
                                    <ArrowRight class="size-4 text-slate-400" />
                                </Link>
                                <Link
                                    :href="route('e-booking.admin.closures.index')"
                                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm transition hover:bg-sky-50"
                                >
                                    <span class="rounded-xl bg-rose-100 p-2 text-rose-700">
                                        <CalendarOff class="size-4" />
                                    </span>
                                    <span class="flex-1 font-medium text-slate-800">Blok / buka jadwal</span>
                                    <ArrowRight class="size-4 text-slate-400" />
                                </Link>
                                <Link
                                    :href="route('e-booking.admin.settings')"
                                    class="flex items-center gap-3 rounded-2xl bg-slate-50 px-4 py-3 text-sm transition hover:bg-sky-50"
                                >
                                    <span class="rounded-xl bg-slate-200 p-2 text-slate-700">
                                        <Settings2 class="size-4" />
                                    </span>
                                    <span class="flex-1 font-medium text-slate-800">Pengaturan & rekening</span>
                                    <ArrowRight class="size-4 text-slate-400" />
                                </Link>
                            </div>
                        </section>

                        <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
                            <h2 class="text-lg font-bold text-slate-900">Tips kerja cepat</h2>
                            <ul class="mt-3 space-y-3 text-sm leading-6 text-slate-600">
                                <li class="flex gap-2">
                                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-amber-500" />
                                    Prioritaskan kartu <strong class="font-semibold text-slate-800">Perlu ditinjau</strong> dan
                                    <strong class="font-semibold text-slate-800">Bukti perlu dicek</strong>.
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-sky-500" />
                                    Blok jadwal lebih dulu jika ada maintenance atau event internal.
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-1 size-1.5 shrink-0 rounded-full bg-emerald-500" />
                                    Setelah bukti valid, verifikasi agar penyewa mendapat konfirmasi.
                                </li>
                            </ul>
                        </section>
                    </aside>
                </div>
                </div>
            </template>
        </Deferred>
    </AdminLayout>
</template>
