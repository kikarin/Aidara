<script setup lang="ts">
import GenericParticipantChartModal from '@/components/GenericParticipantChartModal.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { BarChart3 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    atletId: number;
}>();

const { toast } = useToast();

// State
const rekapData = ref<any[]>([]);
const loading = ref(false);
const selectedTarget = ref<any>(null);
const isModalOpen = ref(false);

// Load data
onMounted(async () => {
    await loadRekapData();
});

const loadRekapData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/atlet/${props.atletId}/rekap-latihan`);
        if (response.data.success) {
            rekapData.value = response.data.data || [];
        } else {
            toast({ title: response.data.message || 'Gagal mengambil data rekap latihan', variant: 'destructive' });
        }
    } catch (error: any) {
        console.error('Error loading rekap latihan:', error);
        toast({ title: error.response?.data?.message || 'Gagal mengambil data rekap latihan', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Format tanggal
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// Get trend icon
const getTrendIcon = (trend: string) => {
    switch (trend) {
        case 'naik':
            return '↑';
        case 'turun':
            return '↓';
        case 'stabil':
        default:
            return '→';
    }
};

// Get trend color
const getTrendColor = (trend: string) => {
    switch (trend) {
        case 'naik':
            return 'text-green-600';
        case 'turun':
            return 'text-red-600';
        case 'stabil':
        default:
            return 'text-yellow-600';
    }
};

// Get performa color
const getPerformaColor = (persentase: number | null) => {
    if (persentase === null) return 'text-gray-400';
    if (persentase > 75) return 'text-red-600';
    if (persentase >= 40) return 'text-yellow-600';
    return 'text-green-600';
};

const currentTarget = ref<any>(null);

// Open modal untuk melihat grafik
const openChartModal = (target: any) => {
    currentTarget.value = target;
    selectedTarget.value = {
        id: props.atletId,
        nama: 'Atlet',
    };
    isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedTarget.value = null;
    currentTarget.value = null;
};

// Prepare data untuk chart modal
const chartStatistikData = computed(() => {
    if (!currentTarget.value) return [];

    return currentTarget.value.rencana_list.map((rencana: any) => ({
        peserta_id: props.atletId,
        rencana_latihan_id: rencana.rencana_id,
        nilai: rencana.nilai,
        trend: rencana.trend,
        tanggal: rencana.tanggal,
        persentase_performa: rencana.persentase_performa,
    }));
});

const chartRencanaList = computed(() => {
    if (!currentTarget.value) return [];

    return currentTarget.value.rencana_list.map((rencana: any) => ({
        id: rencana.rencana_id,
        tanggal: rencana.tanggal,
        materi: rencana.materi,
    }));
});

const chartTargetInfo = computed(() => {
    if (!currentTarget.value) return null;

    return {
        id: currentTarget.value.target_id,
        deskripsi: currentTarget.value.deskripsi,
        nilai_target: currentTarget.value.nilai_target,
        satuan: currentTarget.value.satuan,
        performa_arah: currentTarget.value.performa_arah,
    };
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="loading" class="py-8 text-center">
            <p class="text-muted-foreground">Memuat data rekap latihan...</p>
        </div>

        <div v-else-if="rekapData.length === 0" class="py-8 text-center">
            <p class="text-muted-foreground">Belum ada data rekap latihan untuk atlet ini</p>
        </div>

        <div v-else class="space-y-6">
            <Card v-for="target in rekapData" :key="target.target_id" class="overflow-hidden">
                <CardHeader class="bg-gray-50 dark:bg-neutral-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">{{ target.deskripsi }}</CardTitle>
                            <p class="text-muted-foreground mt-1 text-sm">
                                Program: {{ target.nama_program }} | Target: {{ target.nilai_target }} {{ target.satuan || '' }}
                            </p>
                        </div>
                        <button
                            class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors"
                            @click="openChartModal(target)"
                        >
                            <BarChart3 class="h-4 w-4" />
                            Lihat Grafik
                        </button>
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead class="bg-gray-100 dark:bg-neutral-800">
                                <tr class="border-b">
                                    <th class="p-3 text-left font-medium">Tanggal</th>
                                    <th class="p-3 text-left font-medium">Materi</th>
                                    <th class="p-3 text-right font-medium">Nilai</th>
                                    <th class="p-3 text-right font-medium">Persentase Performa</th>
                                    <th class="p-3 text-center font-medium">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(rencana, index) in target.rencana_list"
                                    :key="index"
                                    class="border-b hover:bg-gray-50 dark:hover:bg-neutral-800"
                                >
                                    <td class="p-3">{{ formatDate(rencana.tanggal) }}</td>
                                    <td class="p-3">{{ rencana.materi }}</td>
                                    <td class="p-3 text-right font-medium">{{ rencana.nilai || '-' }}</td>
                                    <td class="p-3 text-right font-medium" :class="getPerformaColor(rencana.persentase_performa)">
                                        {{ rencana.persentase_performa !== null ? `${rencana.persentase_performa.toFixed(2)}%` : '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span :class="getTrendColor(rencana.trend)">{{ getTrendIcon(rencana.trend) }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Chart Modal -->
        <GenericParticipantChartModal
            v-if="selectedTarget && currentTarget"
            :is-open="isModalOpen"
            :participant="selectedTarget"
            :statistik-data="chartStatistikData"
            :rencana-list="chartRencanaList"
            data-type="target-latihan"
            :target-info="chartTargetInfo"
            @close="closeModal"
        />
    </div>
</template>
