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
const selectedParameter = ref<any>(null);
const isModalOpen = ref(false);

// Load data
onMounted(async () => {
    await loadRekapData();
});

const loadRekapData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/atlet/${props.atletId}/rekap-parameter-khusus`);
        if (response.data.success) {
            rekapData.value = response.data.data || [];
        } else {
            toast({ title: response.data.message || 'Gagal mengambil data rekap parameter khusus', variant: 'destructive' });
        }
    } catch (error: any) {
        console.error('Error loading rekap parameter khusus:', error);
        toast({ title: error.response?.data?.message || 'Gagal mengambil data rekap parameter khusus', variant: 'destructive' });
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
        case 'kenaikan':
            return '↑';
        case 'turun':
        case 'penurunan':
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
        case 'kenaikan':
            return 'text-green-600';
        case 'turun':
        case 'penurunan':
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

const currentParameter = ref<any>(null);

// Open modal untuk melihat grafik
const openChartModal = (parameter: any) => {
    currentParameter.value = parameter;
    selectedParameter.value = {
        id: props.atletId,
        nama: 'Atlet',
    };
    isModalOpen.value = true;
};

// Close modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedParameter.value = null;
    currentParameter.value = null;
};

// Prepare data untuk chart modal
const chartStatistikData = computed(() => {
    if (!currentParameter.value) return [];

    return currentParameter.value.pemeriksaan_list.map((pemeriksaan: any) => ({
        peserta_id: props.atletId,
        pemeriksaan_peserta_id: pemeriksaan.pemeriksaan_id,
        nilai: pemeriksaan.nilai,
        trend: pemeriksaan.trend,
        tanggal_pemeriksaan: pemeriksaan.tanggal,
        persentase_performa: pemeriksaan.persentase_performa,
    }));
});

const chartRencanaList = computed(() => {
    if (!currentParameter.value) return [];

    return currentParameter.value.pemeriksaan_list.map((pemeriksaan: any) => ({
        id: pemeriksaan.pemeriksaan_id,
        tanggal_pemeriksaan: pemeriksaan.tanggal,
        nama_pemeriksaan: pemeriksaan.nama_pemeriksaan,
    }));
});

const chartTargetInfo = computed(() => {
    if (!currentParameter.value) return null;

    return {
        id: currentParameter.value.parameter_id,
        deskripsi: currentParameter.value.nama_parameter,
        nilai_target: currentParameter.value.nilai_target,
        satuan: currentParameter.value.satuan,
        performa_arah: currentParameter.value.performa_arah,
    };
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="loading" class="py-8 text-center">
            <p class="text-muted-foreground">Memuat data rekap parameter khusus...</p>
        </div>

        <div v-else-if="rekapData.length === 0" class="py-8 text-center">
            <p class="text-muted-foreground">Belum ada data rekap parameter khusus untuk atlet ini</p>
        </div>

        <div v-else class="space-y-6">
            <Card v-for="parameter in rekapData" :key="parameter.parameter_id" class="overflow-hidden">
                <CardHeader class="bg-gray-50 dark:bg-neutral-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="text-lg">{{ parameter.nama_parameter }}</CardTitle>
                            <p class="text-muted-foreground mt-1 text-sm">Target: {{ parameter.nilai_target }} {{ parameter.satuan || '' }}</p>
                        </div>
                        <button
                            class="border-input bg-background hover:bg-accent hover:text-accent-foreground inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors"
                            @click="openChartModal(parameter)"
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
                                    <th class="p-3 text-left font-medium">Pemeriksaan</th>
                                    <th class="p-3 text-right font-medium">Nilai</th>
                                    <th class="p-3 text-right font-medium">Persentase Performa</th>
                                    <th class="p-3 text-center font-medium">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(pemeriksaan, index) in parameter.pemeriksaan_list"
                                    :key="index"
                                    class="border-b hover:bg-gray-50 dark:hover:bg-neutral-800"
                                >
                                    <td class="p-3">{{ formatDate(pemeriksaan.tanggal) }}</td>
                                    <td class="p-3">{{ pemeriksaan.nama_pemeriksaan }}</td>
                                    <td class="p-3 text-right font-medium">{{ pemeriksaan.nilai || '-' }}</td>
                                    <td class="p-3 text-right font-medium" :class="getPerformaColor(pemeriksaan.persentase_performa)">
                                        {{ pemeriksaan.persentase_performa !== null ? `${pemeriksaan.persentase_performa.toFixed(2)}%` : '-' }}
                                    </td>
                                    <td class="p-3 text-center">
                                        <span :class="getTrendColor(pemeriksaan.trend)">{{ getTrendIcon(pemeriksaan.trend) }}</span>
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
            v-if="selectedParameter && currentParameter"
            :is-open="isModalOpen"
            :participant="selectedParameter"
            :statistik-data="chartStatistikData"
            :rencana-list="chartRencanaList"
            data-type="pemeriksaan"
            @close="closeModal"
        />
    </div>
</template>
