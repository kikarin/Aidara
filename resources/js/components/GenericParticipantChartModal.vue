<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LineChart } from '@/components/ui/chart-line';
import { BarChart3, X } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    isOpen: boolean;
    participant: {
        id: number;
        nama: string;
        jenis_peserta?: string;
    } | null;
    statistikData: any[];
    rencanaList: any[]; // Generic rencana list (bisa pemeriksaan atau latihan)
    dataType: 'pemeriksaan' | 'target-latihan'; // Type identifier
    targetInfo?: any; // Info target latihan untuk menghitung persentase performa
}

const props = defineProps<Props>();
const emit = defineEmits<{
    close: [];
}>();

// Generate chart data for the specific participant
const chartData = computed(() => {
    if (!props.participant || !props.statistikData.length) {
        return [];
    }

    const sortedRencana = [...props.rencanaList].sort((a, b) => {
        const dateA = props.dataType === 'pemeriksaan' ? a.tanggal_pemeriksaan : a.tanggal;
        const dateB = props.dataType === 'pemeriksaan' ? b.tanggal_pemeriksaan : b.tanggal;
        return new Date(dateA).getTime() - new Date(dateB).getTime();
    });

    const chartDataResult = sortedRencana
        .map((rencana) => {
            const statistik = props.statistikData.find((item) => {
                if (props.dataType === 'pemeriksaan') {
                    return item.peserta_id === props.participant!.id && item.tanggal_pemeriksaan === rencana.tanggal_pemeriksaan;
                } else {
                    // Untuk target-latihan atau parameter khusus dengan pemeriksaan
                    if (item.rencana_latihan_id) {
                        return item.peserta_id === props.participant!.id && item.rencana_latihan_id === rencana.id;
                    } else {
                        // Parameter khusus dengan pemeriksaan
                        return item.peserta_id === props.participant!.id && item.tanggal_pemeriksaan === rencana.tanggal_pemeriksaan;
                    }
                }
            });

            const nilai = statistik ? parseFloat(statistik.nilai) : null;
            const persentasePerforma = statistik?.persentase_performa ?? null;

            const dataPoint: any = {
                tanggal: new Date(
                    props.dataType === 'pemeriksaan' ? rencana.tanggal_pemeriksaan : rencana.tanggal || rencana.tanggal_pemeriksaan,
                ).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                }),
                pemeriksaan:
                    props.dataType === 'pemeriksaan'
                        ? rencana.nama_pemeriksaan
                        : rencana.materi ||
                          rencana.nama_pemeriksaan ||
                          `Rencana ${new Date(rencana.tanggal || rencana.tanggal_pemeriksaan).toLocaleDateString('id-ID')}`,
                rawNilai: statistik?.nilai || null,
                persentasePerforma: persentasePerforma,
            };

            // Untuk target-latihan atau parameter khusus dengan persentase performa, gunakan persentase performa
            if (props.dataType === 'target-latihan' && persentasePerforma !== null && !isNaN(persentasePerforma)) {
                dataPoint[props.participant!.nama] = persentasePerforma;
            } else if (nilai !== null && !isNaN(nilai)) {
                dataPoint[props.participant!.nama] = nilai;
            }

            return dataPoint;
        })
        .filter((item) => item[props.participant!.nama] !== undefined);

    return chartDataResult;
});

// Generate bar chart data untuk target-latihan
const barChartOptions = computed(() => {
    if (props.dataType !== 'target-latihan') return null;

    const categories = chartData.value.map((item) => item.tanggal);

    return {
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: true,
            },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 5,
                borderRadiusApplication: 'end',
            },
        },
        dataLabels: {
            enabled: true,
            formatter: (val: number) => `${val.toFixed(1)}%`,
            style: {
                fontSize: '12px',
                fontWeight: 'bold',
            },
        },
        xaxis: {
            categories: categories,
            labels: {
                style: {
                    fontSize: '12px',
                },
            },
        },
        yaxis: {
            title: {
                text: 'Persentase Performa (%)',
            },
            labels: {
                formatter: (val: number) => `${val.toFixed(0)}%`,
            },
        },
        tooltip: {
            y: {
                formatter: (val: number) => `${val.toFixed(2)}%`,
            },
        },
        colors: ['#3b82f6'],
        grid: {
            borderColor: '#e5e7eb',
        },
        fill: {
            type: 'solid',
        },
    };
});

const barChartSeries = computed(() => {
    if (props.dataType !== 'target-latihan' || !props.participant) return [];

    const data = chartData.value.map((item) => {
        const persentase = item[props.participant!.nama] || 0;
        return persentase;
    });

    return [
        {
            name: 'Persentase Performa',
            data: data,
        },
    ];
});

// Get colors array untuk setiap bar
const barChartColors = computed(() => {
    if (props.dataType !== 'target-latihan' || !props.participant) return [];

    return chartData.value.map((item) => {
        const persentase = item[props.participant!.nama] || 0;
        return getBarColorByPerforma(persentase);
    });
});

// Get bar color berdasarkan persentase performa
const getBarColorByPerforma = (persentase: number) => {
    if (persentase > 75) return '#ef4444'; // merah
    if (persentase >= 40) return '#f59e0b'; // kuning
    return '#10b981'; // hijau
};

// Update bar chart options untuk menggunakan warna dinamis
const barChartOptionsWithColors = computed(() => {
    if (!barChartOptions.value) return null;

    return {
        ...barChartOptions.value,
        plotOptions: {
            ...barChartOptions.value.plotOptions,
            bar: {
                ...barChartOptions.value.plotOptions.bar,
                distributed: true, // Enable different colors for each bar
            },
        },
        colors: barChartColors.value.length > 0 ? barChartColors.value : ['#3b82f6'],
    };
});

// Get categories for chart (participant names)
const chartCategories = computed(() => {
    return props.participant ? [props.participant.nama] : [];
});

// Generate colors for the participant
const getParticipantColor = () => {
    return '#8884d8'; // Default blue color for single participant
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="handleClose"></div>

        <!-- Modal -->
        <div class="relative mx-4 flex max-h-[90vh] w-full max-w-4xl flex-col rounded-lg bg-white shadow-xl dark:bg-neutral-900">
            <!-- Header -->
            <CardHeader class="flex flex-shrink-0 flex-row items-center justify-between space-y-0 pb-4">
                <div class="flex items-center gap-2">
                    <BarChart3 class="h-5 w-5" />
                    <CardTitle>Grafik Perkembangan {{ participant?.nama }}</CardTitle>
                </div>
                <Button variant="ghost" size="sm" @click="handleClose">
                    <X class="h-4 w-4" />
                </Button>
            </CardHeader>

            <!-- Content -->
            <CardContent class="flex-1 space-y-6 overflow-y-auto scroll-smooth px-6 pb-6">
                <div v-if="chartData.length === 0" class="py-8 text-center">
                    <BarChart3 class="text-muted-foreground mx-auto mb-4 h-12 w-12" />
                    <p class="text-muted-foreground">Belum ada data untuk ditampilkan</p>
                </div>

                <div v-else>
                    <!-- Chart -->
                    <Card>
                        <CardContent class="pt-6">
                            <!-- Bar Chart untuk Target Latihan -->
                            <ApexChart
                                v-if="dataType === 'target-latihan' && barChartOptionsWithColors && barChartSeries.length > 0"
                                :options="barChartOptionsWithColors"
                                :series="barChartSeries"
                            />
                            <!-- Line Chart untuk Pemeriksaan -->
                            <LineChart
                                v-else
                                :data="chartData"
                                :categories="chartCategories"
                                :index="'tanggal'"
                                :colors="[getParticipantColor()]"
                                class="h-[400px]"
                            />
                        </CardContent>
                    </Card>

                    <!-- Detail Table -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-lg">Detail Data</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="p-3 text-left font-medium">Tanggal</th>
                                            <th class="p-3 text-left font-medium">
                                                {{ dataType === 'pemeriksaan' ? 'Pemeriksaan' : 'Rencana Latihan' }}
                                            </th>
                                            <th class="p-3 text-right font-medium">Nilai</th>
                                            <th v-if="dataType === 'target-latihan'" class="p-3 text-right font-medium">Persentase Performa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, index) in chartData"
                                            :key="index"
                                            class="border-b hover:bg-gray-50 dark:hover:bg-neutral-800"
                                        >
                                            <td class="p-3">{{ item.tanggal }}</td>
                                            <td class="p-3">{{ item.pemeriksaan }}</td>
                                            <td class="p-3 text-right font-medium">
                                                {{ dataType === 'target-latihan' ? item.rawNilai : item[participant?.nama || ''] }}
                                            </td>
                                            <td v-if="dataType === 'target-latihan'" class="p-3 text-right font-medium">
                                                {{ item.persentasePerforma !== null ? `${item.persentasePerforma.toFixed(2)}%` : '-' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </CardContent>
        </div>
    </div>
</template>
