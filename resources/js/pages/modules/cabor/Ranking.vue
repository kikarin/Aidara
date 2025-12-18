<script setup lang="ts">
import AppTabs from '@/components/AppTabs.vue';
import ApexChart from '@/components/ApexChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { Award, Loader2, TrendingUp, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    caborId: number;
    caborNama: string;
}>();

// Tab management untuk sub-tab
const activeSubTab = ref<string>('total-rata-rata');
const rankingData = ref<any>(null);
const loadingRanking = ref(false);
const selectedPesertaForModal = ref<any>(null);
const isModalOpen = ref(false);
const modalAspekList = ref<any[]>([]);
const modalVisualisasiData = ref<any>(null);

// State untuk modal perbandingan 3 tes terakhir
const isComparisonModalOpen = ref(false);
const comparisonData = ref<any[]>([]);
const comparisonAspekList = ref<any[]>([]);
const loadingComparison = ref(false);

// Fetch ranking data
const fetchRankingData = async () => {
    loadingRanking.value = true;
    try {
        const response = await axios.get(`/api/cabor/${props.caborId}/ranking`);
        rankingData.value = response.data.data || null;
    } catch (error: any) {
        console.error('Error fetching ranking data:', error);
        toast({
            title: 'Gagal mengambil data ranking',
            variant: 'destructive',
        });
        rankingData.value = null;
    } finally {
        loadingRanking.value = false;
    }
};

// Format percentage
const formatPersentase = (value: number | null): string => {
    if (value === null || value === undefined) return '-';
    return `${value.toFixed(1)}%`;
};

// Get predikat label
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

// Get predikat color
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

// Get rank badge color
const getRankBadgeColor = (rank: number): string => {
    if (rank === 1) return 'bg-yellow-500 text-white';
    if (rank === 2) return 'bg-gray-400 text-white';
    if (rank === 3) return 'bg-orange-500 text-white';
    return 'bg-gray-300 text-gray-700';
};

// Get rank icon
const getRankIcon = (rank: number): string => {
    if (rank === 1) return '🥇';
    if (rank === 2) return '🥈';
    if (rank === 3) return '🥉';
    return `${rank}`;
};

// Open modal dengan radar chart
const openModal = async (peserta: any, pemeriksaanId?: number) => {
    selectedPesertaForModal.value = peserta;
    isModalOpen.value = true;
    modalVisualisasiData.value = null;
    modalAspekList.value = [];

    // Load data visualisasi untuk peserta ini
    let targetPemeriksaanId = pemeriksaanId;
    
    // Jika tidak ada pemeriksaanId (tab Total Rata-rata), ambil pemeriksaan pertama yang punya data
    if (!targetPemeriksaanId && rankingData.value?.pemeriksaan_list?.length > 0) {
        // Cari pemeriksaan pertama yang punya data untuk peserta ini
        const rankingPerTes = rankingData.value.ranking_per_tes || [];
        const firstPemeriksaanWithData = rankingPerTes.find((r: any) => 
            r.data?.some((d: any) => d.peserta_id === peserta.peserta_id)
        );
        if (firstPemeriksaanWithData) {
            targetPemeriksaanId = firstPemeriksaanWithData.pemeriksaan_id;
        } else {
            // Fallback: gunakan pemeriksaan pertama
            targetPemeriksaanId = rankingData.value.pemeriksaan_list[0]?.id;
        }
    }
    
    if (targetPemeriksaanId) {
        try {
            const res = await axios.get(`/api/pemeriksaan-khusus/${targetPemeriksaanId}/visualisasi`);
            if (res.data.success) {
                const allData = res.data.data || [];
                modalVisualisasiData.value = allData.find((d: any) => {
                    // Match by peserta id
                    const dPesertaId = d.peserta?.id;
                    return dPesertaId === peserta.peserta_id;
                });
                modalAspekList.value = res.data.aspek_list || [];
            }
        } catch (error: any) {
            console.error('Error loading visualisasi data:', error);
            toast({
                title: 'Gagal memuat data visualisasi',
                variant: 'destructive',
            });
        }
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedPesertaForModal.value = null;
    modalVisualisasiData.value = null;
    modalAspekList.value = [];
};

// Open modal perbandingan 3 tes terakhir
const openComparisonModal = async (peserta: any, event?: Event) => {
    if (event) {
        event.stopPropagation();
    }
    
    selectedPesertaForModal.value = peserta;
    isComparisonModalOpen.value = true;
    comparisonData.value = [];
    comparisonAspekList.value = [];
    loadingComparison.value = true;

    try {
        const response = await axios.get(`/api/cabor/${props.caborId}/atlet/${peserta.peserta_id}/last-three-pemeriksaan`);
        if (response.data.success && response.data.data.length > 0) {
            const data = response.data.data;
            // Pastikan data diurutkan dari terbaru ke terlama (sudah diurutkan di backend)
            comparisonData.value = data;
            
            // Ambil aspek list dari pemeriksaan pertama (semua pemeriksaan harus punya aspek yang sama)
            if (data[0]?.aspek_list && data[0].aspek_list.length > 0) {
                comparisonAspekList.value = data[0].aspek_list;
            } else {
                // Fallback: ambil dari pemeriksaan manapun yang punya aspek_list
                const pemeriksaanDenganAspek = data.find((p: any) => p.aspek_list && p.aspek_list.length > 0);
                if (pemeriksaanDenganAspek) {
                    comparisonAspekList.value = pemeriksaanDenganAspek.aspek_list;
                }
            }
            
        } else {
            toast({
                title: 'Tidak ada data',
                variant: 'default',
            });
        }
    } catch (error: any) {
        console.error('Error loading comparison data:', error);
        toast({
            title: 'Gagal memuat data perbandingan',
            variant: 'destructive',
        });
    } finally {
        loadingComparison.value = false;
    }
};

const closeComparisonModal = () => {
    isComparisonModalOpen.value = false;
    selectedPesertaForModal.value = null;
    comparisonData.value = [];
    comparisonAspekList.value = [];
};

// Helper: Convert to number safely
const toNumber = (value: any): number | null => {
    if (value === null || value === undefined || value === '') return null;
    const num = typeof value === 'string' ? parseFloat(value) : Number(value);
    return isNaN(num) ? null : num;
};

// Radar chart options untuk modal
const modalRadarChartOptions = computed(() => {
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
            categories: modalAspekList.value.length > 0 ? modalAspekList.value.map((a: any) => a.nama) : [''],
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

// Helper: Get tes label (TES I, TES II, TES III) - terbalik karena data sudah diurutkan desc
const getTesLabel = (index: number, total: number): string => {
    const tesNumber = total - index;
    if (tesNumber === 1) return 'TES I';
    if (tesNumber === 2) return 'TES II';
    if (tesNumber === 3) return 'TES III';
    return `TES ${tesNumber}`;
};

// Radar chart options untuk modal perbandingan
const comparisonRadarChartOptions = computed(() => {
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
            categories: comparisonAspekList.value.length > 0 
                ? comparisonAspekList.value.map((a: any) => a.nama) 
                : [''],
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
        colors: ['#3b82f6', '#10b981', '#f59e0b'], // Biru (terbaru), Hijau, Orange (terlama)
        legend: {
            show: true,
            position: 'bottom',
            labels: {
                colors: isDark ? '#9ca3af' : '#6b7280',
            },
        },
    };
});

// Radar chart series untuk modal perbandingan
const comparisonRadarChartSeries = computed(() => {
    if (!comparisonData.value.length || comparisonAspekList.value.length === 0) return [];

    return comparisonData.value.map((pemeriksaan: any, index: number) => {
        const tesLabel = getTesLabel(index, comparisonData.value.length);
        const tanggal = new Date(pemeriksaan.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
        const label = `${tesLabel} (${tanggal})`;
        
        // Mapping data berdasarkan nama aspek (karena aspek_id bisa berbeda antar pemeriksaan)
        return {
            name: label,
            data: comparisonAspekList.value.map((aspek: any) => {
                // Normalisasi nama untuk matching yang lebih baik
                const aspekNamaNormalized = (aspek.nama || '').trim().toLowerCase();
                
                // Cari hasil aspek berdasarkan nama aspek (dengan normalisasi)
                const hasilAspek = pemeriksaan.aspek?.find((a: any) => {
                    const aNamaNormalized = (a.nama || '').trim().toLowerCase();
                    return aNamaNormalized === aspekNamaNormalized;
                });
                
                const nilai = toNumber(hasilAspek?.nilai_performa);
                // Jika tidak ada nilai, return 0 agar chart tetap muncul
                return nilai !== null && nilai !== undefined ? nilai : 0;
            }),
        };
    });
});

// Radar chart series untuk modal
const modalRadarChartSeries = computed(() => {
    if (!modalVisualisasiData.value || modalAspekList.value.length === 0) return [];

    return [
        {
            name: selectedPesertaForModal.value?.nama || 'Peserta',
            data: modalAspekList.value.map((aspek: any) => {
                const hasilAspek = modalVisualisasiData.value?.aspek?.find((a: any) => a.aspek_id === aspek.id);
                const nilai = toNumber(hasilAspek?.nilai_performa);
                return nilai !== null ? nilai : 0;
            }),
        },
    ];
});

// Sub-tabs config
const subTabsConfig = computed(() => {
    if (!rankingData.value?.pemeriksaan_list) return [];
    
    const tabs = [
        {
            value: 'total-rata-rata',
            label: 'Total Rata-rata',
        },
    ];

    // Add tab for each pemeriksaan
    rankingData.value.pemeriksaan_list.forEach((p: any) => {
        tabs.push({
            value: `tes-${p.id}`,
            label: p.nama_pemeriksaan,
        });
    });

    return tabs;
});

// Get ranking data for current sub-tab
const currentRankingData = computed(() => {
    if (!rankingData.value) return [];
    
    if (activeSubTab.value === 'total-rata-rata') {
        return rankingData.value.ranking_total_rata_rata || [];
    }
    
    // Get pemeriksaan ID from sub-tab value
    const pemeriksaanId = parseInt(activeSubTab.value.replace('tes-', ''));
    return rankingData.value.ranking_per_tes?.find((r: any) => r.pemeriksaan_id === pemeriksaanId)?.data || [];
});

// Get pemeriksaan info for current sub-tab
const currentPemeriksaanInfo = computed(() => {
    if (!rankingData.value || activeSubTab.value === 'total-rata-rata') return null;
    
    const pemeriksaanId = parseInt(activeSubTab.value.replace('tes-', ''));
    return rankingData.value.pemeriksaan_list?.find((p: any) => p.id === pemeriksaanId);
});

// Initialize
onMounted(() => {
    fetchRankingData();
});
</script>

<template>
    <div class="space-y-4">
        <Card v-if="loadingRanking">
            <CardContent class="flex items-center justify-center py-8">
                <Loader2 class="h-8 w-8 animate-spin" />
            </CardContent>
        </Card>

        <Card v-else-if="rankingData">
            <CardHeader>
                <CardTitle>Ranking Peserta</CardTitle>
                <CardDescription>Ranking berdasarkan nilai keseluruhan pemeriksaan khusus</CardDescription>
            </CardHeader>
            <CardContent>
                <!-- Sub-tabs -->
                <div class="mb-6">
                    <AppTabs :tabs="subTabsConfig" :default-value="'total-rata-rata'" v-model="activeSubTab" />
                </div>

                <!-- Ranking Table -->
                <div v-if="currentRankingData.length > 0" class="overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16 text-center">Rank</TableHead>
                                <TableHead>Nama Peserta</TableHead>
                                <TableHead class="text-center">Jenis Kelamin</TableHead>
                                <TableHead class="text-center">Usia</TableHead>
                                <TableHead class="text-center">Posisi</TableHead>
                                <TableHead class="text-center">Nilai</TableHead>
                                <TableHead class="text-center">Predikat</TableHead>
                                <TableHead v-if="activeSubTab !== 'total-rata-rata'" class="text-center">Tanggal Tes</TableHead>
                                <TableHead class="text-center w-32">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="(item, index) in currentRankingData"
                                :key="item.peserta_id"
                                class="hover:bg-muted/50"
                            >
                                <TableCell class="text-center">
                                    <Badge :class="getRankBadgeColor(index + 1)" class="px-3 py-1">
                                        {{ getRankIcon(index + 1) }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="font-medium">{{ item.nama }}</TableCell>
                                <TableCell class="text-center">
                                    {{ item.jenis_kelamin === 'L' || item.jenis_kelamin === 'Laki-laki' ? 'Laki-laki' : 'Perempuan' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    {{ item.usia !== '-' ? item.usia + ' tahun' : '-' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    {{ item.posisi !== '-' ? item.posisi : '-' }}
                                </TableCell>
                                <TableCell class="text-center font-semibold">
                                    {{ formatPersentase(item.nilai) }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <Badge :class="getPredikatColor(item.predikat)" class="text-xs">
                                        {{ getPredikatLabel(item.predikat) }}
                                    </Badge>
                                </TableCell>
                                <TableCell v-if="activeSubTab !== 'total-rata-rata'" class="text-center">
                                    {{ currentPemeriksaanInfo ? new Date(currentPemeriksaanInfo.tanggal_pemeriksaan).toLocaleDateString('id-ID') : '-' }}
                                </TableCell>
                                <TableCell class="text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openModal(item, activeSubTab !== 'total-rata-rata' ? currentPemeriksaanInfo?.id : null)"
                                            class="h-8"
                                        >
                                            <TrendingUp class="h-4 w-4 mr-1" />
                                            Grafik
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            @click="openComparisonModal(item, $event)"
                                            class="h-8"
                                        >
                                            <Award class="h-4 w-4 mr-1" />
                                            Perbandingan
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    Tidak ada data ranking
                </div>
            </CardContent>
        </Card>

        <Card v-else>
            <CardContent class="text-center py-8 text-muted-foreground">
                Belum ada data ranking. Pastikan sudah ada pemeriksaan khusus dengan hasil tes.
            </CardContent>
        </Card>

        <!-- Modal Radar Chart -->
        <Dialog :open="isModalOpen" @update:open="(val: boolean) => !val && closeModal()">
            <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <TrendingUp class="h-5 w-5" />
                        Grafik Performa Aspek - {{ selectedPesertaForModal?.nama }}
                    </DialogTitle>
                </DialogHeader>
                <div v-if="modalVisualisasiData && modalAspekList.length > 0" class="space-y-4">
                    <ApexChart :options="modalRadarChartOptions" :series="modalRadarChartSeries" />
                </div>
                <div v-else class="flex items-center justify-center py-8">
                    <Loader2 class="h-8 w-8 animate-spin" />
                </div>
            </DialogContent>
        </Dialog>

        <!-- Modal Radar Chart Perbandingan 3 Tes Terakhir -->
        <Dialog :open="isComparisonModalOpen" @update:open="(val: boolean) => !val && closeComparisonModal()">
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Award class="h-5 w-5" />
                        Perbandingan 3 Tes Terakhir - {{ selectedPesertaForModal?.nama }}
                    </DialogTitle>
                </DialogHeader>
                <div v-if="loadingComparison" class="flex items-center justify-center py-8">
                    <Loader2 class="h-8 w-8 animate-spin" />
                </div>
                <div v-else-if="comparisonData.length > 0 && comparisonAspekList.length > 0" class="space-y-4">
                    <!-- Informasi Tes -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div
                            v-for="(pemeriksaan, index) in comparisonData"
                            :key="pemeriksaan.pemeriksaan_id"
                            class="p-4 border-2 rounded-lg"
                            :class="{
                                'border-blue-500 bg-blue-500/10': index === 0,
                                'border-green-500 bg-green-500/10': index === 1,
                                'border-orange-500 bg-orange-500/10': index === 2,
                            }"
                        >
                            <p class="font-bold text-lg mb-2"
                                :class="{
                                    'text-blue-700 dark:text-blue-400': index === 0,
                                    'text-green-700 dark:text-green-400': index === 1,
                                    'text-orange-700 dark:text-orange-400': index === 2,
                                }"
                            >
                                {{ getTesLabel(index, comparisonData.length) }}
                            </p>
                            <p class="text-sm text-muted-foreground mb-1">
                                {{ new Date(pemeriksaan.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                }) }}
                            </p>
                            <p class="text-sm font-semibold"
                                :class="{
                                    'text-blue-700 dark:text-blue-400': index === 0,
                                    'text-green-700 dark:text-green-400': index === 1,
                                    'text-orange-700 dark:text-orange-400': index === 2,
                                }"
                            >
                                Nilai: {{ formatPersentase(pemeriksaan.nilai_keseluruhan) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Radar Chart -->
                    <ApexChart :options="comparisonRadarChartOptions" :series="comparisonRadarChartSeries" />
                </div>
                <div v-else class="text-center py-8 text-muted-foreground">
                    <p>Tidak ada data perbandingan tersedia</p>
                    <p class="text-sm mt-2">Atlet ini belum memiliki 3 pemeriksaan khusus</p>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>

