<script setup lang="ts">
import AppImage from '@/components/AppImage.vue';
import SeoHead from '@/components/SeoHead.vue';
import SiBolaIllustration from '@/components/sibola/SiBolaIllustration.vue';
import SiBolaMark from '@/components/sibola/SiBolaMark.vue';
import { Skeleton } from '@/components/ui/skeleton';
import WorldCupPreviewSection from '@/components/worldcup/WorldCupPreviewSection.vue';
import PublicSiteLayout from '@/layouts/PublicSiteLayout.vue';
import { trackSpotlight, vReveal } from '@/lib/publicMotion';
import { buildFaqSchema, buildOrganizationSchema, buildWebPageSchema, buildWebSiteSchema } from '@/lib/schema';
import { SEO_DEFAULT_KEYWORDS, SEO_HOME_DESCRIPTION, SEO_WORLDCUP_HOME_SNIPPET, SEO_WORLDCUP_KEYWORDS } from '@/lib/seo';
import type { BookingVenueSummary } from '@/types/booking';
import type { WorldCupPreview, WorldCupSettings } from '@/types/worldcup';
import { library } from '@fortawesome/fontawesome-svg-core';
import {
    faArrowRight,
    faBullhorn,
    faCalendarCheck,
    faChartLine,
    faCircleCheck,
    faClipboardList,
    faClock,
    faEnvelopeOpenText,
    faFileSignature,
    faFingerprint,
    faHeartPulse,
    faLayerGroup,
    faMapLocationDot,
    faMedal,
    faPeopleGroup,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Deferred, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

library.add(
    faArrowRight,
    faBullhorn,
    faCalendarCheck,
    faChartLine,
    faCircleCheck,
    faClipboardList,
    faClock,
    faEnvelopeOpenText,
    faFileSignature,
    faFingerprint,
    faHeartPulse,
    faLayerGroup,
    faMapLocationDot,
    faMedal,
    faPeopleGroup,
    faUsers,
);

const SIBOLA_KEYWORDS = 'Si Bola, booking lapangan Kabupaten Bogor, sewa venue olahraga Bogor, sewa lapangan UPT Dispora';

const siBolaPoints = [
    'Tarif resmi untuk instansi pemerintah dan umum ditampilkan terbuka.',
    'Slot yang sudah terisi langsung terlihat, jadi tidak perlu tanya bolak-balik.',
    'Pengajuan, pembayaran, sampai surat izin dipantau dari satu akun.',
];

const siBolaSteps = [
    {
        icon: 'map-location-dot',
        title: 'Pilih venue',
        text: 'Lihat lapangan dan fasilitas olahraga milik UPT Dispora beserta area yang bisa disewa.',
    },
    {
        icon: 'clock',
        title: 'Cek slot & biaya',
        text: 'Pilih tanggal dan jam untuk melihat ketersediaan serta perkiraan biaya sebelum mengajukan.',
    },
    {
        icon: 'file-signature',
        title: 'Kirim pengajuan',
        text: 'Lengkapi data penyewa dan dokumen pendukung, lalu kirim dari akun Si Bola.',
    },
    {
        icon: 'envelope-open-text',
        title: 'Terima surat izin',
        text: 'Setelah disetujui dan pembayaran terverifikasi, surat izin pemakaian bisa langsung diunduh.',
    },
];

const keunggulan = [
    {
        icon: 'layer-group',
        title: 'Data terpusat',
        text: 'Semua data cabang olahraga, atlet, pelatih, dan tenaga pendukung tersimpan dalam satu sistem terintegrasi.',
    },
    {
        icon: 'users',
        title: 'Kolaborasi tim',
        text: 'Admin, pelatih, dan tenaga pendukung bekerja bersama melalui portal yang aman dan terstruktur per cabang olahraga.',
    },
    {
        icon: 'fingerprint',
        title: 'Akuntabel & transparan',
        text: 'Laporan statistik dan riwayat pemeriksaan kesehatan mendukung pengambilan keputusan berbasis data.',
    },
];

const caraKerja = [
    {
        step: '01',
        title: 'Daftar & verifikasi',
        text: 'Buat akun dan lengkapi proses verifikasi untuk mengakses portal AIDARA.',
    },
    {
        step: '02',
        title: 'Kelola cabang olahraga',
        text: 'Atur data cabang olahraga beserta atlet, pelatih, dan tenaga pendukung di dalam sistem.',
    },
    {
        step: '03',
        title: 'Jalankan program',
        text: 'Catat program latihan, pemeriksaan kesehatan, dan kegiatan event secara terstruktur.',
    },
    {
        step: '04',
        title: 'Pantau laporan',
        text: 'Akses statistik dan rekap data untuk evaluasi kinerja olahraga Kabupaten Bogor.',
    },
];

type Fitur = {
    icon: string;
    title: string;
    text: string;
    span: string;
    tags?: string[];
    route?: string;
    cta?: string;
};

const fitur: Fitur[] = [
    {
        icon: 'people-group',
        title: 'Manajemen peserta',
        text: 'Kelola data atlet, pelatih, dan tenaga pendukung dalam satu dashboard — lengkap dengan status verifikasi dan riwayatnya.',
        span: 'lg:col-span-4',
        tags: ['Atlet', 'Pelatih', 'Tenaga pendukung'],
    },
    {
        icon: 'clipboard-list',
        title: 'Program latihan',
        text: 'Rencanakan dan pantau program latihan beserta absensi peserta.',
        span: 'lg:col-span-2',
    },
    {
        icon: 'heart-pulse',
        title: 'Pemeriksaan kesehatan',
        text: 'Catat riwayat pemeriksaan dan parameter kesehatan peserta olahraga.',
        span: 'lg:col-span-2',
    },
    {
        icon: 'chart-line',
        title: 'Laporan statistik',
        text: 'Lihat rekap data dan statistik untuk mendukung evaluasi kebijakan olahraga.',
        span: 'lg:col-span-2',
    },
    {
        icon: 'medal',
        title: 'Prestasi & turnamen',
        text: 'Dokumentasikan prestasi atlet dan kelola data turnamen olahraga.',
        span: 'lg:col-span-2',
    },
    {
        icon: 'bullhorn',
        title: 'Event publik',
        text: 'Informasi kegiatan olahraga dapat diakses masyarakat melalui halaman event.',
        span: 'lg:col-span-3',
        route: 'event.public.index',
        cta: 'Lihat event',
    },
    {
        icon: 'calendar-check',
        title: 'Si Bola — booking venue',
        text: 'Sewa lapangan dan fasilitas olahraga UPT Dispora secara online — jadwal, persetujuan, dan pembayaran.',
        span: 'lg:col-span-3',
        route: 'e-booking.catalog',
        cta: 'Buka Si Bola',
    },
];

const faq = [
    {
        question: 'Apa itu AIDARA?',
        answer: 'AIDARA (Aplikasi Informasi Data Olahraga) adalah sistem terpadu milik Dispora Kabupaten Bogor untuk pengelolaan data cabang olahraga, atlet, pelatih, dan tenaga pendukung.',
    },
    {
        question: 'Siapa yang dapat menggunakan AIDARA?',
        answer: 'AIDARA digunakan oleh admin Dispora, pelatih, tenaga pendukung, dan pihak terkait yang telah terdaftar dan terverifikasi. Masyarakat dapat mengakses informasi event publik dan booking venue lewat Si Bola tanpa masuk dashboard Dispora.',
    },
    {
        question: 'Bagaimana cara booking venue olahraga milik Pemkab Bogor?',
        answer: 'Gunakan Si Bola, layanan booking venue di AIDARA. Pilih venue yang dikelola UPT Dispora, cek slot dan perkiraan biaya, lalu kirim pengajuan dari akun Si Bola. Persetujuan, pembayaran, dan surat izin bisa dipantau secara online.',
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

const heroSessions = [
    { cabor: 'Pencak silat', pelatih: 'Yusuf Hidayat', hadir: 18, total: 21 },
    { cabor: 'Bulu tangkis', pelatih: 'Dewi Anggraeni', hadir: 12, total: 14 },
    { cabor: 'Atletik', pelatih: 'Rizky Pratama', hadir: 23, total: 27 },
];

const heroAttendance = computed(() => {
    const hadir = heroSessions.reduce((sum, s) => sum + s.hadir, 0);
    const total = heroSessions.reduce((sum, s) => sum + s.total, 0);

    return ((hadir / total) * 100).toFixed(1).replace('.', ',');
});

defineProps<{
    worldcupPreview?: WorldCupPreview;
    bookingPreview?: {
        total: number;
        venues: BookingVenueSummary[];
    };
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

const showWorldCupSection = () => Boolean(page.props.worldcup?.enabled && page.props.worldcup?.show_on_landing);

const homeDescription = computed(() => (showWorldCupSection() ? `${SEO_HOME_DESCRIPTION} ${SEO_WORLDCUP_HOME_SNIPPET}` : SEO_HOME_DESCRIPTION));

const homeKeywords = computed(() =>
    showWorldCupSection() ? `${SEO_DEFAULT_KEYWORDS}, ${SIBOLA_KEYWORDS}, ${SEO_WORLDCUP_KEYWORDS}` : `${SEO_DEFAULT_KEYWORDS}, ${SIBOLA_KEYWORDS}`,
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

const navSections = computed(() => [
    { id: 'si-bola', label: 'Si Bola' },
    ...(showWorldCupSection() ? [{ id: 'piala-dunia', label: 'Piala Dunia' }] : []),
    { id: 'keunggulan', label: 'Keunggulan' },
    { id: 'cara-kerja', label: 'Cara kerja' },
    { id: 'fitur', label: 'Fitur' },
    { id: 'faq', label: 'FAQ' },
]);

const activeSection = ref<string | null>(null);

let sectionObserver: IntersectionObserver | null = null;

onMounted(async () => {
    await nextTick();
    sectionObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-40% 0px -55% 0px' },
    );

    navSections.value.forEach(({ id }) => {
        const el = document.getElementById(id);
        if (el) {
            sectionObserver?.observe(el);
        }
    });
});

onUnmounted(() => {
    sectionObserver?.disconnect();
});
</script>

<template>
    <SeoHead
        title="AIDARA — Aplikasi Informasi Data Olahraga Dispora Kabupaten Bogor"
        :description="homeDescription"
        :keywords="homeKeywords"
        :canonical="homeUrl"
        :schema="homeSchema"
    />

    <PublicSiteLayout current="home" :sections="navSections" :active-section="activeSection">
        <section id="beranda" class="relative isolate">
            <div class="wp-hero-bg absolute inset-0 -z-10" aria-hidden="true"></div>

            <div class="mx-auto grid max-w-7xl items-center gap-16 px-6 pt-32 pb-24 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:pt-44 lg:pb-36">
                <div class="lg:col-span-7">
                    <p class="wp-enter wp-glass inline-flex items-center gap-3 rounded-full py-1.5 pr-4 pl-1.5 text-sm font-medium">
                        <span class="bg-card flex items-center gap-1.5 rounded-full px-2 py-1">
                            <AppImage src="/kabupaten_bogor.webp" alt="" class="size-5 object-contain" :lazy="false" />
                            <AppImage src="/Logo.svg" alt="" class="size-5 object-contain" :lazy="false" />
                        </span>
                        Platform resmi Dispora Kabupaten Bogor
                    </p>

                    <h1
                        class="wp-enter mt-8 text-[clamp(2.75rem,6.4vw,5.5rem)] leading-[0.98] font-bold tracking-[-0.045em] text-balance"
                        :style="{ '--enter-delay': '80ms' }"
                    >
                        Data olahraga daerah, rapi dalam
                        <span class="text-(--wp-accent)">satu tempat.</span>
                    </h1>

                    <p
                        class="wp-enter text-muted-foreground mt-7 max-w-[58ch] text-lg leading-relaxed text-pretty"
                        :style="{ '--enter-delay': '160ms' }"
                    >
                        AIDARA — Aplikasi Informasi Data Olahraga — menyatukan data cabang olahraga, atlet, pelatih, dan tenaga pendukung, dari
                        program latihan sampai pemeriksaan kesehatan.
                    </p>

                    <div class="wp-enter mt-10 flex flex-wrap items-center gap-x-7 gap-y-4" :style="{ '--enter-delay': '240ms' }">
                        <Link v-if="!isLoggedIn" :href="route('register')" class="wp-btn wp-btn-primary px-7 py-3.5 text-[0.95rem]">
                            Mulai sekarang
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                        </Link>
                        <Link v-else :href="route('dashboard')" class="wp-btn wp-btn-primary px-7 py-3.5 text-[0.95rem]">
                            Ke dasbor
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                        </Link>
                        <a href="#fitur" class="wp-link-arrow inline-flex items-center gap-2 text-[0.95rem] font-semibold">
                            Jelajahi fitur
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5 text-(--wp-accent)" />
                        </a>
                    </div>

                    <div class="wp-enter mt-12" :style="{ '--enter-delay': '320ms' }">
                        <a
                            href="#si-bola"
                            class="wp-glass wp-tile group flex max-w-md items-center gap-4 rounded-2xl py-3 pr-5 pl-3"
                            @pointermove="trackSpotlight"
                        >
                            <SiBolaMark class="size-11" />
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold">Mau sewa lapangan? Pakai Si Bola.</span>
                                <span class="text-muted-foreground block text-xs leading-relaxed">
                                    Booking venue olahraga milik UPT Dispora Kabupaten Bogor secara online.
                                </span>
                            </span>
                            <span class="wp-link-arrow">
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5 text-(--wp-accent)" />
                            </span>
                        </a>
                    </div>
                </div>

                <figure class="wp-enter relative lg:col-span-5" :style="{ '--enter-delay': '200ms' }">
                    <div class="relative mx-auto max-w-md lg:mr-0" aria-hidden="true">
                        <div class="wp-glass rounded-[1.75rem] p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-muted-foreground text-xs font-medium">Program latihan</p>
                                    <p class="mt-1 text-lg font-semibold tracking-[-0.01em]">Sesi pekan ini</p>
                                </div>
                                <span class="wp-icon size-10">
                                    <FontAwesomeIcon :icon="['fas', 'clipboard-list']" class="size-4" />
                                </span>
                            </div>

                            <ul class="mt-6 space-y-5">
                                <li v-for="s in heroSessions" :key="s.cabor">
                                    <div class="flex items-baseline justify-between gap-4 text-sm">
                                        <span class="font-semibold">{{ s.cabor }}</span>
                                        <span class="text-muted-foreground tabular-nums">{{ s.hadir }}/{{ s.total }} hadir</span>
                                    </div>
                                    <p class="text-muted-foreground mt-0.5 text-xs">Pelatih: {{ s.pelatih }}</p>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-(--wp-accent-soft)">
                                        <div class="h-full rounded-full bg-(--wp-accent)" :style="{ width: `${(s.hadir / s.total) * 100}%` }"></div>
                                    </div>
                                </li>
                            </ul>

                            <div class="mt-6 flex items-end justify-between border-t border-(--wp-hairline) pt-5">
                                <p class="text-muted-foreground text-xs">Rata-rata kehadiran</p>
                                <p class="text-3xl font-bold tracking-[-0.03em] tabular-nums">{{ heroAttendance }}%</p>
                            </div>
                        </div>

                        <div class="wp-glass absolute -bottom-10 -left-4 w-60 rounded-2xl p-4 sm:-left-12">
                            <div class="flex items-center gap-3">
                                <span class="wp-icon size-9 rounded-xl">
                                    <FontAwesomeIcon :icon="['fas', 'heart-pulse']" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold">Nadia Kusuma</p>
                                    <p class="text-muted-foreground text-xs">Renang · cek kesehatan</p>
                                </div>
                            </div>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <dt class="text-muted-foreground">Denyut istirahat</dt>
                                    <dd class="mt-0.5 text-base font-semibold tabular-nums">58 bpm</dd>
                                </div>
                                <div>
                                    <dt class="text-muted-foreground">VO₂max</dt>
                                    <dd class="mt-0.5 text-base font-semibold tabular-nums">47,3</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                    <figcaption class="text-muted-foreground mt-16 text-center text-xs lg:text-right">Ilustrasi tampilan dasbor AIDARA</figcaption>
                </figure>
            </div>
        </section>

        <section id="si-bola" class="mt-16 scroll-mt-24 px-3 sm:mt-20 lg:mt-28 lg:px-6" aria-labelledby="si-bola-heading">
            <div class="relative isolate mx-auto max-w-[88rem] overflow-hidden rounded-[2.5rem] bg-(--wp-accent-soft) ring-1 ring-(--wp-hairline)">
                <div class="wp-hero-bg absolute inset-0 -z-10" aria-hidden="true"></div>

                <div class="mx-auto max-w-7xl px-6 pt-20 pb-20 lg:px-10 lg:pt-28 lg:pb-24">
                    <div class="grid items-center gap-16 lg:grid-cols-12 lg:gap-10">
                        <div v-reveal class="lg:col-span-6">
                            <div class="flex items-center gap-3">
                                <SiBolaMark class="size-12" />
                                <div class="leading-none">
                                    <p class="text-2xl font-bold tracking-[-0.03em]">Si Bola</p>
                                    <p class="text-muted-foreground mt-1.5 text-xs font-medium">Booking venue · UPT Dispora Kab. Bogor</p>
                                </div>
                            </div>

                            <h2 id="si-bola-heading" class="mt-8 text-4xl leading-[1.04] font-bold tracking-[-0.04em] text-balance lg:text-[3.4rem]">
                                Sewa lapangan milik Pemkab Bogor,
                                <span class="text-(--wp-accent)">tanpa antre di kantor.</span>
                            </h2>
                            <p class="text-muted-foreground mt-6 max-w-[54ch] text-lg leading-relaxed text-pretty">
                                Si Bola adalah layanan booking venue olahraga di AIDARA yang dikelola langsung oleh UPT Dispora Kabupaten Bogor. Untuk
                                latihan rutin, sparing antarklub, sampai turnamen komunitas.
                            </p>

                            <ul class="mt-8 space-y-3">
                                <li v-for="point in siBolaPoints" :key="point" class="flex items-start gap-3 text-[0.95rem]">
                                    <FontAwesomeIcon :icon="['fas', 'circle-check']" class="mt-1 size-4 shrink-0 text-(--wp-accent)" />
                                    <span>{{ point }}</span>
                                </li>
                            </ul>

                            <div class="mt-10 flex flex-wrap items-center gap-x-7 gap-y-4">
                                <Link :href="route('e-booking.catalog')" class="wp-btn wp-btn-primary px-7 py-3.5 text-[0.95rem]">
                                    Cari venue di Si Bola
                                    <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                                </Link>
                                <Link
                                    :href="route('e-booking.login')"
                                    class="text-foreground text-[0.95rem] font-semibold decoration-(--wp-accent) underline-offset-4 hover:underline"
                                >
                                    Cek status pengajuan
                                </Link>
                            </div>
                        </div>

                        <figure v-reveal="120" class="lg:col-span-6">
                            <SiBolaIllustration />
                            <figcaption class="text-muted-foreground mt-4 text-center text-xs">Ilustrasi alur booking di Si Bola</figcaption>
                        </figure>
                    </div>

                    <ol class="mt-20 grid gap-4 sm:grid-cols-2 lg:mt-24 lg:grid-cols-4">
                        <li
                            v-for="(step, index) in siBolaSteps"
                            :key="step.title"
                            v-reveal="index * 90"
                            class="wp-surface flex flex-col rounded-3xl p-6"
                        >
                            <div class="flex items-center justify-between">
                                <span class="wp-icon size-11">
                                    <FontAwesomeIcon :icon="['fas', step.icon]" class="size-4.5" />
                                </span>
                                <span class="text-muted-foreground font-mono text-sm tabular-nums">0{{ index + 1 }}</span>
                            </div>
                            <h3 class="mt-6 text-lg font-semibold tracking-[-0.015em]">{{ step.title }}</h3>
                            <p class="text-muted-foreground mt-2 text-sm leading-relaxed">{{ step.text }}</p>
                        </li>
                    </ol>

                    <div class="mt-20 lg:mt-24">
                        <div v-reveal class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <h3 class="text-2xl font-bold tracking-[-0.025em] lg:text-3xl">Venue yang bisa dibooking</h3>
                                <div class="text-muted-foreground mt-2 text-sm">
                                    <template v-if="bookingPreview">
                                        {{ bookingPreview.total }} venue aktif dikelola UPT Dispora Kabupaten Bogor
                                    </template>
                                    <Skeleton v-else class="h-4 w-64" />
                                </div>
                            </div>
                            <Link
                                :href="route('e-booking.catalog')"
                                class="wp-link-arrow inline-flex shrink-0 items-center gap-2 text-sm font-semibold"
                            >
                                Lihat semua venue
                                <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3 text-(--wp-accent)" />
                            </Link>
                        </div>

                        <Deferred data="bookingPreview">
                            <template #fallback>
                                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-busy="true" aria-label="Memuat daftar venue">
                                    <div v-for="n in 4" :key="n" class="wp-surface overflow-hidden rounded-3xl p-2">
                                        <Skeleton class="aspect-[4/3] w-full rounded-2xl" />
                                        <div class="space-y-2.5 p-4">
                                            <Skeleton class="h-5 w-3/4" />
                                            <Skeleton class="h-3.5 w-1/3" />
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="bookingPreview && bookingPreview.venues.length > 0" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <Link
                                    v-for="venue in bookingPreview.venues"
                                    :key="venue.id"
                                    :href="route('e-booking.venues.show', venue.id)"
                                    class="wp-surface wp-tile group flex flex-col rounded-3xl p-2"
                                    @pointermove="trackSpotlight"
                                >
                                    <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-(--wp-accent-soft)">
                                        <AppImage
                                            v-if="venue.cover_url"
                                            :src="venue.cover_url"
                                            :alt="`Foto ${venue.name}`"
                                            class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]"
                                        />
                                        <svg
                                            v-else
                                            viewBox="0 0 160 120"
                                            class="size-full text-(--wp-accent) opacity-60"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.5"
                                            aria-hidden="true"
                                        >
                                            <rect x="20" y="20" width="120" height="80" rx="3" />
                                            <line x1="80" y1="20" x2="80" y2="100" />
                                            <circle cx="80" cy="60" r="14" />
                                            <rect x="20" y="42" width="18" height="36" />
                                            <rect x="122" y="42" width="18" height="36" />
                                        </svg>
                                        <span
                                            class="bg-background/85 absolute top-3 left-3 rounded-md px-2 py-1 text-xs font-semibold tabular-nums backdrop-blur"
                                        >
                                            {{ venue.areas_count }} area
                                        </span>
                                    </div>
                                    <div class="flex flex-1 flex-col p-4">
                                        <h4 class="leading-snug font-semibold tracking-[-0.01em]">{{ venue.name }}</h4>
                                        <p v-if="venue.description" class="text-muted-foreground mt-1.5 line-clamp-2 text-sm leading-relaxed">
                                            {{ venue.description }}
                                        </p>
                                        <span class="wp-link-arrow mt-auto inline-flex items-center gap-2 pt-4 text-sm font-semibold">
                                            Cek jadwal
                                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3 text-(--wp-accent)" />
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <div
                                v-else-if="bookingPreview"
                                class="wp-surface mt-8 flex flex-col items-start gap-5 rounded-3xl p-8 sm:flex-row sm:items-center"
                            >
                                <SiBolaMark class="size-12" />
                                <div class="flex-1">
                                    <p class="font-semibold">Daftar venue sedang disiapkan UPT Dispora.</p>
                                    <p class="text-muted-foreground mt-1 text-sm">
                                        Untuk info sewa sementara, hubungi
                                        <a href="tel:+622517503524" class="text-foreground font-medium tabular-nums underline underline-offset-4">
                                            (0251) 7503524</a
                                        >.
                                    </p>
                                </div>
                            </div>
                        </Deferred>
                    </div>
                </div>
            </div>
        </section>

        <WorldCupPreviewSection
            v-if="showWorldCupSection()"
            :matches="worldcupPreview?.matches ?? []"
            :title="page.props.worldcup?.section_title ?? 'Piala Dunia 2026'"
        />

        <section id="keunggulan" class="scroll-mt-20" aria-labelledby="keunggulan-heading">
            <div class="mx-auto grid max-w-7xl gap-14 px-6 pt-24 pb-28 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:pt-32 lg:pb-40">
                <div v-reveal class="lg:sticky lg:top-32 lg:col-span-5 lg:self-start">
                    <p class="wp-eyebrow">Mengapa AIDARA</p>
                    <h2 id="keunggulan-heading" class="mt-5 text-4xl leading-[1.05] font-bold tracking-[-0.035em] text-balance lg:text-5xl">
                        Satu sumber data untuk seluruh ekosistem olahraga.
                    </h2>
                    <p class="text-muted-foreground mt-6 max-w-[48ch] text-lg leading-relaxed">
                        Solusi digital resmi Dispora Kabupaten Bogor untuk pengelolaan data olahraga yang efisien.
                    </p>
                </div>

                <ol class="divide-y divide-(--wp-hairline) lg:col-span-6 lg:col-start-7">
                    <li
                        v-for="(item, index) in keunggulan"
                        :key="item.title"
                        v-reveal="index * 90"
                        class="grid grid-cols-[auto_1fr] gap-x-6 py-10 first:pt-0 last:pb-0"
                    >
                        <span class="pt-1 font-mono text-sm text-(--wp-accent) tabular-nums">0{{ index + 1 }}</span>
                        <div>
                            <div class="flex items-center gap-4">
                                <span class="wp-icon size-11">
                                    <FontAwesomeIcon :icon="['fas', item.icon]" class="size-4.5" />
                                </span>
                                <h3 class="text-2xl font-semibold tracking-[-0.02em]">{{ item.title }}</h3>
                            </div>
                            <p class="text-muted-foreground mt-4 max-w-[52ch] leading-relaxed">{{ item.text }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <section id="cara-kerja" class="scroll-mt-20 px-3 lg:px-6" aria-labelledby="cara-kerja-heading">
            <div class="bg-muted/55 relative mx-auto max-w-[88rem] overflow-hidden rounded-[2.5rem]">
                <div class="relative mx-auto max-w-7xl px-6 pt-20 pb-24 lg:px-10 lg:pt-28 lg:pb-32">
                    <div v-reveal class="grid gap-6 lg:grid-cols-12 lg:items-end">
                        <div class="lg:col-span-7">
                            <p class="wp-eyebrow">Alur penggunaan</p>
                            <h2 id="cara-kerja-heading" class="mt-5 text-4xl leading-[1.05] font-bold tracking-[-0.035em] text-balance lg:text-5xl">
                                Dari pendaftaran sampai laporan, dalam empat langkah.
                            </h2>
                        </div>
                        <p class="text-muted-foreground max-w-[44ch] text-lg leading-relaxed lg:col-span-4 lg:col-start-9">
                            Mulai mengelola data olahraga di Kabupaten Bogor tanpa proses yang berbelit.
                        </p>
                    </div>

                    <ol class="mt-16 grid gap-10 sm:grid-cols-2 lg:mt-20 lg:grid-cols-4 lg:gap-8">
                        <li
                            v-for="(item, index) in caraKerja"
                            :key="item.step"
                            v-reveal="index * 110"
                            class="relative border-t border-(--wp-hairline) pt-8"
                        >
                            <span
                                class="ring-muted absolute -top-[5px] left-0 size-2.5 rounded-full bg-(--wp-accent) ring-4"
                                aria-hidden="true"
                            ></span>
                            <span class="text-muted-foreground font-mono text-sm tabular-nums">Langkah {{ item.step }}</span>
                            <h3 class="mt-3 text-xl font-semibold tracking-[-0.015em]">{{ item.title }}</h3>
                            <p class="text-muted-foreground mt-3 text-[0.95rem] leading-relaxed">{{ item.text }}</p>
                        </li>
                    </ol>
                </div>
            </div>
        </section>

        <section id="fitur" class="scroll-mt-20" aria-labelledby="fitur-heading">
            <div class="mx-auto max-w-7xl px-6 pt-28 pb-28 lg:px-8 lg:pt-36 lg:pb-36">
                <div v-reveal class="max-w-3xl">
                    <p class="wp-eyebrow">Modul sistem</p>
                    <h2 id="fitur-heading" class="mt-5 text-4xl leading-[1.05] font-bold tracking-[-0.035em] text-balance lg:text-5xl">
                        Semua kebutuhan pengelolaan data olahraga, terhubung.
                    </h2>
                    <p class="text-muted-foreground mt-6 max-w-[56ch] text-lg leading-relaxed">
                        Setiap modul berbagi data yang sama, jadi Anda cukup mencatat sekali.
                    </p>
                </div>

                <div class="mt-16 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                    <component
                        :is="item.route ? Link : 'article'"
                        v-for="(item, index) in fitur"
                        :key="item.title"
                        v-reveal="(index % 3) * 80"
                        :href="item.route ? route(item.route) : undefined"
                        class="wp-surface wp-tile group flex flex-col p-7 lg:p-8"
                        :class="[item.span, item.route ? 'bg-(--wp-accent-soft)' : '', index === 0 ? 'sm:col-span-2' : '']"
                        @pointermove="trackSpotlight"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <span class="wp-icon size-12">
                                <FontAwesomeIcon :icon="['fas', item.icon]" class="size-5" />
                            </span>
                            <span v-if="item.route" class="inline-flex items-center gap-2 text-xs font-semibold text-(--wp-accent)">
                                <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                Terbuka untuk publik
                            </span>
                        </div>

                        <h3 class="mt-8 font-semibold tracking-[-0.02em]" :class="index === 0 ? 'text-2xl lg:text-3xl' : 'text-xl'">
                            {{ item.title }}
                        </h3>
                        <p class="text-muted-foreground mt-3 max-w-[52ch] leading-relaxed">{{ item.text }}</p>

                        <ul v-if="item.tags" class="mt-6 flex flex-wrap gap-2">
                            <li
                                v-for="tag in item.tags"
                                :key="tag"
                                class="bg-background/70 rounded-md border border-(--wp-hairline) px-2.5 py-1 text-xs font-medium"
                            >
                                {{ tag }}
                            </li>
                        </ul>

                        <span v-if="item.cta" class="wp-link-arrow mt-auto inline-flex items-center gap-2 pt-8 text-sm font-semibold">
                            {{ item.cta }}
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3 text-(--wp-accent)" />
                        </span>
                    </component>
                </div>
            </div>
        </section>

        <section id="faq" class="scroll-mt-20 border-t border-(--wp-hairline)" aria-labelledby="faq-heading">
            <div class="mx-auto grid max-w-7xl gap-14 px-6 pt-24 pb-28 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:pt-32 lg:pb-36">
                <div v-reveal class="lg:sticky lg:top-32 lg:col-span-4 lg:self-start">
                    <p class="wp-eyebrow">Pertanyaan umum</p>
                    <h2 id="faq-heading" class="mt-5 text-4xl leading-[1.05] font-bold tracking-[-0.035em] text-balance lg:text-5xl">
                        Yang sering ditanyakan.
                    </h2>
                    <p class="text-muted-foreground mt-6 leading-relaxed">
                        Tidak menemukan jawaban? Kirim email ke
                        <a
                            href="mailto:dispora@bogorkab.go.id"
                            class="text-foreground font-medium underline decoration-(--wp-accent) underline-offset-4"
                        >
                            dispora@bogorkab.go.id</a
                        >.
                    </p>
                </div>

                <dl class="divide-y divide-(--wp-hairline) lg:col-span-7 lg:col-start-6">
                    <div v-for="(item, index) in faq" :key="item.question" v-reveal="index * 60" class="py-8 first:pt-0">
                        <dt class="text-lg font-semibold tracking-[-0.01em] text-balance">{{ item.question }}</dt>
                        <dd class="text-muted-foreground mt-3 max-w-[64ch] leading-relaxed text-pretty">{{ item.answer }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <section id="cta" class="px-3 pb-24 lg:px-6 lg:pb-32" aria-labelledby="cta-heading">
            <div v-reveal class="wp-surface relative isolate mx-auto max-w-[88rem] overflow-hidden rounded-[2.5rem]">
                <div class="wp-hero-bg absolute inset-0 -z-10" aria-hidden="true"></div>
                <div class="mx-auto grid max-w-7xl gap-10 px-8 py-16 lg:grid-cols-12 lg:items-end lg:px-12 lg:py-24">
                    <div class="lg:col-span-7">
                        <h2 id="cta-heading" class="text-4xl leading-[1.02] font-bold tracking-[-0.04em] text-balance lg:text-6xl">
                            Siap merapikan data olahraga Anda?
                        </h2>
                        <p class="text-muted-foreground mt-6 max-w-[52ch] text-lg leading-relaxed">
                            Bergabung dengan AIDARA — platform resmi Dispora Kabupaten Bogor untuk pengelolaan data olahraga yang lebih efisien.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-6 gap-y-4 lg:col-span-5 lg:justify-end">
                        <Link v-if="!isLoggedIn" :href="route('register')" class="wp-btn wp-btn-primary px-7 py-3.5 text-[0.95rem]">
                            Daftar sekarang
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                        </Link>
                        <Link v-else :href="route('dashboard')" class="wp-btn wp-btn-primary px-7 py-3.5 text-[0.95rem]">
                            Buka dasbor
                            <FontAwesomeIcon :icon="['fas', 'arrow-right']" class="size-3.5" />
                        </Link>
                        <Link
                            v-if="!isLoggedIn"
                            :href="route('login')"
                            class="text-foreground text-[0.95rem] font-semibold decoration-(--wp-accent) underline-offset-4 hover:underline"
                        >
                            Sudah punya akun? Masuk
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </PublicSiteLayout>
</template>
