<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const loading = ref(false);
const totalBonus = ref(0);
const totalMedali = ref({
    Emas: 0,
    Perak: 0,
    Perunggu: 0,
});

// Format rupiah
const formatRupiah = (value: number | null | undefined): string => {
    if (!value || value === 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const fetchSummary = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/prestasi/summary');
        if (response.data.success) {
            totalBonus.value = response.data.data.total_bonus || 0;
            totalMedali.value = response.data.data.total_medali || {
                Emas: 0,
                Perak: 0,
                Perunggu: 0,
            };
        }
    } catch (error) {
        console.error('Error fetching prestasi summary:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchSummary();
});
</script>

<template>
    <div class="space-y-4">
        <div v-if="loading" class="text-center py-8 text-muted-foreground">
            <div class="inline-flex items-center gap-2">
                <div class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                <span>Memuat data...</span>
            </div>
        </div>
        <div v-else class="space-y-6">
            <!-- Total Bonus -->
            <div class="rounded-lg border border-border bg-card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-muted-foreground mb-1">Total Bonus</h3>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ formatRupiah(totalBonus) }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Total Medali -->
            <div class="rounded-lg border border-border bg-card p-6">
                <h3 class="text-sm font-medium text-muted-foreground mb-4">Total Medali</h3>
                <div class="flex items-center gap-4">
                    <Badge variant="default" class="text-base px-4 py-2">
                        🥇 {{ totalMedali.Emas }}
                    </Badge>
                    <Badge variant="secondary" class="text-base px-4 py-2">
                        🥈 {{ totalMedali.Perak }}
                    </Badge>
                    <Badge variant="outline" class="text-base px-4 py-2">
                        🥉 {{ totalMedali.Perunggu }}
                    </Badge>
                </div>
            </div>
        </div>
    </div>
</template>
