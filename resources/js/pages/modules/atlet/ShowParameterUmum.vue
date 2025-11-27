<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps<{
    atletId: number;
}>();

const { toast } = useToast();

// State
const parameterUmumData = ref<any[]>([]);
const loading = ref(false);

// Load data
onMounted(async () => {
    await loadParameterUmumData();
});

const loadParameterUmumData = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/atlet/${props.atletId}/parameter-umum`);
        if (response.data.success) {
            parameterUmumData.value = response.data.data || [];
        } else {
            toast({ title: response.data.message || 'Gagal mengambil data parameter umum', variant: 'destructive' });
        }
    } catch (error: any) {
        console.error('Error loading parameter umum:', error);
        toast({ title: error.response?.data?.message || 'Gagal mengambil data parameter umum', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

// Get performa color
const getPerformaColor = (persentase: number | null) => {
    if (persentase === null) return 'text-gray-400';
    if (persentase > 75) return 'text-red-600';
    if (persentase >= 40) return 'text-yellow-600';
    return 'text-green-600';
};

// Helper function to parse number with comma as decimal separator
const parseNumber = (value: string | null): number | null => {
    if (!value) return null;
    
    // Convert to string and trim whitespace
    const strValue = value.toString().trim();
    if (!strValue) return null;
    
    // Replace comma with dot for decimal separator (Indonesian format)
    // Handle both formats: "15,6" and "15.6"
    // Also handle cases like "15,6" or "15.6" or "15"
    const normalizedValue = strValue.replace(',', '.');
    const parsed = parseFloat(normalizedValue);
    
    return isNaN(parsed) ? null : parsed;
};

// Calculate persentase performa
const calculatePerforma = (nilai: string | null, nilaiTarget: string | null, performaArah: string) => {
    if (!nilai || !nilaiTarget) return null;

    const nilaiAktual = parseNumber(nilai);
    const target = parseNumber(nilaiTarget);

    if (nilaiAktual === null || target === null || target <= 0) return null;

    if (performaArah === 'min') {
        return (target / nilaiAktual) * 100;
    } else {
        return (nilaiAktual / target) * 100;
    }
};
</script>

<template>
    <div class="space-y-4">
        <div v-if="loading" class="py-8 text-center">
            <p class="text-muted-foreground">Memuat data parameter umum...</p>
        </div>

        <div v-else-if="parameterUmumData.length === 0" class="py-8 text-center">
            <p class="text-muted-foreground">Belum ada data parameter umum untuk atlet ini</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="param in parameterUmumData" :key="param.id" class="overflow-hidden bg-gray-100 dark:bg-neutral-900">
                <CardHeader class="">
                    <CardTitle class="text-lg">{{ param.nama }}</CardTitle>
                    <p class="text-muted-foreground mt-1 text-sm">
                        Satuan: {{ param.satuan || '-' }}
                        <span v-if="param.nilai_target"> | Target: {{ param.nilai_target }}</span>
                    </p>
                </CardHeader>
                <CardContent class="p-6">
                    <div class="">
                        <div>
                            <span class="text-muted-foreground text-sm">Nilai:</span>
                            <p class="text-lg font-semibold">{{ param.nilai || '-' }}</p>
                        </div>
                        <div v-if="param.nilai_target && param.nilai">
                            <span class="text-muted-foreground text-sm">Persentase Performa:</span>
                            <p
                                class="text-lg font-semibold"
                                :class="getPerformaColor(calculatePerforma(param.nilai, param.nilai_target, param.performa_arah || 'max'))"
                            >
                                {{
                                    calculatePerforma(param.nilai, param.nilai_target, param.performa_arah || 'max') !== null
                                        ? `${calculatePerforma(param.nilai, param.nilai_target, param.performa_arah || 'max')?.toFixed(2)}%`
                                        : '-'
                                }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
