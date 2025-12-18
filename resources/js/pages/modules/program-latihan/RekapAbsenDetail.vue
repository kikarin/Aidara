<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/vue3';
import { FileText, Image as ImageIcon, ArrowLeft, Calendar, User, Award } from 'lucide-vue-next';

const props = defineProps<{
    program_latihan: {
        id: number;
        nama_program: string;
        cabor_nama: string;
        cabor_kategori_nama: string;
        periode_mulai: string;
        periode_selesai: string;
        periode_hitung?: string;
    };
    rekap_data: Array<{
        id: number;
        tanggal: string;
        jenis_latihan?: string;
        keterangan?: string;
        foto_absen: Array<{ id: number; url: string; name: string }>;
        file_nilai: Array<{ id: number; url: string; name: string }>;
    }>;
    pelatih_data?: {
        nama: string;
        kategori_peserta: string;
        cabor: string;
    } | null;
    stats: {
        total: number;
        fisik: number;
        strategi: number;
        teknik: number;
        mental: number;
        pemulihan: number;
    };
}>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rekap Absen', href: `/program-latihan/${props.program_latihan.id}/rekap-absen` },
    { title: 'Detail Rekap Absen', href: '#' },
];

// Format date
const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    const day = date.getDate();
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    return `${day} ${month} ${year}`;
};

// Get jenis latihan label
const getJenisLatihanLabel = (value: string | null | undefined): string => {
    const labels: Record<string, string> = {
        'latihan_fisik': 'Latihan Fisik',
        'latihan_strategi': 'Latihan Strategi',
        'latihan_teknik': 'Latihan Teknik',
        'latihan_mental': 'Latihan Mental',
        'latihan_pemulihan': 'Latihan Pemulihan',
    };
    return value ? labels[value] || value : '-';
};

// Get jenis latihan badge color
const getJenisLatihanBadge = (value: string | null | undefined): string => {
    const badges: Record<string, string> = {
        'latihan_fisik': 'bg-blue-500',
        'latihan_strategi': 'bg-purple-500',
        'latihan_teknik': 'bg-green-500',
        'latihan_mental': 'bg-yellow-500',
        'latihan_pemulihan': 'bg-orange-500',
    };
    return value ? badges[value] || 'bg-gray-500' : 'bg-gray-500';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-6 bg-background text-foreground">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-foreground">Laporan Rekap Absen Program Latihan</h1>
                        <p class="text-muted-foreground mt-2 text-lg">
                            {{ program_latihan.nama_program }}
                        </p>
                        <p class="text-muted-foreground">
                            {{ program_latihan.cabor_nama }} - {{ program_latihan.cabor_kategori_nama }}
                        </p>
                    </div>
                    <Button variant="outline" @click="router.visit(`/program-latihan/${program_latihan.id}/rekap-absen`)">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Kembali
                    </Button>
                </div>
                
                <!-- Info Box -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <Card>
                        <CardHeader class="pb-3">
                            <CardDescription>Periode Program</CardDescription>
                            <CardTitle class="text-lg">
                                {{ formatDate(program_latihan.periode_mulai) }} - {{ formatDate(program_latihan.periode_selesai) }}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader class="pb-3">
                            <CardDescription>Durasi</CardDescription>
                            <CardTitle class="text-lg">{{ program_latihan.periode_hitung || '-' }}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader class="pb-3">
                            <CardDescription>Total Hari Latihan</CardDescription>
                            <CardTitle class="text-lg">{{ stats.total }} hari</CardTitle>
                        </CardHeader>
                    </Card>
                </div>
            </div>

            <!-- Informasi Pelatih -->
            <Card v-if="pelatih_data" class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <User class="h-5 w-5" />
                        Informasi Pelatih
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Nama Pelatih</p>
                            <p class="font-semibold">{{ pelatih_data.nama }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Kategori Peserta</p>
                            <p class="font-semibold">{{ pelatih_data.kategori_peserta }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Cabor</p>
                            <p class="font-semibold">{{ pelatih_data.cabor }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Statistik -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Award class="h-5 w-5" />
                        Ringkasan Jenis Latihan
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center p-4 border rounded-lg">
                            <p class="text-2xl font-bold text-blue-600">{{ stats.fisik }}</p>
                            <p class="text-sm text-muted-foreground">Latihan Fisik</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <p class="text-2xl font-bold text-purple-600">{{ stats.strategi }}</p>
                            <p class="text-sm text-muted-foreground">Latihan Strategi</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <p class="text-2xl font-bold text-green-600">{{ stats.teknik }}</p>
                            <p class="text-sm text-muted-foreground">Latihan Teknik</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <p class="text-2xl font-bold text-yellow-600">{{ stats.mental }}</p>
                            <p class="text-sm text-muted-foreground">Latihan Mental</p>
                        </div>
                        <div class="text-center p-4 border rounded-lg">
                            <p class="text-2xl font-bold text-orange-600">{{ stats.pemulihan }}</p>
                            <p class="text-sm text-muted-foreground">Latihan Pemulihan</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Daftar Rekap Absen -->
            <div class="space-y-6">
                <h2 class="text-2xl font-bold text-foreground flex items-center gap-2">
                    <Calendar class="h-6 w-6" />
                    Daftar Rekap Absen
                </h2>
                
                <div v-if="rekap_data.length === 0" class="text-center py-12">
                    <p class="text-muted-foreground">Belum ada data rekap absen</p>
                </div>
                
                <div v-else class="space-y-4">
                    <Card v-for="rekap in rekap_data" :key="rekap.id" class="hover:shadow-lg transition-shadow">
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div>
                                    <CardTitle class="text-xl">{{ formatDate(rekap.tanggal) }}</CardTitle>
                                    <CardDescription>
                                        {{ new Date(rekap.tanggal).toLocaleDateString('id-ID', { weekday: 'long' }) }}
                                    </CardDescription>
                                </div>
                                <Badge :class="getJenisLatihanBadge(rekap.jenis_latihan)" class="text-white">
                                    {{ getJenisLatihanLabel(rekap.jenis_latihan) }}
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Keterangan -->
                            <div v-if="rekap.keterangan">
                                <p class="text-sm font-medium text-foreground mb-1">Keterangan:</p>
                                <p class="text-sm text-foreground whitespace-pre-wrap bg-muted p-3 rounded">{{ rekap.keterangan }}</p>
                            </div>
                            
                            <!-- Foto Absen -->
                            <div v-if="rekap.foto_absen.length > 0">
                                <p class="text-sm font-medium text-foreground mb-2">
                                    Foto Absen ({{ rekap.foto_absen.length }}):
                                </p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <a
                                        v-for="foto in rekap.foto_absen"
                                        :key="foto.id"
                                        :href="foto.url"
                                        target="_blank"
                                        class="relative group block w-full h-40 rounded-lg border overflow-hidden hover:opacity-90 transition-opacity"
                                    >
                                        <img
                                            :src="foto.url"
                                            :alt="foto.name"
                                            class="w-full h-full object-cover"
                                        />
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                            <ImageIcon class="h-8 w-8 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                        </div>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- File Nilai -->
                            <div v-if="rekap.file_nilai.length > 0">
                                <p class="text-sm font-medium text-foreground mb-2">
                                    File Nilai ({{ rekap.file_nilai.length }}):
                                </p>
                                <div class="space-y-2">
                                    <a
                                        v-for="file in rekap.file_nilai"
                                        :key="file.id"
                                        :href="file.url"
                                        target="_blank"
                                        class="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted transition-colors"
                                    >
                                        <FileText class="h-5 w-5 text-primary" />
                                        <span class="text-sm text-foreground hover:underline">{{ file.name }}</span>
                                    </a>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

