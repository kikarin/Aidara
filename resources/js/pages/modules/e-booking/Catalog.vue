<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import SeoHead from '@/components/SeoHead.vue';
import { Skeleton } from '@/components/ui/skeleton';
import EBookingLayout from '@/layouts/e-booking/EBookingLayout.vue';
import { buildWebPageSchema } from '@/lib/schema';
import { SEO_DEFAULT_KEYWORDS } from '@/lib/seo';
import type { BookingVenueSummary } from '@/types/booking';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, MapPin, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    venues?: BookingVenueSummary[];
    branding: string;
}>();

const page = usePage();
const pageUrl = computed(() => route('e-booking.catalog', undefined, true));
const siteBaseUrl = computed(() => {
    const ziggy = page.props.ziggy as { url?: string } | undefined;

    return (ziggy?.url ?? '').replace(/\/$/, '');
});

const description =
    'Cari lapangan dan fasilitas olahraga UPT Dispora Kabupaten Bogor. Cek jadwal, lihat harga, lalu kirim pengajuan sewa dengan mudah.';

const pageSchema = computed(() => [
    buildWebPageSchema({
        baseUrl: siteBaseUrl.value,
        name: `${props.branding} — AIDARA`,
        description,
        url: pageUrl.value,
    }),
]);
</script>

<template>
    <SeoHead
        :title="branding"
        :description="description"
        :keywords="SEO_DEFAULT_KEYWORDS"
        :canonical="pageUrl"
        :schema="pageSchema"
    />

    <EBookingLayout active="catalog">
        <section class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-[32px] bg-[linear-gradient(135deg,#0f3d87_0%,#1479d1_58%,#44b6ff_100%)] p-7 text-white shadow-[0_20px_60px_rgba(20,121,209,0.25)] sm:p-9">
                <p class="text-sm font-semibold text-white/80">Sewa fasilitas olahraga tanpa ribet</p>
                <h1 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">
                    Pilih tempat, cek jadwal, lalu kirim pengajuan dalam beberapa langkah.
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-6 text-white/85 sm:text-base">
                    Cocok untuk latihan, pertandingan, kegiatan komunitas, atau pemakaian fasilitas lainnya. Semua
                    proses bisa dimulai dari sini.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <span class="rounded-full bg-white/15 px-4 py-2 text-sm">Tanpa perlu datang dulu</span>
                    <span class="rounded-full bg-white/15 px-4 py-2 text-sm">Harga terlihat lebih jelas</span>
                    <span class="rounded-full bg-white/15 px-4 py-2 text-sm">Status pengajuan mudah dipantau</span>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-2xl bg-sky-100 p-3 text-sky-700">
                            <MapPin class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900">1. Pilih tempat</h2>
                            <p class="mt-1 text-sm text-slate-600">Lihat daftar lapangan atau fasilitas yang tersedia.</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-2xl bg-emerald-100 p-3 text-emerald-700">
                            <CalendarDays class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900">2. Cek jadwal dan biaya</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                Masukkan waktu yang diinginkan untuk melihat ketersediaan dan perkiraan biaya.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="rounded-2xl bg-amber-100 p-3 text-amber-700">
                            <ShieldCheck class="size-5" />
                        </div>
                        <div>
                            <h2 class="font-semibold text-slate-900">3. Kirim pengajuan</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                Setelah cocok, kirim pengajuan dan pantau prosesnya dari akun Anda.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-10">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-sky-700">Daftar tempat</p>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Pilih fasilitas yang ingin dipakai</h2>
                </div>
                <p v-if="venues" class="text-sm text-slate-500">{{ venues.length }} tempat tersedia</p>
                <Skeleton v-else class="h-4 w-28" />
            </div>

            <Deferred data="venues">
                <template #fallback>
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3" aria-busy="true" aria-label="Memuat daftar tempat">
                        <div
                            v-for="n in 6"
                            :key="n"
                            class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm"
                        >
                            <Skeleton class="aspect-[16/10] w-full rounded-none" />
                            <div class="space-y-3 p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <Skeleton class="h-5 w-2/3" />
                                    <Skeleton class="h-6 w-16 rounded-full" />
                                </div>
                                <Skeleton class="h-4 w-full" />
                                <Skeleton class="h-4 w-4/5" />
                                <div class="flex items-center justify-between pt-1">
                                    <Skeleton class="h-3 w-28" />
                                    <Skeleton class="h-4 w-20" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-if="venues && venues.length > 0" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="venue in venues"
                        :key="venue.id"
                        :href="route('e-booking.venues.show', venue.id)"
                        class="group block overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-[0_18px_50px_rgba(15,23,42,0.12)]"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-100">
                            <AppImage
                                v-if="venue.cover_url"
                                :src="venue.cover_url"
                                :alt="venue.name"
                                class="size-full object-cover transition duration-300 group-hover:scale-[1.03]"
                            />
                            <div v-else class="flex size-full items-center justify-center text-sm text-slate-500">
                                Foto belum tersedia
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-semibold leading-snug text-slate-900">{{ venue.name }}</h3>
                                <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                                    {{ venue.areas_count }} area
                                </span>
                            </div>
                            <p v-if="venue.description" class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">
                                {{ venue.description }}
                            </p>
                            <div class="mt-4 flex items-center justify-between">
                                <p class="flex items-center gap-1.5 text-xs text-slate-500">
                                    <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                                    Siap dicek jadwalnya
                                </p>
                                <p class="text-sm font-semibold text-[var(--brand-green,#2e7d32)]">Lihat detail</p>
                            </div>
                        </div>
                    </Link>
                </div>

                <div
                    v-else-if="venues"
                    class="rounded-[28px] border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm"
                >
                    <p class="text-sm text-slate-500">Belum ada tempat aktif yang bisa dipilih.</p>
                </div>
            </Deferred>
        </section>
    </EBookingLayout>
</template>
