<script setup lang="ts">
import GenericParticipantChartModal from '@/components/GenericParticipantChartModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowDown, ArrowUp, BarChart3, Minus, RefreshCw } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const { toast } = useToast();
const page = usePage();

interface Parameter {
    id: number;
    nama: string;
    satuan: string;
    kategori?: string;
    nilai_target?: string;
    performa_arah?: string;
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
];

// State untuk data
const statistikData = ref<StatistikData[]>([]);
const pesertaList = ref<Peserta[]>([]);
const rencanaPemeriksaanList = ref<RencanaPemeriksaan[]>([]);
const parameterInfo = ref<Parameter | null>(null);
const loading = ref(false);

// Modal state
const isModalOpen = ref(false);
const selectedParticipant = ref<Peserta | null>(null);

// Load data statistik
const loadStatistikData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/pemeriksaan-parameter/AllParameter/${parameterId.value}/statistik`);

        statistikData.value = response.data.data || [];
        pesertaList.value = response.data.peserta || [];
        rencanaPemeriksaanList.value = response.data.rencana_pemeriksaan || [];
        parameterInfo.value = response.data.parameter_info || null;
    } catch (error) {
        console.error('Error loading statistik data:', error);
        toast({ title: 'Gagal memuat data statistik', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Generate data untuk tabel
const sortedRencanaPemeriksaan = computed(() => {
    return [...rencanaPemeriksaanList.value].sort((a, b) => new Date(a.tanggal_pemeriksaan).getTime() - new Date(b.tanggal_pemeriksaan).getTime());
});

// Get trend icon
const getTrendIcon = (trend: string) => {
    switch (trend) {
        case 'naik':
        case 'kenaikan':
            return { component: ArrowUp, colorClass: 'text-green-600' };
        case 'turun':
        case 'penurunan':
            return { component: ArrowDown, colorClass: 'text-red-600' };
        case 'stabil':
        default:
            return { component: Minus, colorClass: 'text-yellow-600' };
    }
};

// Get nilai untuk peserta dan tanggal tertentu
const getNilaiForPesertaAndTanggal = (pesertaId: number, tanggal: string) => {
    const data = statistikData.value.find((item) => item.peserta_id === pesertaId && item.tanggal_pemeriksaan === tanggal);
    return data || null;
};

// Format tanggal untuk display
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: '2-digit',
    });
};

// Get jenis peserta label
const getJenisPesertaLabel = (jenisPeserta: string) => {
    switch (jenisPeserta) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga-pendukung':
            return 'Tenaga Pendukung';
        default:
            return jenisPeserta;
    }
};

// Get performa color
const getPerformaColor = (persentase: number | null) => {
    if (persentase === null) return 'text-gray-400';
    if (persentase > 75) return 'text-red-600';
    if (persentase >= 40) return 'text-yellow-600';
    return 'text-green-600';
};

// Check if parameter is khusus
const isParameterKhusus = computed(() => parameterInfo.value?.kategori === 'khusus');

// Handler untuk membuka modal chart peserta
const openParticipantChart = (peserta: Peserta) => {
    selectedParticipant.value = peserta;
    isModalOpen.value = true;
};

// Handler untuk menutup modal
const closeModal = () => {
    isModalOpen.value = false;
    selectedParticipant.value = null;
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
                    <h1 class="text-2xl font-bold">Statistik Parameter</h1>
                </div>
                <div class="flex items-center gap-2"></div>
            </div>

            <!-- Info Header -->
            <Card class="w-sm bg-gray-100 dark:bg-neutral-900">
                <CardContent>
                    <div class="grid-cols-2 gap-4 md:grid-cols-4">
                        <div>
                            <p class="text-muted-foreground text-sm">Parameter:</p>
                            <p class="font-medium">{{ parameter.nama }} ({{ parameter.satuan }})</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Button
                v-if="statistikData.length > 0"
                @click="router.visit(`/pemeriksaan-parameter/AllParameter/${parameterId}/chart`)"
                variant="outline"
                class="flex items-center gap-2"
            >
                <BarChart3 class="h-4 w-4" />
                Lihat Grafik
            </Button>

            <!-- Statistik Table -->
            <div class="rounded-lg border bg-white dark:bg-neutral-900">
                <div v-if="loading" class="flex items-center justify-center p-8">
                    <div class="flex items-center gap-2">
                        <RefreshCw class="h-4 w-4 animate-spin" />
                        <span>Memuat data...</span>
                    </div>
                </div>

                <div v-else-if="statistikData.length === 0" class="rounded-lg border bg-white p-8 text-center dark:bg-neutral-900">
                    <p class="text-muted-foreground">Belum ada data statistik untuk parameter ini.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 dark:bg-neutral-800">
                            <tr>
                                <th class="sticky left-0 z-10 min-w-[200px] bg-gray-100 p-4 text-left font-medium dark:bg-neutral-800">
                                    Nama Peserta
                                </th>
                                <th v-for="rencana in sortedRencanaPemeriksaan" :key="rencana.id" class="min-w-[120px] p-4 text-center font-medium">
                                    <div>
                                        <div class="text-muted-foreground text-xs">{{ rencana.nama_pemeriksaan }}</div>
                                        <div>{{ formatDate(rencana.tanggal_pemeriksaan) }}</div>
                                    </div>
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
                                            title="Klik untuk melihat grafik individu"
                                        >
                                            {{ peserta.nama }}
                                        </p>
                                        <p class="text-sm">
                                            {{ getJenisPesertaLabel(peserta.jenis_peserta) }}
                                        </p>
                                    </div>
                                </td>
                                <td v-for="rencana in sortedRencanaPemeriksaan" :key="rencana.id" class="p-4 text-center">
                                    <div
                                        v-if="getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)"
                                        class="flex flex-col items-center justify-center gap-1"
                                    >
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="text-sm font-medium">
                                                {{ getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.nilai }}
                                            </span>
                                            <component
                                                :is="
                                                    getTrendIcon(
                                                        getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.trend || 'stabil',
                                                    ).component
                                                "
                                                :class="
                                                    getTrendIcon(
                                                        getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.trend || 'stabil',
                                                    ).colorClass
                                                "
                                                class="h-4 w-4"
                                                :title="getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.trend || 'stabil'"
                                            />
                                        </div>
                                        <span
                                            v-if="
                                                isParameterKhusus &&
                                                getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.persentase_performa !== null
                                            "
                                            class="text-xs font-semibold"
                                            :class="
                                                getPerformaColor(
                                                    getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.persentase_performa,
                                                )
                                            "
                                        >
                                            {{
                                                getNilaiForPesertaAndTanggal(peserta.id, rencana.tanggal_pemeriksaan)?.persentase_performa?.toFixed(
                                                    1,
                                                ) || '-'
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
        </div>

        <!-- Modal Chart Peserta -->
        <GenericParticipantChartModal
            :is-open="isModalOpen"
            :participant="selectedParticipant"
            :statistik-data="statistikData"
            :rencana-list="rencanaPemeriksaanList"
            @close="closeModal"
        />
    </AppLayout>
</template>
