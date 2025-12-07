<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import ApexChart from '@/components/ApexChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Activity, BarChart3, Info, Loader2, Users } from 'lucide-vue-next';
import permissionService from '@/services/permissionService';
import { computed, onMounted, ref, watch } from 'vue';

const { toast } = useToast();

const props = defineProps<{ item: Record<string, any> }>();

// Tab management
function getTabFromUrl(url: string, fallback = 'informasi-data') {
    if (url.includes('tab=')) {
        return new URLSearchParams(url.split('?')[1]).get('tab') || fallback;
    }
    return fallback;
}

const page = usePage();
const initialTab = getTabFromUrl(page.url);
const activeTab = ref(initialTab);

watch(activeTab, (val) => {
    const url = `/pemeriksaan-khusus/${props.item.id}?tab=${val}`;
    router.visit(url, { replace: true, preserveState: true, preserveScroll: true, only: [] });
});

watch(
    () => page.url,
    (newUrl) => {
        const tab = getTabFromUrl(newUrl);
        if (tab !== activeTab.value) {
            activeTab.value = tab;
        }
    },
);

const dynamicTitle = computed(() => {
    if (activeTab.value === 'informasi-data') {
        return `Informasi : ${props.item.nama_pemeriksaan || 'Pemeriksaan Khusus'}`;
    } else if (activeTab.value === 'visualisasi-data') {
        return `Visualisasi : ${props.item.nama_pemeriksaan || 'Pemeriksaan Khusus'}`;
    }
    return `Pemeriksaan Khusus : ${props.item.nama_pemeriksaan || ''}`;
});

const breadcrumbs = [
    { title: 'Pemeriksaan Khusus', href: '/pemeriksaan-khusus' },
    { title: 'Detail Pemeriksaan Khusus', href: `/pemeriksaan-khusus/${props.item.id}` },
];

const tabsConfig = [
    {
        value: 'informasi-data',
        label: 'Informasi',
    },
    {
        value: 'visualisasi-data',
        label: 'Visualisasi',
    },
];

const fields = computed(() => {
    const status = props.item?.status;
    const statusMap = {
        belum: {
            label: 'Belum',
            class: 'text-red-800 bg-red-300',
        },
        sebagian: {
            label: 'Sebagian',
            class: 'text-yellow-800 bg-yellow-100',
        },
        selesai: {
            label: 'Selesai',
            class: 'text-green-800 bg-green-100',
        },
    };

    const statusValue = statusMap[status as keyof typeof statusMap] || { label: '-', class: 'text-gray-500' };

    return [
        { label: 'Cabor', value: props.item?.cabor?.nama || '-' },
        { label: 'Kategori', value: props.item?.cabor_kategori?.nama || '-' },
        { label: 'Nama Pemeriksaan', value: props.item?.nama_pemeriksaan || '-' },
        {
            label: 'Tanggal Pemeriksaan',
            value: props.item?.tanggal_pemeriksaan
                ? new Date(props.item.tanggal_pemeriksaan).toLocaleDateString('id-ID')
                : '-',
        },
        {
            label: 'Status',
            value: statusValue.label,
            className: `inline-block px-2 py-1 text-xs font-semibold rounded-full ${statusValue.class}`,
        },
    ];
});

const actionFields = computed(() => {
    if (activeTab.value !== 'informasi-data') return [];
    return [
        { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
        { label: 'Created By', value: props.item.created_by_user?.name || '-' },
        { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
        { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
    ];
});

const handleDelete = () => {
    router.delete(`/pemeriksaan-khusus/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Data pemeriksaan khusus berhasil dihapus', variant: 'success' });
            router.visit('/pemeriksaan-khusus');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data pemeriksaan khusus', variant: 'destructive' });
        },
    });
};

// Visualisasi data
const loadingVisualisasi = ref(false);
const visualisasiData = ref<any[]>([]);
const aspekList = ref<any[]>([]);
const selectedPeserta = ref<any>(null);

// Load data visualisasi
const loadVisualisasi = async () => {
    if (activeTab.value !== 'visualisasi-data') return;
    
    loadingVisualisasi.value = true;
    try {
        const res = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/visualisasi`);
        if (res.data.success) {
            visualisasiData.value = res.data.data || [];
            aspekList.value = res.data.aspek_list || [];

            // Set peserta pertama sebagai default
            if (visualisasiData.value.length > 0) {
                selectedPeserta.value = visualisasiData.value[0];
            }
        }
    } catch (error: any) {
        console.error('Error loading visualisasi:', error);
        toast({
            title: error.response?.data?.message || 'Gagal memuat data visualisasi',
            variant: 'destructive',
        });
    } finally {
        loadingVisualisasi.value = false;
    }
};

// Load visualisasi when tab changes to visualisasi
watch(activeTab, (newTab) => {
    if (newTab === 'visualisasi-data') {
        loadVisualisasi();
    }
});

// Helper: Convert to number safely
const toNumber = (value: any): number | null => {
    if (value === null || value === undefined || value === '') return null;
    const num = typeof value === 'string' ? parseFloat(value) : Number(value);
    return isNaN(num) ? null : num;
};

// Helper: Format percentage safely
const formatPersentase = (value: any): string => {
    const num = toNumber(value);
    if (num === null) return '-';
    return `${num.toFixed(1)}%`;
};

// Helper: Get predikat label
const getPredikatLabel = (predikat: string | null): string => {
    if (!predikat) return '-';
    const labels: Record<string, string> = {
        sangat_kurang: 'Sangat Kurang',
        kurang: 'Kurang',
        sedang: 'Sedang',
        mendekati_target: 'Mendekati Target',
        target: 'Target',
    };
    return labels[predikat] || predikat;
};

// Helper: Get predikat color
const getPredikatColor = (predikat: string | null): string => {
    if (!predikat) return 'bg-gray-300 text-gray-600';
    const colors: Record<string, string> = {
        sangat_kurang: 'bg-red-500 text-white',
        kurang: 'bg-orange-500 text-white',
        sedang: 'bg-yellow-500 text-white',
        mendekati_target: 'bg-green-400 text-white',
        target: 'bg-green-600 text-white',
    };
    return colors[predikat] || 'bg-gray-500 text-white';
};

// Radar chart options untuk aspek
const radarChartOptions = computed(() => {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        chart: {
            type: 'radar',
            toolbar: { show: false },
            background: 'transparent',
        },
        stroke: {
            width: 2,
        },
        fill: {
            opacity: 0.3,
        },
        markers: {
            size: 4,
        },
        xaxis: {
            categories: aspekList.value.length > 0 ? aspekList.value.map((a) => a.nama) : [''],
            labels: {
                style: {
                    colors: isDark ? '#9ca3af' : '#6b7280',
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            min: 0,
            max: 100,
            tickAmount: 5,
            labels: {
                style: {
                    colors: isDark ? '#9ca3af' : '#6b7280',
                },
                formatter: (val: number) => `${val}%`,
            },
        },
        tooltip: {
            theme: isDark ? 'dark' : 'light',
            y: {
                formatter: (val: any) => {
                    const num = toNumber(val);
                    return num !== null ? `${num.toFixed(1)}%` : '0%';
                },
            },
        },
        plotOptions: {
            radar: {
                polygons: {
                    strokeColors: isDark ? '#374151' : '#e5e7eb',
                    connectorColors: isDark ? '#374151' : '#e5e7eb',
                    fill: {
                        colors: isDark ? ['#1f2937'] : ['#f9fafb'],
                    },
                },
            },
        },
        colors: ['#3b82f6'],
    };
});

// Radar chart series untuk aspek
const radarChartSeries = computed(() => {
    if (!selectedPeserta.value || aspekList.value.length === 0) return [];

    return [
        {
            name: selectedPeserta.value.peserta.nama,
            data: aspekList.value.map((aspek) => {
                const hasilAspek = selectedPeserta.value?.aspek?.find((a: any) => a.aspek_id === aspek.id);
                const nilai = toNumber(hasilAspek?.nilai_performa);
                return nilai !== null ? nilai : 0;
            }),
        },
    ];
});

// Gauge chart options untuk nilai keseluruhan
const gaugeChartOptions = computed(() => {
    const isDark = document.documentElement.classList.contains('dark');

    return {
        chart: {
            type: 'radialBar',
            toolbar: { show: false },
            background: 'transparent',
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: {
                    background: isDark ? '#374151' : '#e5e7eb',
                    strokeWidth: '97%',
                    margin: 5,
                },
                dataLabels: {
                    name: {
                        show: true,
                        fontSize: '16px',
                        fontWeight: 600,
                        offsetY: -10,
                        color: isDark ? '#ffffff' : '#000000',
                    },
                    value: {
                        show: true,
                        fontSize: '30px',
                        fontWeight: 700,
                        offsetY: 16,
                        color: isDark ? '#ffffff' : '#000000',
                        formatter: (val: any) => {
                            const num = toNumber(val);
                            return num !== null ? `${num.toFixed(1)}%` : '0%';
                        },
                    },
                },
            },
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: 'light',
                type: 'horizontal',
                shadeIntensity: 0.5,
                gradientToColors: ['#10b981'],
                inverseColors: true,
                opacityFrom: 1,
                opacityTo: 1,
                stops: [0, 100],
            },
        },
        labels: ['Nilai Keseluruhan'],
        colors: ['#3b82f6'],
    };
});

// Gauge chart series untuk nilai keseluruhan
const gaugeChartSeries = computed(() => {
    if (!selectedPeserta.value) return [0];
    const nilai = toNumber(selectedPeserta.value.nilai_keseluruhan);
    return nilai !== null ? [nilai] : [0];
});

// Helper: Get item tes by aspek (sorted by urutan)
const getItemTesByAspek = (itemTesList: any[], aspekId: number) => {
    if (!itemTesList || !Array.isArray(itemTesList)) return [];
    return itemTesList
        .filter((item) => item.aspek_id === aspekId)
        .sort((a, b) => (a.urutan || 0) - (b.urutan || 0));
};

// Permission checks
const canSetup = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Setup');
});

const canInputHasilTes = computed(() => {
    return permissionService.hasPermission('Pemeriksaan Khusus Input Hasil Tes');
});

// Get peserta count by type
const pesertaCount = computed(() => {
    const peserta = props.item?.pemeriksaan_khusus_peserta || [];
    let atlet = 0;
    let pelatih = 0;
    let tenagaPendukung = 0;

    peserta.forEach((p: any) => {
        const type = p.peserta_type;
        if (type === 'App\\Models\\Atlet') atlet++;
        else if (type === 'App\\Models\\Pelatih') pelatih++;
        else if (type === 'App\\Models\\TenagaPendukung') tenagaPendukung++;
    });

    return { atlet, pelatih, tenagaPendukung, total: peserta.length };
});

onMounted(() => {
    if (activeTab.value === 'visualisasi-data') {
        loadVisualisasi();
    }
});
</script>

<template>
    <PageShow
        :title="dynamicTitle"
        :breadcrumbs="breadcrumbs"
        :fields="activeTab === 'informasi-data' ? fields : []"
        :action-fields="actionFields"
        :back-url="'/pemeriksaan-khusus'"
        :on-edit="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/edit`)"
        :on-delete="activeTab === 'informasi-data' ? handleDelete : undefined"
    >
        <template #tabs>
            <AppTabs :tabs="tabsConfig" :default-value="'informasi-data'" v-model="activeTab" />
        </template>

        <template #custom-action>
            <Button
                v-if="activeTab === 'visualisasi-data' && canInputHasilTes"
                variant="outline"
                @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/input-hasil-tes`)"
            >
                <Activity class="h-4 w-4 mr-2" />
                Input Hasil Tes
            </Button>
        </template>

        <template #custom>
            <!-- Tab Informasi -->
            <div v-if="activeTab === 'informasi-data'" class="space-y-6">
                <!-- Informasi Peserta -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Informasi Peserta
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Total Peserta</div>
                                <div class="text-2xl font-bold">{{ pesertaCount.total }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Atlet</div>
                                <div class="text-2xl font-bold text-blue-600">{{ pesertaCount.atlet }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Pelatih</div>
                                <div class="text-2xl font-bold text-green-600">{{ pesertaCount.pelatih }}</div>
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs text-muted-foreground">Tenaga Pendukung</div>
                                <div class="text-2xl font-bold text-yellow-600">{{ pesertaCount.tenagaPendukung }}</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Informasi Aspek & Item Tes -->
                <Card v-if="props.item.aspek && props.item.aspek.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Info class="h-5 w-5" />
                            Aspek & Item Tes
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-6">
                            <div
                                v-for="(aspek, aspekIdx) in props.item.aspek"
                                :key="aspek.id"
                                class="space-y-3"
                                :class="{ 'border-t pt-6': aspekIdx > 0 }"
                            >
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold">{{ aspek.nama }}</h3>
                                    <Badge variant="outline">Urutan: {{ aspek.urutan }}</Badge>
                                </div>
                                <div v-if="aspek.item_tes && aspek.item_tes.length > 0" class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="px-4 py-2 text-left">Item Tes</th>
                                                <th class="px-4 py-2 text-left">Satuan</th>
                                                <th class="px-4 py-2 text-center">Target Laki-laki</th>
                                                <th class="px-4 py-2 text-center">Target Perempuan</th>
                                                <th class="px-4 py-2 text-center">Performa Arah</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="itemTes in aspek.item_tes"
                                                :key="itemTes.id"
                                                class="border-b hover:bg-muted/50"
                                            >
                                                <td class="px-4 py-2 font-medium">{{ itemTes.nama }}</td>
                                                <td class="px-4 py-2">{{ itemTes.satuan || '-' }}</td>
                                                <td class="px-4 py-2 text-center">{{ itemTes.target_laki_laki || '-' }}</td>
                                                <td class="px-4 py-2 text-center">{{ itemTes.target_perempuan || '-' }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <Badge
                                                        :class="
                                                            itemTes.performa_arah === 'max'
                                                                ? 'bg-green-500 text-white'
                                                                : 'bg-red-500 text-white'
                                                        "
                                                    >
                                                        {{ itemTes.performa_arah === 'max' ? 'Maksimal' : 'Minimal' }}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-sm text-muted-foreground italic">Belum ada item tes</div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card v-else>
                    <CardContent class="py-8 text-center text-muted-foreground">
                        <Info class="h-12 w-12 mx-auto mb-4 opacity-50" />
                        <p>Belum ada aspek yang ditambahkan</p>
                        <Button
                            v-if="canSetup"
                            class="mt-4"
                            variant="outline"
                            @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/setup`)"
                        >
                            Setup Aspek & Item Tes
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Tab Visualisasi -->
            <div v-if="activeTab === 'visualisasi-data'" class="space-y-6">
                <!-- Loading State -->
                <div v-if="loadingVisualisasi" class="flex items-center justify-center py-12">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    <span class="ml-2 text-muted-foreground">Memuat data visualisasi...</span>
                </div>

                <!-- Empty State -->
                <div v-else-if="visualisasiData.length === 0" class="text-center py-12">
                    <BarChart3 class="h-12 w-12 mx-auto mb-4 text-muted-foreground" />
                    <p class="text-muted-foreground">Belum ada data visualisasi</p>
                    <Button
                        v-if="canInputHasilTes"
                        class="mt-4"
                        @click="() => router.visit(`/pemeriksaan-khusus/${props.item.id}/input-hasil-tes`)"
                    >
                        Input Hasil Tes
                    </Button>
                </div>

                <!-- Visualisasi Content -->
                <div v-else class="space-y-6">
                    <!-- Peserta Selector -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Pilih Peserta</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                                <button
                                    v-for="peserta in visualisasiData"
                                    :key="peserta.peserta_id"
                                    :class="[
                                        'p-3 rounded-lg border text-left transition-all',
                                        selectedPeserta?.peserta_id === peserta.peserta_id
                                            ? 'border-primary bg-primary/10'
                                            : 'border-border hover:bg-muted',
                                    ]"
                                    @click="selectedPeserta = peserta"
                                >
                                    <div class="font-medium">{{ peserta.peserta.nama }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            peserta.peserta.jenis_kelamin === 'L' || peserta.peserta.jenis_kelamin === 'Laki-laki'
                                                ? 'Laki-laki'
                                                : 'Perempuan'
                                        }}
                                    </div>
                                </button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Charts untuk Peserta Terpilih -->
                    <div v-if="selectedPeserta && aspekList.length > 0" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Radar Chart - Aspek -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Performa Aspek</CardTitle>
                                <CardDescription>Radar chart untuk menampilkan performa setiap aspek</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ApexChart
                                    v-if="radarChartSeries.length > 0"
                                    :options="radarChartOptions"
                                    :series="radarChartSeries"
                                />
                                <div v-else class="flex items-center justify-center py-12 text-muted-foreground">
                                    Belum ada data aspek
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Gauge Chart - Nilai Keseluruhan -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Nilai Keseluruhan</CardTitle>
                                <CardDescription>Gauge chart untuk menampilkan nilai keseluruhan</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-4">
                                    <ApexChart :options="gaugeChartOptions" :series="gaugeChartSeries" />
                                    <div class="text-center">
                                        <Badge :class="getPredikatColor(selectedPeserta.predikat_keseluruhan)" class="text-sm">
                                            {{ getPredikatLabel(selectedPeserta.predikat_keseluruhan) }}
                                        </Badge>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <!-- Detail Table untuk Peserta Terpilih -->
                    <div v-if="selectedPeserta" class="space-y-6">
                        <!-- Tabel Detail Per Aspek -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Detail Per Aspek</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="px-4 py-2 text-left">Aspek</th>
                                                <th class="px-4 py-2 text-right">Nilai Performa</th>
                                                <th class="px-4 py-2 text-center">Predikat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="hasilAspek in selectedPeserta.aspek"
                                                :key="hasilAspek.aspek_id"
                                                class="border-b hover:bg-muted/50"
                                            >
                                                <td class="px-4 py-2 font-medium">{{ hasilAspek.nama }}</td>
                                                <td class="px-4 py-2 text-right">{{ formatPersentase(hasilAspek.nilai_performa) }}</td>
                                                <td class="px-4 py-2 text-center">
                                                    <Badge :class="getPredikatColor(hasilAspek.predikat)" class="text-xs">
                                                        {{ getPredikatLabel(hasilAspek.predikat) }}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Tabel Detail Item Tes -->
                        <Card v-if="selectedPeserta.item_tes && selectedPeserta.item_tes.length > 0">
                            <CardHeader>
                                <CardTitle>Detail Item Tes</CardTitle>
                                <CardDescription>
                                    Detail hasil tes per item dengan target dan predikat
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="space-y-6">
                                    <div
                                        v-for="aspek in aspekList"
                                        :key="aspek.id"
                                        v-show="getItemTesByAspek(selectedPeserta.item_tes, aspek.id).length > 0"
                                        class="space-y-3"
                                    >
                                        <h3 class="text-lg font-semibold">{{ aspek.nama }}</h3>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b">
                                                        <th class="px-4 py-2 text-left">Item Tes</th>
                                                        <th class="px-4 py-2 text-left">Satuan</th>
                                                        <th class="px-4 py-2 text-center">Target</th>
                                                        <th class="px-4 py-2 text-center">Nilai</th>
                                                        <th class="px-4 py-2 text-right">Persentase</th>
                                                        <th class="px-4 py-2 text-center">Predikat</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-for="itemTes in getItemTesByAspek(selectedPeserta.item_tes, aspek.id)"
                                                        :key="itemTes.item_tes_id"
                                                        class="border-b hover:bg-muted/50"
                                                    >
                                                        <td class="px-4 py-2 font-medium">{{ itemTes.nama }}</td>
                                                        <td class="px-4 py-2">{{ itemTes.satuan || '-' }}</td>
                                                        <td class="px-4 py-2 text-center">{{ itemTes.target || '-' }}</td>
                                                        <td class="px-4 py-2 text-center">{{ itemTes.nilai || '-' }}</td>
                                                        <td class="px-4 py-2 text-right">{{ formatPersentase(itemTes.persentase_performa) }}</td>
                                                        <td class="px-4 py-2 text-center">
                                                            <Badge :class="getPredikatColor(itemTes.predikat)" class="text-xs">
                                                                {{ getPredikatLabel(itemTes.predikat) }}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </template>
    </PageShow>
</template>
