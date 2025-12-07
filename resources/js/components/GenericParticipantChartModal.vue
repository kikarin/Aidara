<script setup lang="ts">
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
    rencanaList: any[]; // Generic rencana list (pemeriksaan)
    dataType: 'pemeriksaan'; // Type identifier
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
        const dateA = a.tanggal_pemeriksaan;
        const dateB = b.tanggal_pemeriksaan;
        return new Date(dateA).getTime() - new Date(dateB).getTime();
    });

    const chartDataResult = sortedRencana
        .map((rencana) => {
            const statistik = props.statistikData.find((item) => {
                // Hanya untuk pemeriksaan
                return item.peserta_id === props.participant!.id && item.tanggal_pemeriksaan === rencana.tanggal_pemeriksaan;
            });

            const nilai = statistik ? parseFloat(statistik.nilai) : null;
            const persentasePerforma = statistik?.persentase_performa ?? null;

            const dataPoint: any = {
                tanggal: new Date(rencana.tanggal_pemeriksaan).toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                }),
                pemeriksaan: rencana.nama_pemeriksaan,
                rawNilai: statistik?.nilai || null,
            };

            if (nilai !== null && !isNaN(nilai)) {
                dataPoint[props.participant!.nama] = nilai;
            }

            return dataPoint;
        })
        .filter((item) => item[props.participant!.nama] !== undefined);

    return chartDataResult;
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
                            <!-- Line Chart untuk Pemeriksaan -->
                            <LineChart
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
                                                Pemeriksaan
                                            </th>
                                            <th class="p-3 text-right font-medium">Nilai</th>
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
                                                {{ item[participant?.nama || ''] }}
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
