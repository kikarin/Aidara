<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { Award, ChevronDown, ChevronUp, Loader2, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    atletId: number;
}>();

const { toast } = useToast();
const loading = ref(false);
const pemeriksaanData = ref<any[]>([]);
const expandedPemeriksaan = ref<Set<number>>(new Set());

// State untuk modal perbandingan 3 tes terakhir
const isComparisonModalOpen = ref(false);
const comparisonData = ref<any[]>([]);
const comparisonAspekList = ref<any[]>([]);
const loadingComparison = ref(false);

const loadData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/atlet/${props.atletId}/pemeriksaan-khusus`);
        if (response.data.success) {
            pemeriksaanData.value = response.data.data || [];
            // Expand pemeriksaan terbaru secara default
            if (pemeriksaanData.value.length > 0) {
                expandedPemeriksaan.value.add(pemeriksaanData.value[0].pemeriksaan_id);
            }
        } else {
            toast({ title: response.data.message || 'Gagal mengambil data', variant: 'destructive' });
        }
    } catch (error: any) {
        console.error('Error loading pemeriksaan khusus:', error);
        toast({ title: error.response?.data?.message || 'Gagal mengambil data', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

const toggleExpand = (pemeriksaanId: number) => {
    if (expandedPemeriksaan.value.has(pemeriksaanId)) {
        expandedPemeriksaan.value.delete(pemeriksaanId);
    } else {
        expandedPemeriksaan.value.add(pemeriksaanId);
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatPersentase = (value: number | null): string => {
    if (value === null || value === undefined) return '-';
    return `${value.toFixed(1)}%`;
};

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

// Open modal perbandingan 3 tes terakhir
const openComparisonModal = async () => {
    isComparisonModalOpen.value = true;
    comparisonData.value = [];
    comparisonAspekList.value = [];
    loadingComparison.value = true;

    try {
        const response = await axios.get(`/api/atlet/${props.atletId}/last-three-pemeriksaan-khusus`);
        if (response.data.success && response.data.data.length > 0) {
            const data = response.data.data;
            comparisonData.value = data;
            
            // Ambil aspek list dari pemeriksaan pertama
            if (data[0]?.aspek_list && data[0].aspek_list.length > 0) {
                comparisonAspekList.value = data[0].aspek_list;
            } else {
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
    comparisonData.value = [];
    comparisonAspekList.value = [];
};

// Helper: Get tes label (TES I, TES II, TES III) - terbalik karena data sudah diurutkan desc
const getTesLabel = (index: number, total: number): string => {
    const tesNumber = total - index;
    if (tesNumber === 1) return 'TES I';
    if (tesNumber === 2) return 'TES II';
    if (tesNumber === 3) return 'TES III';
    return `TES ${tesNumber}`;
};

// Helper: Convert to number safely
const toNumber = (value: any): number | null => {
    if (value === null || value === undefined || value === '') return null;
    const num = typeof value === 'string' ? parseFloat(value) : Number(value);
    return isNaN(num) ? null : num;
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
        
        // Mapping data berdasarkan nama aspek
        return {
            name: label,
            data: comparisonAspekList.value.map((aspek: any) => {
                const aspekNamaNormalized = (aspek.nama || '').trim().toLowerCase();
                const hasilAspek = pemeriksaan.aspek?.find((a: any) => {
                    const aNamaNormalized = (a.nama || '').trim().toLowerCase();
                    return aNamaNormalized === aspekNamaNormalized;
                });
                const nilai = toNumber(hasilAspek?.nilai_performa);
                return nilai !== null && nilai !== undefined ? nilai : 0;
            }),
        };
    });
});

onMounted(() => {
    loadData();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Button untuk melihat grafik perbandingan -->
        <div v-if="!loading && pemeriksaanData.length >= 2" class="flex justify-end">
            <Button @click="openComparisonModal" variant="outline">
                <TrendingUp class="h-4 w-4 mr-2" />
                Lihat Grafik Perbandingan 3 Tes Terakhir
            </Button>
        </div>

        <div v-if="loading" class="py-8 text-center">
            <Loader2 class="h-8 w-8 animate-spin mx-auto" />
            <p class="text-muted-foreground mt-2">Memuat data pemeriksaan khusus...</p>
        </div>

        <div v-else-if="pemeriksaanData.length === 0" class="py-8 text-center">
            <p class="text-muted-foreground">Belum ada data pemeriksaan khusus untuk atlet ini</p>
        </div>

        <div v-else class="space-y-4">
            <Card
                v-for="pemeriksaan in pemeriksaanData"
                :key="pemeriksaan.pemeriksaan_id"
                class="overflow-hidden"
            >
                <Collapsible>
                    <CollapsibleTrigger
                        @click="toggleExpand(pemeriksaan.pemeriksaan_id)"
                        class="w-full"
                    >
                        <CardHeader class="cursor-pointer hover:bg-muted/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <CardTitle class="text-lg">{{ pemeriksaan.nama_pemeriksaan }}</CardTitle>
                                    <CardDescription class="mt-1">
                                        {{ formatDate(pemeriksaan.tanggal_pemeriksaan) }}
                                        <span class="mx-2">•</span>
                                        {{ pemeriksaan.cabor_nama }} - {{ pemeriksaan.cabor_kategori_nama }}
                                    </CardDescription>
                                    <div class="mt-2 flex items-center gap-2">
                                        <Badge :class="getPredikatColor(pemeriksaan.predikat_keseluruhan)" class="text-white">
                                            {{ getPredikatLabel(pemeriksaan.predikat_keseluruhan) }}
                                        </Badge>
                                        <span class="text-sm font-semibold">
                                            Nilai Keseluruhan: {{ formatPersentase(pemeriksaan.nilai_keseluruhan) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <ChevronDown
                                        v-if="!expandedPemeriksaan.has(pemeriksaan.pemeriksaan_id)"
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                    <ChevronUp
                                        v-else
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                </div>
                            </div>
                        </CardHeader>
                    </CollapsibleTrigger>

                    <CollapsibleContent>
                        <CardContent class="pt-0">
                            <div class="space-y-6">
                                <!-- Aspek dan Item Tes -->
                                <div
                                    v-for="aspek in pemeriksaan.aspek"
                                    :key="aspek.id"
                                    class="border rounded-lg p-4"
                                >
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-semibold text-lg">{{ aspek.nama }}</h4>
                                        <div class="flex items-center gap-2">
                                            <Badge :class="getPredikatColor(aspek.predikat)" class="text-white">
                                                {{ getPredikatLabel(aspek.predikat) }}
                                            </Badge>
                                            <span class="text-sm font-medium">
                                                {{ formatPersentase(aspek.nilai_performa) }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Item Tes -->
                                    <div v-if="aspek.item_tes && aspek.item_tes.length > 0">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Item Tes</TableHead>
                                                    <TableHead class="text-center">Nilai</TableHead>
                                                    <TableHead class="text-center">Persentase Performa</TableHead>
                                                    <TableHead class="text-center">Predikat</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow
                                                    v-for="itemTes in aspek.item_tes"
                                                    :key="itemTes.id"
                                                >
                                                    <TableCell class="font-medium">
                                                        {{ itemTes.nama }}
                                                        <span v-if="itemTes.satuan" class="text-muted-foreground text-sm">
                                                            ({{ itemTes.satuan }})
                                                        </span>
                                                    </TableCell>
                                                    <TableCell class="text-center">
                                                        {{ itemTes.nilai !== null ? itemTes.nilai : '-' }}
                                                    </TableCell>
                                                    <TableCell class="text-center">
                                                        {{ formatPersentase(itemTes.persentase_performa) }}
                                                    </TableCell>
                                                    <TableCell class="text-center">
                                                        <Badge :class="getPredikatColor(itemTes.predikat)" class="text-white text-xs">
                                                            {{ getPredikatLabel(itemTes.predikat) }}
                                                        </Badge>
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground text-center py-4">
                                        Tidak ada item tes
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </CollapsibleContent>
                </Collapsible>
            </Card>
        </div>

        <!-- Modal Radar Chart Perbandingan 3 Tes Terakhir -->
        <Dialog :open="isComparisonModalOpen" @update:open="(val: boolean) => !val && closeComparisonModal()">
            <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2">
                        <Award class="h-5 w-5" />
                        Perbandingan 3 Tes Terakhir
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
                                {{ formatDate(pemeriksaan.tanggal_pemeriksaan) }}
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

