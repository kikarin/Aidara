<script setup lang="ts">
import GenericParticipantChartModal from '@/components/GenericParticipantChartModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowDown, ArrowUp, BarChart3, Minus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    infoHeader?: any;
}>();

const info = computed(() => props.infoHeader || {});

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Target Latihan', href: `/program-latihan/${info.value.program_latihan_id}/target-latihan/${info.value.jenis_target}` },
    { title: 'Statistik', href: '#' },
];

// State untuk data
const statistikData = ref<any[]>([]);
const pesertaList = ref<any[]>([]);
const rencanaLatihanList = ref<any[]>([]);
const targetInfo = ref<any>(null);
const loading = ref(false);

const selectedPesertaType = ref<string>('atlet');

// Modal state
const isModalOpen = ref(false);
const selectedParticipant = ref<any>(null);

// Fetch data saat component mount
onMounted(async () => {
    await loadTargetInfo();
    await loadStatistikData();
});

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
    } catch (error) {
        console.error('Error loading statistik:', error);
        toast({ title: 'Gagal mengambil data statistik', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Format tanggal untuk display
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
    });
};

const sortedRencanaLatihan = computed(() => {
    return rencanaLatihanList.value.slice().sort((a, b) => new Date(a.tanggal).getTime() - new Date(b.tanggal).getTime());
});

// Get trend icon dan warna
const getTrendIcon = (trend: string) => {
    switch (trend) {
        case 'naik':
            return { icon: ArrowUp, color: 'text-green-600' };
        case 'turun':
            return { icon: ArrowDown, color: 'text-red-600' };
        case 'stabil':
        default:
            return { icon: Minus, color: 'text-yellow-600' };
    }
};

// Get performa color
const getPerformaColor = (persentase: number | null) => {
    if (persentase === null) return 'text-gray-400';
    if (persentase > 75) return 'text-red-600';
    if (persentase >= 40) return 'text-yellow-600';
    return 'text-green-600';
};

// Modal functions
const openParticipantChart = (peserta: any) => {
    selectedParticipant.value = peserta;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    selectedParticipant.value = null;
};

// Data sudah dalam format yang benar untuk GenericParticipantChartModal
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Statistik Target Latihan</h1>
                </div>
            </div>

            <!-- Info Header -->
            <Card class="w-sm bg-gray-100 dark:bg-neutral-900">
                <CardContent>
                    <div class="grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground text-sm">Nama Target</p>
                            <p class="font-medium">{{ targetInfo?.deskripsi }}</p>
                        </div>
                        <div>
                            <p class="text-muted-foreground text-sm">Nilai Target</p>
                            <p class="font-medium">{{ targetInfo?.nilai_target }} {{ targetInfo?.satuan }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
            <Button
                v-if="statistikData.length > 0"
                @click="router.visit(`/program-latihan/${info.program_latihan_id}/target-latihan/${info.jenis_target}/${info.target_id}/chart`)"
                variant="outline"
                class="flex items-center gap-2"
            >
                <BarChart3 class="h-4 w-4" />
                Lihat Grafik
            </Button>

            <!-- Statistik Table -->
            <div v-if="statistikData.length > 0 || targetInfo" class="rounded-lg border bg-white dark:bg-neutral-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] border-collapse">
                        <thead class="bg-gray-100 dark:bg-neutral-800">
                            <tr class="border-b">
                                <th class="sticky left-0 z-10 min-w-[200px] bg-gray-100 p-4 text-left font-medium dark:bg-neutral-800">
                                    Nama Peserta
                                </th>
                                <th v-for="rencana in sortedRencanaLatihan" :key="rencana.id" class="min-w-[120px] p-4 text-center font-medium">
                                    {{ formatDate(rencana.tanggal) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="peserta in pesertaList" :key="peserta.id" class="border-b hover:bg-gray-100 dark:hover:bg-neutral-800">
                                <td class="sticky left-0 z-10 bg-white p-4 dark:bg-neutral-900">
                                    <div>
                                        <p
                                            class="cursor-pointer font-medium hover:text-blue-800 hover:underline"
                                            @click="openParticipantChart(peserta)"
                                        >
                                            {{ peserta.nama }}
                                        </p>
                                        <p class="text-sm">{{ peserta.posisi }}</p>
                                    </div>
                                </td>
                                <td v-for="rencana in sortedRencanaLatihan" :key="`${peserta.id}-${rencana.id}`" class="p-4 text-center">
                                    <div
                                        v-if="statistikData.find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id)"
                                        class="flex flex-col items-center justify-center gap-1"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="text-sm font-medium">
                                                {{
                                                    statistikData.find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id)
                                                        ?.nilai || '-'
                                                }}
                                            </span>
                                            <component
                                                :is="
                                                    getTrendIcon(
                                                        statistikData.find(
                                                            (s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id,
                                                        )?.trend || 'stabil',
                                                    ).icon
                                                "
                                                :class="
                                                    getTrendIcon(
                                                        statistikData.find(
                                                            (s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id,
                                                        )?.trend || 'stabil',
                                                    ).color
                                                "
                                                class="h-4 w-4"
                                            />
                                        </div>
                                        <span
                                            v-if="
                                                statistikData.find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id)
                                                    ?.persentase_performa !== null
                                            "
                                            class="text-xs font-semibold"
                                            :class="
                                                getPerformaColor(
                                                    statistikData.find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id)
                                                        ?.persentase_performa,
                                                )
                                            "
                                        >
                                            {{
                                                statistikData
                                                    .find((s: any) => s.peserta_id === peserta.id && s.rencana_latihan_id === rencana.id)
                                                    ?.persentase_performa?.toFixed(1) || '-'
                                            }}%
                                        </span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <Card v-else-if="!loading">
                <CardContent class="py-12 text-center">
                    <p class="text-muted-foreground">Belum ada data statistik untuk target latihan ini</p>
                </CardContent>
            </Card>

            <!-- Participant Chart Modal -->
            <GenericParticipantChartModal
                :is-open="isModalOpen"
                :participant="selectedParticipant"
                :statistik-data="statistikData"
                :rencana-list="rencanaLatihanList"
                data-type="target-latihan"
                :target-info="targetInfo"
                @close="closeModal"
            />
        </div>
    </AppLayout>
</template>
