<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import AppTabs from '@/components/AppTabs.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/vue3';
import { FileText, Image as ImageIcon, ArrowLeft, Calendar, User, Award, MapPin, Clock, Download, Users, Camera, CheckCircle2, AlertCircle, ExternalLink, Loader2 } from 'lucide-vue-next';
import { ref, computed, onMounted, watch } from 'vue';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import axios from 'axios';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

interface AbsenMonitorRecord {
    id: number;
    atlet_id: number;
    atlet_nama?: string;
    tanggal: string;
    status: string;
    status_label: string;
    waktu_foto?: string | null;
    lokasi?: string | null;
    latitude?: number | string | null;
    longitude?: number | string | null;
    foto?: {
        url: string;
        lokasi?: string | null;
        latitude?: number | string | null;
        longitude?: number | string | null;
        waktu_foto?: string | null;
    } | null;
}

interface KehadiranAtlet {
    atlet_id: number;
    nama: string;
    nik?: string;
    hadir: number;
    izin: number;
    sakit: number;
    alpha: number;
    total_sesi: number;
    persentase: number;
    kategori_kehadiran: string;
}

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
        foto_absen: Array<{ id: number; url: string; name: string; lokasi?: string | null; latitude?: number | null; longitude?: number | null; waktu_foto?: string | null }>;
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
    kehadiran: {
        total_sesi: number;
        total_atlet: number;
        atlets: KehadiranAtlet[];
        filter_bulan?: string | null;
        range_start?: string | null;
        range_end?: string | null;
    };
    bulan_options: Array<{ value: string; label: string }>;
    filter_bulan: string;
}>();

const activeTab = ref('rekap-pelatih');
const selectedBulan = ref(props.filter_bulan);

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab === 'ringkasan-atlet' || tab === 'monitoring-absen') {
        activeTab.value = tab;
    }
});

const tabsConfig = [
    { value: 'rekap-pelatih', label: 'Rekap Pelatih' },
    { value: 'ringkasan-atlet', label: 'Ringkasan Atlet' },
    { value: 'monitoring-absen', label: 'Monitoring Foto Absen' },
];

const monitorDate = ref(new Date().toISOString().slice(0, 10));
const monitorRecords = ref<AbsenMonitorRecord[]>([]);
const monitorLoading = ref(false);
const selectedAtlet = ref<KehadiranAtlet | null>(null);
const atletRiwayat = ref<AbsenMonitorRecord[]>([]);
const atletRiwayatLoading = ref(false);
const showAtletDialog = ref(false);

const fetchMonitorRecords = async () => {
    monitorLoading.value = true;
    try {
        const response = await axios.get(`/api/program-latihan/${props.program_latihan.id}/absen-atlet`, {
            params: { tanggal: monitorDate.value },
        });
        monitorRecords.value = response.data?.data ?? [];
    } catch {
        monitorRecords.value = [];
    } finally {
        monitorLoading.value = false;
    }
};

const openAtletRiwayat = async (atlet: KehadiranAtlet) => {
    selectedAtlet.value = atlet;
    showAtletDialog.value = true;
    atletRiwayatLoading.value = true;
    try {
        const response = await axios.get(
            `/api/program-latihan/${props.program_latihan.id}/kehadiran/atlet/${atlet.atlet_id}`
        );
        atletRiwayat.value = response.data?.data ?? [];
    } catch {
        atletRiwayat.value = [];
    } finally {
        atletRiwayatLoading.value = false;
    }
};

const absenHasGps = (item: AbsenMonitorRecord) => {
    const lat = item.latitude ?? item.foto?.latitude;
    const lng = item.longitude ?? item.foto?.longitude;
    return lat != null && lng != null && lat !== '';
};

const absenLokasiLabel = (item: AbsenMonitorRecord) => {
    if (item.lokasi) return item.lokasi;
    if (item.foto?.lokasi) return item.foto.lokasi;
    const lat = item.latitude ?? item.foto?.latitude;
    const lng = item.longitude ?? item.foto?.longitude;
    if (lat != null && lng != null) return `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
    return null;
};

const absenMapsUrl = (item: AbsenMonitorRecord) => {
    const lat = item.latitude ?? item.foto?.latitude;
    const lng = item.longitude ?? item.foto?.longitude;
    if (lat == null || lng == null) return null;
    return `https://www.google.com/maps?q=${lat},${lng}`;
};

const verifiedMonitorCount = computed(() => monitorRecords.value.filter(absenHasGps).length);

watch(activeTab, (tab) => {
    if (tab === 'monitoring-absen' && monitorRecords.value.length === 0) {
        fetchMonitorRecords();
    }
});

watch(monitorDate, () => {
    if (activeTab.value === 'monitoring-absen') {
        fetchMonitorRecords();
    }
});

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Rekap Absen', href: `/program-latihan/${props.program_latihan.id}/rekap-absen` },
    { title: 'Detail Rekap Absen', href: '#' },
];

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr);
    const day = date.getDate();
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${day} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const getFotoLokasiLabel = (foto: { lokasi?: string | null; latitude?: number | null; longitude?: number | null }): string | null => {
    if (foto.lokasi) return foto.lokasi;
    if (foto.latitude != null && foto.longitude != null) {
        return `${Number(foto.latitude).toFixed(6)}, ${Number(foto.longitude).toFixed(6)}`;
    }
    return null;
};

const getFotoMapsUrl = (foto: { latitude?: number | null; longitude?: number | null }): string | null => {
    if (foto.latitude == null || foto.longitude == null) return null;
    return `https://www.google.com/maps?q=${foto.latitude},${foto.longitude}`;
};

const getFotoWaktuLabel = (foto: { waktu_foto?: string | null }): string | null => {
    return foto.waktu_foto ? `${foto.waktu_foto} WIB` : null;
};

const getJenisLatihanLabel = (value: string | null | undefined): string => {
    const labels: Record<string, string> = {
        latihan_fisik: 'Latihan Fisik',
        latihan_strategi: 'Latihan Strategi',
        latihan_teknik: 'Latihan Teknik',
        latihan_mental: 'Latihan Mental',
        latihan_pemulihan: 'Latihan Pemulihan',
    };
    return value ? labels[value] || value : '-';
};

const getJenisLatihanBadge = (value: string | null | undefined): string => {
    const badges: Record<string, string> = {
        latihan_fisik: 'bg-blue-500',
        latihan_strategi: 'bg-purple-500',
        latihan_teknik: 'bg-green-500',
        latihan_mental: 'bg-yellow-500',
        latihan_pemulihan: 'bg-orange-500',
    };
    return value ? badges[value] || 'bg-muted-foreground' : 'bg-muted-foreground';
};

const kehadiranBadgeClass = (kategori: string) => {
    if (kategori === 'baik') return 'bg-green-500';
    if (kategori === 'cukup') return 'bg-yellow-500';
    return 'bg-red-500';
};

const kehadiranLabel = (kategori: string) => {
    if (kategori === 'baik') return 'Baik (≥80%)';
    if (kategori === 'cukup') return 'Cukup (50-79%)';
    return 'Kurang (<50%)';
};

const onBulanChange = (value: string) => {
    selectedBulan.value = value;
    router.visit(`/program-latihan/${props.program_latihan.id}/rekap-absen/detail?bulan=${value}`, {
        preserveScroll: true,
    });
};

const sortedAtlets = computed(() =>
    [...props.kehadiran.atlets].sort((a, b) => b.persentase - a.persentase)
);

const exportKehadiranPdf = () => {
    const doc = new jsPDF();
    const bulanLabel = props.bulan_options.find((b) => b.value === selectedBulan.value)?.label ?? 'Semua Periode';

    doc.setFontSize(14);
    doc.text('Ringkasan Kehadiran Atlet', 14, 18);
    doc.setFontSize(10);
    doc.text(`Program: ${props.program_latihan.nama_program}`, 14, 26);
    doc.text(`${props.program_latihan.cabor_nama} - ${props.program_latihan.cabor_kategori_nama}`, 14, 32);
    doc.text(`Periode filter: ${bulanLabel}`, 14, 38);
    doc.text(`Total sesi latihan: ${props.kehadiran.total_sesi}`, 14, 44);

    autoTable(doc, {
        startY: 50,
        head: [['No', 'Nama Atlet', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Sesi', 'Persentase', 'Kategori']],
        body: sortedAtlets.value.map((a, i) => [
            i + 1,
            a.nama,
            a.hadir,
            a.izin,
            a.sakit,
            a.alpha,
            a.total_sesi,
            `${a.persentase}%`,
            kehadiranLabel(a.kategori_kehadiran),
        ]),
        styles: { fontSize: 8 },
    });

    doc.save(`kehadiran-atlet-${props.program_latihan.id}-${selectedBulan.value}.pdf`);
};

const exportKehadiranExcel = () => {
    const bulanLabel = props.bulan_options.find((b) => b.value === selectedBulan.value)?.label ?? 'Semua Periode';
    const headers = ['No', 'Nama Atlet', 'NIK', 'Hadir', 'Izin', 'Sakit', 'Alpha', 'Total Sesi', 'Persentase', 'Kategori'];
    const rows = sortedAtlets.value.map((a, i) => [
        i + 1,
        a.nama,
        a.nik ?? '',
        a.hadir,
        a.izin,
        a.sakit,
        a.alpha,
        a.total_sesi,
        `${a.persentase}%`,
        kehadiranLabel(a.kategori_kehadiran),
    ]);

    const escapeCsv = (value: string | number) => `"${String(value).replace(/"/g, '""')}"`;
    const csvLines = [
        [`Program: ${props.program_latihan.nama_program}`],
        [`Cabor: ${props.program_latihan.cabor_nama} - ${props.program_latihan.cabor_kategori_nama}`],
        [`Periode filter: ${bulanLabel}`],
        [`Total sesi latihan: ${props.kehadiran.total_sesi}`],
        [],
        headers.map(escapeCsv).join(','),
        ...rows.map((row) => row.map(escapeCsv).join(',')),
    ];

    const blob = new Blob(['\ufeff' + csvLines.map((line) => (Array.isArray(line) ? line.join('') : line)).join('\n')], {
        type: 'text/csv;charset=utf-8;',
    });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `kehadiran-atlet-${props.program_latihan.id}-${selectedBulan.value}.csv`;
    link.click();
    URL.revokeObjectURL(url);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto p-6 bg-background text-foreground">
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h1 class="text-3xl font-bold text-foreground">Laporan Rekap Absen Program Latihan</h1>
                        <p class="text-muted-foreground mt-2 text-lg">{{ program_latihan.nama_program }}</p>
                        <p class="text-muted-foreground">{{ program_latihan.cabor_nama }} - {{ program_latihan.cabor_kategori_nama }}</p>
                    </div>
                    <Button variant="outline" @click="router.visit(`/program-latihan/${program_latihan.id}/rekap-absen`)">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Kembali
                    </Button>
                </div>

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
                            <CardDescription>Total Hari Latihan (Pelatih)</CardDescription>
                            <CardTitle class="text-lg">{{ stats.total }} hari</CardTitle>
                        </CardHeader>
                    </Card>
                </div>
            </div>

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

            <AppTabs :tabs="tabsConfig" v-model="activeTab" class="mb-6" />

            <!-- Tab Rekap Pelatih -->
            <div v-if="activeTab === 'rekap-pelatih'" class="space-y-6">
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

                <div class="space-y-6">
                    <h2 class="text-2xl font-bold flex items-center gap-2">
                        <Calendar class="h-6 w-6" />
                        Daftar Rekap Absen
                    </h2>

                    <div v-if="rekap_data.length === 0" class="text-center py-12 text-muted-foreground">
                        Belum ada data rekap absen
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
                                <div v-if="rekap.keterangan">
                                    <p class="text-sm font-medium mb-1">Keterangan:</p>
                                    <p class="text-sm whitespace-pre-wrap bg-muted p-3 rounded">{{ rekap.keterangan }}</p>
                                </div>

                                <div v-if="rekap.foto_absen.length > 0">
                                    <p class="text-sm font-medium mb-2">Foto Absen ({{ rekap.foto_absen.length }}):</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <div v-for="foto in rekap.foto_absen" :key="foto.id" class="space-y-2">
                                            <a :href="foto.url" target="_blank" class="relative group block w-full h-40 rounded-lg border overflow-hidden">
                                                <img :src="foto.url" :alt="foto.name" class="w-full h-full object-cover" />
                                            </a>
                                            <div v-if="getFotoWaktuLabel(foto)" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                <Clock class="h-3.5 w-3.5" />
                                                <span>{{ getFotoWaktuLabel(foto) }}</span>
                                            </div>
                                            <div v-if="getFotoLokasiLabel(foto)" class="flex items-start gap-1.5 text-xs text-muted-foreground">
                                                <MapPin class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                                <a v-if="getFotoMapsUrl(foto)" :href="getFotoMapsUrl(foto)!" target="_blank" class="hover:underline break-words">
                                                    {{ getFotoLokasiLabel(foto) }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="rekap.file_nilai.length > 0">
                                    <p class="text-sm font-medium mb-2">File Nilai ({{ rekap.file_nilai.length }}):</p>
                                    <div class="space-y-2">
                                        <a v-for="file in rekap.file_nilai" :key="file.id" :href="file.url" target="_blank"
                                            class="flex items-center gap-3 p-3 border rounded-lg hover:bg-muted">
                                            <FileText class="h-5 w-5 text-primary" />
                                            <span class="text-sm">{{ file.name }}</span>
                                        </a>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>

            <!-- Tab Ringkasan Atlet -->
            <div v-if="activeTab === 'ringkasan-atlet'" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <Users class="h-6 w-6" />
                            Ringkasan Kehadiran Atlet
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            {{ kehadiran.total_atlet }} atlet · {{ kehadiran.total_sesi }} sesi latihan tercatat
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Select :model-value="selectedBulan" @update:model-value="onBulanChange">
                            <SelectTrigger class="w-[200px]">
                                <SelectValue placeholder="Pilih bulan" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="opt in bulan_options" :key="opt.value" :value="opt.value">
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Button variant="outline" @click="exportKehadiranPdf">
                            <Download class="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                        <Button variant="outline" @click="exportKehadiranExcel">
                            <Download class="h-4 w-4 mr-2" />
                            Export Excel
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardContent class="pt-6">
                        <div v-if="sortedAtlets.length === 0" class="text-center py-8 text-muted-foreground">
                            Belum ada data absen atlet
                        </div>
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-12">No</TableHead>
                                    <TableHead>Nama Atlet</TableHead>
                                    <TableHead class="text-center">Hadir</TableHead>
                                    <TableHead class="text-center">Izin</TableHead>
                                    <TableHead class="text-center">Sakit</TableHead>
                                    <TableHead class="text-center">Alpha</TableHead>
                                    <TableHead class="text-center">Total Sesi</TableHead>
                                    <TableHead class="text-center">Persentase</TableHead>
                                    <TableHead>Kategori</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="(atlet, index) in sortedAtlets"
                                    :key="atlet.atlet_id"
                                    class="cursor-pointer hover:bg-muted/50"
                                    @click="openAtletRiwayat(atlet)"
                                >
                                    <TableCell>{{ index + 1 }}</TableCell>
                                    <TableCell class="font-medium">
                                        {{ atlet.nama }}
                                        <span class="block text-xs text-muted-foreground font-normal">Klik untuk lihat foto absen</span>
                                    </TableCell>
                                    <TableCell class="text-center">{{ atlet.hadir }}</TableCell>
                                    <TableCell class="text-center">{{ atlet.izin }}</TableCell>
                                    <TableCell class="text-center">{{ atlet.sakit }}</TableCell>
                                    <TableCell class="text-center">{{ atlet.alpha }}</TableCell>
                                    <TableCell class="text-center">{{ atlet.total_sesi }}</TableCell>
                                    <TableCell class="text-center font-semibold">{{ atlet.persentase }}%</TableCell>
                                    <TableCell>
                                        <Badge :class="kehadiranBadgeClass(atlet.kategori_kehadiran)" class="text-white">
                                            {{ kehadiranLabel(atlet.kategori_kehadiran) }}
                                        </Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Monitoring Foto Absen -->
            <div v-if="activeTab === 'monitoring-absen'" class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <Camera class="h-6 w-6" />
                            Monitoring Foto Absen Atlet
                        </h2>
                        <p class="text-sm text-muted-foreground mt-1">
                            Verifikasi kehadiran atlet melalui foto, timestamp, dan koordinat GPS.
                        </p>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Tanggal</label>
                        <input
                            v-model="monitorDate"
                            type="date"
                            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card>
                        <CardContent class="pt-6">
                            <p class="text-sm text-muted-foreground">Absen tercatat</p>
                            <p class="text-3xl font-bold">{{ monitorRecords.length }}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent class="pt-6">
                            <p class="text-sm text-muted-foreground">Lokasi terverifikasi (GPS)</p>
                            <p class="text-3xl font-bold text-green-600">{{ verifiedMonitorCount }}</p>
                        </CardContent>
                    </Card>
                </div>

                <div v-if="monitorLoading" class="flex items-center justify-center py-12 text-muted-foreground gap-2">
                    <Loader2 class="h-5 w-5 animate-spin" />
                    Memuat data absen...
                </div>

                <div v-else-if="monitorRecords.length === 0" class="text-center py-12 text-muted-foreground">
                    Belum ada absen atlet pada tanggal ini.
                </div>

                <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="item in monitorRecords" :key="item.id" class="overflow-hidden">
                        <div class="flex flex-col sm:flex-row">
                            <a
                                v-if="item.foto?.url"
                                :href="item.foto.url"
                                target="_blank"
                                class="block sm:w-40 h-40 shrink-0 bg-muted"
                            >
                                <img :src="item.foto.url" alt="" class="w-full h-full object-cover" />
                            </a>
                            <div v-else class="sm:w-40 h-40 shrink-0 bg-muted flex items-center justify-center">
                                <ImageIcon class="h-10 w-10 text-muted-foreground" />
                            </div>
                            <CardContent class="pt-4 space-y-2 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-semibold">{{ item.atlet_nama ?? 'Atlet' }}</p>
                                        <Badge variant="secondary">{{ item.status_label }}</Badge>
                                    </div>
                                    <Badge :class="absenHasGps(item) ? 'bg-green-500' : 'bg-amber-500'" class="text-white shrink-0">
                                        <CheckCircle2 v-if="absenHasGps(item)" class="h-3 w-3 mr-1 inline" />
                                        <AlertCircle v-else class="h-3 w-3 mr-1 inline" />
                                        {{ absenHasGps(item) ? 'GPS OK' : 'Tanpa GPS' }}
                                    </Badge>
                                </div>
                                <p v-if="item.waktu_foto" class="text-sm text-muted-foreground flex items-center gap-1.5">
                                    <Clock class="h-4 w-4" />
                                    {{ item.waktu_foto }} WIB
                                </p>
                                <p v-if="absenLokasiLabel(item)" class="text-sm text-muted-foreground flex items-start gap-1.5">
                                    <MapPin class="h-4 w-4 shrink-0 mt-0.5" />
                                    <a
                                        v-if="absenMapsUrl(item)"
                                        :href="absenMapsUrl(item)!"
                                        target="_blank"
                                        class="hover:underline break-words"
                                    >
                                        {{ absenLokasiLabel(item) }}
                                    </a>
                                    <span v-else>{{ absenLokasiLabel(item) }}</span>
                                </p>
                            </CardContent>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <Dialog v-model:open="showAtletDialog">
            <DialogContent class="max-w-3xl max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Riwayat Foto Absen — {{ selectedAtlet?.nama }}</DialogTitle>
                </DialogHeader>
                <div v-if="atletRiwayatLoading" class="py-8 text-center text-muted-foreground">Memuat riwayat...</div>
                <div v-else-if="atletRiwayat.length === 0" class="py-8 text-center text-muted-foreground">
                    Belum ada riwayat absen dengan foto.
                </div>
                <div v-else class="grid gap-4 sm:grid-cols-2">
                    <Card v-for="item in atletRiwayat" :key="item.id" class="overflow-hidden">
                        <a v-if="item.foto?.url" :href="item.foto.url" target="_blank" class="block h-48 bg-muted">
                            <img :src="item.foto.url" alt="" class="w-full h-full object-cover" />
                        </a>
                        <CardContent class="pt-4 space-y-2">
                            <p class="font-medium">{{ formatDate(item.tanggal) }}</p>
                            <Badge variant="secondary">{{ item.status_label }}</Badge>
                            <p v-if="item.waktu_foto" class="text-sm text-muted-foreground">{{ item.waktu_foto }} WIB</p>
                            <p v-if="absenLokasiLabel(item)" class="text-sm text-muted-foreground flex items-start gap-1.5">
                                <MapPin class="h-4 w-4 shrink-0" />
                                <a v-if="absenMapsUrl(item)" :href="absenMapsUrl(item)!" target="_blank" class="hover:underline">
                                    {{ absenLokasiLabel(item) }}
                                </a>
                                <span v-else>{{ absenLokasiLabel(item) }}</span>
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
