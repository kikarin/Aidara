<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, FileCheck2, Gavel, UserPlus, Users } from 'lucide-vue-next';

interface Syarat {
    id: number;
    nama_cabor: string;
    kelompok_map: string;
    tahun_lahir_min: number;
    tahun_lahir_max: number;
    kuota_putra: number;
    kuota_putri: number;
}

interface Periode {
    id: number;
    nama: string;
    tahun: number;
    status: string;
    tanggal_daftar_mulai: string;
    tanggal_daftar_selesai: string;
    tanggal_pengumuman_admin: string | null;
    tanggal_tes_mulai: string | null;
    tanggal_tes_selesai: string | null;
    tanggal_pleno: string | null;
    tanggal_pengumuman_hasil: string | null;
    tanggal_orientasi: string | null;
    lokasi_daftar: string | null;
    lokasi_tes: string | null;
}

const props = defineProps<{
    periode: Periode | null;
    syarat: Syarat[];
    stats: {
        total: number;
        submitted: number;
        lulus_administrasi: number;
        lulus: number;
        observasi: number;
        tidak_lulus: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Seleksi PPOPM', href: '/seleksi-ppopm' }];

const formatDate = (value?: string | null) => {
    if (!value) return '-';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const jadwal = [
    { label: 'Pendaftaran', value: `${formatDate(props.periode?.tanggal_daftar_mulai)} – ${formatDate(props.periode?.tanggal_daftar_selesai)}`, lokasi: props.periode?.lokasi_daftar },
    { label: 'Pengumuman administrasi', value: formatDate(props.periode?.tanggal_pengumuman_admin), lokasi: 'Sosial media PPOPM' },
    { label: 'Seleksi umum', value: `${formatDate(props.periode?.tanggal_tes_mulai)} – ${formatDate(props.periode?.tanggal_tes_selesai)}`, lokasi: props.periode?.lokasi_tes },
    { label: 'Pleno penetapan', value: formatDate(props.periode?.tanggal_pleno), lokasi: 'PPOPM' },
    { label: 'Pengumuman hasil', value: formatDate(props.periode?.tanggal_pengumuman_hasil), lokasi: 'Sosial media PPOPM' },
    { label: 'Orientasi', value: props.periode?.tanggal_orientasi || '-', lokasi: 'PPOPM' },
];

const tesHarian = [
    { hari: '18 Mei 2026', tes: 'Tes kecabangan' },
    { hari: '19 Mei 2026', tes: 'Tes kesehatan & antropometri' },
    { hari: '20 Mei 2026', tes: 'Tes psikologi' },
    { hari: '21 Mei 2026', tes: 'Tes parameter fisik' },
    { hari: '22 Mei 2026', tes: 'Tes akademik' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Seleksi PPOPM" />

        <div class="space-y-6 p-4 lg:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">Seleksi Atlet PPOPM</h1>
                    <p class="text-muted-foreground text-sm">
                        {{ periode?.nama || 'Periode seleksi belum tersedia. Jalankan seeder SeleksiPpopmSeeder.' }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline">
                        <Link href="/seleksi-ppopm/syarat">Syarat cabor</Link>
                    </Button>
                    <Button as-child variant="outline">
                        <Link href="/seleksi-ppopm/pleno">Pleno & ranking</Link>
                    </Button>
                    <Button as-child>
                        <Link href="/seleksi-ppopm/pendaftar/create">Daftar calon atlet</Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Total pendaftar</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.total }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Menunggu verifikasi</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.submitted }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Lulus administrasi</CardDescription>
                        <CardTitle class="text-3xl">{{ stats.lulus_administrasi }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Lulus</CardDescription>
                        <CardTitle class="text-3xl text-green-600">{{ stats.lulus }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Observasi</CardDescription>
                        <CardTitle class="text-3xl text-amber-600">{{ stats.observasi }}</CardTitle>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>Tidak lulus / gugur</CardDescription>
                        <CardTitle class="text-3xl text-red-600">{{ stats.tidak_lulus }}</CardTitle>
                    </CardHeader>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Jadwal JUKNIS 2026</CardTitle>
                        <CardDescription>Lokasi pendaftaran: Gor Karadenan Cibinong, 08.00–16.00 WIB</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div v-for="item in jadwal" :key="item.label" class="flex items-start justify-between gap-4 border-b pb-2 last:border-0">
                            <div>
                                <p class="font-medium">{{ item.label }}</p>
                                <p class="text-muted-foreground">{{ item.lokasi || '-' }}</p>
                            </div>
                            <p class="text-right">{{ item.value }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tahapan tes</CardTitle>
                        <CardDescription>Nilai akhir = kecabangan 40% + fisik 25% + akademik 10% + psikologi 25%</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div v-for="item in tesHarian" :key="item.hari" class="flex justify-between gap-4 border-b pb-2 last:border-0">
                            <span>{{ item.tes }}</span>
                            <span class="text-muted-foreground">{{ item.hari }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <Link href="/seleksi-ppopm/pendaftar" class="border-border hover:bg-muted/40 rounded-lg border p-4 transition">
                    <Users class="text-primary mb-2 size-5" />
                    <p class="font-medium">Pendaftar</p>
                    <p class="text-muted-foreground text-sm">Daftar, filter, dan verifikasi berkas</p>
                </Link>
                <Link href="/seleksi-ppopm/pendaftar/create" class="border-border hover:bg-muted/40 rounded-lg border p-4 transition">
                    <UserPlus class="text-primary mb-2 size-5" />
                    <p class="font-medium">Pendaftaran baru</p>
                    <p class="text-muted-foreground text-sm">Input calon atlet sesuai syarat cabor</p>
                </Link>
                <Link href="/seleksi-ppopm/syarat" class="border-border hover:bg-muted/40 rounded-lg border p-4 transition">
                    <FileCheck2 class="text-primary mb-2 size-5" />
                    <p class="font-medium">Persyaratan khusus</p>
                    <p class="text-muted-foreground text-sm">Tahun lahir, tinggi, posisi, dan kuota</p>
                </Link>
                <Link href="/seleksi-ppopm/pleno" class="border-border hover:bg-muted/40 rounded-lg border p-4 transition">
                    <Gavel class="text-primary mb-2 size-5" />
                    <p class="font-medium">Pleno</p>
                    <p class="text-muted-foreground text-sm">Hitung ranking, kuota, dan angkat atlet</p>
                </Link>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2"><ClipboardList class="size-5" /> Cabor dibuka</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="item in syarat" :key="item.id" class="rounded-md border px-3 py-2 text-sm">
                            <p class="font-medium">{{ item.nama_cabor }}</p>
                            <p class="text-muted-foreground">
                                Lahir {{ item.tahun_lahir_min }}–{{ item.tahun_lahir_max }} · Kuota putra {{ item.kuota_putra }} / putri
                                {{ item.kuota_putri }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
