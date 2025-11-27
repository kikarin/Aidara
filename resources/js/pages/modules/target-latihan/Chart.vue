<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LineChart } from '@/components/ui/chart-line';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { BarChart3 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    infoHeader?: any;
}>();

const info = computed(() => props.infoHeader || {});

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${info.value.program_latihan_id}/target-latihan/${info.value.jenis_target}` },
    {
        title: 'Statistik',
        href: `/program-latihan/${info.value.program_latihan_id}/target-latihan/${info.value.jenis_target}/${info.value.target_id}/statistik`,
    },
    { title: 'Grafik', href: '#' },
];

// State untuk data
const statistikData = ref<any[]>([]);
const pesertaList = ref<any[]>([]);
const rencanaLatihanList = ref<any[]>([]);
const targetInfo = ref<any>(null);
const loading = ref(false);
const chartData = ref<any[]>([]);

// State untuk filter
const selectedPesertaType = ref<string>('atlet');

// Fetch data saat component mount
onMounted(async () => {
    await loadTargetInfo();
    await loadStatistikData();
});

// Load target info untuk mendapatkan jenis peserta
const loadTargetInfo = async () => {
    try {
        const response = await axios.get(`/api/target-latihan/${info.value.target_id}`);
        if (response.data && response.data.peruntukan) {
            selectedPesertaType.value = response.data.peruntukan;
        }
    } catch (error) {
        console.error('Error loading target info:', error);
        // Fallback ke atlet jika error
        selectedPesertaType.value = 'atlet';
    }
};

// Load statistik data berdasarkan target yang dipilih
const loadStatistikData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/target-latihan/statistik', {
            params: {
                program_latihan_id: info.value.program_latihan_id,
                target_latihan_id: info.value.target_id,
                jenis_peserta: selectedPesertaType.value,
            },
        });

        statistikData.value = response.data.data || [];
        rencanaLatihanList.value = response.data.rencana_latihan || [];
        pesertaList.value = response.data.peserta || [];
        targetInfo.value = response.data.target_info || null;

        // Generate chart data
        generateChartData();
    } catch (error) {
        console.error('Error loading statistik:', error);
        toast({ title: 'Gagal mengambil data statistik', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Generate data untuk chart
const generateChartData = () => {
    const sortedRencana = rencanaLatihanList.value.sort((a, b) => new Date(a.tanggal).getTime() - new Date(b.tanggal).getTime());

    const chartDataArray = sortedRencana.map((rencana) => {
        const dataPoint: any = {
            tanggal: new Date(rencana.tanggal).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
            }),
            fullDate: rencana.tanggal,
        };

        // Tambahkan data untuk setiap peserta
        pesertaList.value.forEach((peserta) => {
            const statistik = statistikData.value.find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id);

            if (statistik && statistik.nilai) {
                dataPoint[peserta.nama] = parseFloat(statistik.nilai) || 0;
            } else {
                dataPoint[peserta.nama] = 0; // shadcn-vue chart tidak support null
            }
        });

        return dataPoint;
    });

    chartData.value = chartDataArray;
};

// Get categories untuk chart (nama-nama peserta)
const chartCategories = computed(() => {
    return pesertaList.value.map((peserta) => peserta.nama);
});

// Generate colors untuk setiap peserta
const getParticipantColor = (index: number) => {
    const colors = [
        '#8884d8',
        '#82ca9d',
        '#ffc658',
        '#ff7300',
        '#00ff00',
        '#ff00ff',
        '#00ffff',
        '#ffff00',
        '#ff0000',
        '#0000ff',
        '#800080',
        '#008000',
        '#ffa500',
        '#ff69b4',
        '#40e0d0',
    ];
    return colors[index % colors.length];
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="flex items-center gap-2 text-2xl font-bold">
                        <BarChart3 class="h-6 w-6" />
                        Grafik Statistik Target Latihan
                    </h1>
                </div>
                <div class="flex gap-2"></div>
            </div>

            <!-- Chart -->
            <Card class="mx-auto w-auto bg-gray-100 dark:bg-neutral-900" v-if="chartData.length > 0 && !loading">
                <CardHeader>
                    <CardTitle>Grafik Perkembangan Nilai Peserta</CardTitle>
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
