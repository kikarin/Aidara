<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LineChart } from '@/components/ui/chart-line';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { BarChart3 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { toast } = useToast();
const page = usePage();

interface Parameter {
    id: number;
    nama: string;
    satuan: string;
}

interface StatistikData {
    peserta_id: number;
    pemeriksaan_peserta_id: number;
    nilai: string;
    trend: string;
    tanggal_pemeriksaan: string;
    pemeriksaan_id: number;
}

interface Peserta {
    id: number;
    nama: string;
    jenis_peserta: string;
    jenis_kelamin: string;
}

interface RencanaPemeriksaan {
    id: number;
    nama_pemeriksaan: string;
    tanggal_pemeriksaan: string;
}

const parameter = computed(() => (page.props.parameter as Parameter) || ({} as Parameter));

const parameterId = computed(() => parameter.value?.id || (typeof window !== 'undefined' ? window.location.pathname.split('/')[3] : ''));

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'All Parameter', href: '/pemeriksaan-parameter/AllParameter' },
    { title: 'Statistik', href: `/pemeriksaan-parameter/AllParameter/${parameterId.value}/statistik` },
    { title: 'Grafik', href: `/pemeriksaan-parameter/AllParameter/${parameterId.value}/chart` },
];

// State untuk data
const statistikData = ref<StatistikData[]>([]);
const pesertaList = ref<Peserta[]>([]);
const rencanaPemeriksaanList = ref<RencanaPemeriksaan[]>([]);
const loading = ref(false);
const chartData = ref<any[]>([]);

// Load data statistik
const loadStatistikData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/pemeriksaan-parameter/AllParameter/${parameterId.value}/statistik`);

        statistikData.value = response.data.data || [];
        pesertaList.value = response.data.peserta || [];
        rencanaPemeriksaanList.value = response.data.rencana_pemeriksaan || [];

        // Generate chart data
        generateChartData();
    } catch (error) {
        console.error('Error loading statistik data:', error);
        toast({ title: 'Gagal memuat data statistik', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Generate chart data
const generateChartData = () => {
    const sortedRencana = [...rencanaPemeriksaanList.value].sort(
        (a, b) => new Date(a.tanggal_pemeriksaan).getTime() - new Date(b.tanggal_pemeriksaan).getTime(),
    );

    chartData.value = sortedRencana.map((rencana) => {
        const dataPoint: any = {
            tanggal: new Date(rencana.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: '2-digit',
            }),
            pemeriksaan: rencana.nama_pemeriksaan,
        };

        // Add data for each participant
        pesertaList.value.forEach((peserta) => {
            const statistik = statistikData.value.find(
                (item) => item.peserta_id === peserta.id && item.tanggal_pemeriksaan === rencana.tanggal_pemeriksaan,
            );
            dataPoint[peserta.nama] = statistik ? parseFloat(statistik.nilai) || 0 : 0;
        });

        return dataPoint;
    });
};

// Chart categories (participant names)
const chartCategories = computed(() => {
    return pesertaList.value.map((peserta) => peserta.nama);
});

// Get participant color
const getParticipantColor = (index: number) => {
    const colors = [
        '#3b82f6', // blue
        '#ef4444', // red
        '#10b981', // green
        '#f59e0b', // yellow
        '#8b5cf6', // purple
        '#06b6d4', // cyan
        '#84cc16', // lime
        '#f97316', // orange
        '#ec4899', // pink
        '#6b7280', // gray
    ];
    return colors[index % colors.length];
};

// Fetch data saat component mount
onMounted(async () => {
    await loadStatistikData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-2xl font-bold">
                        <BarChart3 class="h-6 w-6" />
                        Grafik Parameter
                    </h1>
                </div>
            </div>

            <!-- Chart -->
            <Card class="mx-auto w-auto bg-gray-100 dark:bg-neutral-900" v-if="chartData.length > 0 && !loading">
                <CardHeader>
                    <CardTitle>Grafik Line Parameter</CardTitle>
                </CardHeader>
                <CardContent>
                    <LineChart
                        :data="chartData"
                        :categories="chartCategories"
                        :index="'tanggal'"
                        :colors="pesertaList.map((_: any, index: number) => getParticipantColor(index))"
                        class="h-[500px]"
                    />
                </CardContent>
            </Card>
            <!-- Loading State -->
            <Card v-if="loading">
                <CardContent class="py-12 text-center">
                    <div class="flex items-center justify-center gap-2">
                        <div class="border-primary h-6 w-6 animate-spin rounded-full border-b-2"></div>
                        <p class="text-muted-foreground">Memuat data grafik...</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Empty State -->
            <Card v-else-if="chartData.length === 0">
                <CardContent class="py-12 text-center">
                    <BarChart3 class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                    <p class="text-muted-foreground">Belum ada data untuk ditampilkan dalam grafik</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
