<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import TabsList from '@/components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const loading = ref(false);
const prestasiData = ref<any[]>([]);
const eventNames = ref<string[]>([]);
const selectedEvent = ref<string>('');
const totalBonus = ref(0);

// Format rupiah
const formatRupiah = (value: number | null | undefined): string => {
    if (!value || value === 0) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(value);
};

const fetchPrestasi = async (eventName: string = '', limit: number = 5) => {
    loading.value = true;
    try {
        const params: any = { limit };
        if (eventName) {
            params.event_name = eventName;
        }
        
        const response = await axios.get('/api/prestasi', { params });
        prestasiData.value = response.data.data || [];
        eventNames.value = response.data.event_names || [];
        totalBonus.value = response.data.total_bonus || 0;
    } catch (error) {
        console.error('Error fetching prestasi:', error);
    } finally {
        loading.value = false;
    }
};

const handleEventChange = (eventName: string) => {
    selectedEvent.value = eventName;
    fetchPrestasi(eventName, 5);
};

// Get current event data
const currentEventData = computed(() => {
    if (!selectedEvent.value) {
        return prestasiData.value[0] || null;
    }
    return prestasiData.value.find((event: any) => event.event_name === selectedEvent.value) || null;
});

// Get all prestasi for current event
const currentPrestasi = computed(() => {
    return currentEventData.value?.prestasi || [];
});

// Get columns for current event - check if any prestasi has NPCI or SOIna
const columns = computed(() => {
    const hasNPCI = currentPrestasi.value.some((p: any) => 
        p.kategori_peserta && p.kategori_peserta.includes('NPCI')
    );
    const hasSOIna = currentPrestasi.value.some((p: any) => 
        p.kategori_peserta && p.kategori_peserta.includes('SOIna')
    );
    
    const baseColumns = [
        { key: 'nama', label: 'Nama' },
        { key: 'cabor', label: 'Cabor' },
        { key: 'nomor_posisi', label: 'Nomor/Posisi' },
        { key: 'juara_medali', label: 'Juara/Medali' },
        { key: 'kategori_peserta', label: 'Kategori Peserta' },
    ];
    
    if (hasNPCI || hasSOIna) {
        baseColumns.push({ key: 'disabilitas', label: 'Disabilitas' });
        if (hasNPCI) {
            baseColumns.push({ key: 'klasifikasi', label: 'Klasifikasi' });
        }
        if (hasSOIna) {
            baseColumns.push({ key: 'iq', label: 'IQ' });
        }
    }
    
    baseColumns.push({ 
        key: 'bonus', 
        label: 'Bonus', 
        format: (row: any) => formatRupiah(row.bonus) 
    });
    
    return baseColumns;
});

onMounted(() => {
    fetchPrestasi('', 5); // Load with limit 5 for preview
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
        <div v-else-if="prestasiData.length === 0" class="text-center py-8 text-muted-foreground">
            Tidak ada data prestasi
        </div>
        <div v-else>
            <Tabs :model-value="selectedEvent || 'all'" @update:model-value="handleEventChange" class="w-full">
                <TabsList class="grid w-full overflow-x-auto border-border" :style="{ gridTemplateColumns: `repeat(${Math.min(eventNames.length + 1, 5)}, minmax(0, 1fr))` }">
                    <TabsTrigger value="all" @click="selectedEvent = ''; fetchPrestasi('', 5)" class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground">
                        Semua
                    </TabsTrigger>
                    <TabsTrigger 
                        v-for="eventName in eventNames.slice(0, 4)" 
                        :key="eventName" 
                        :value="eventName"
                        @click="handleEventChange(eventName)"
                        class="data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                    >
                        {{ eventName }}
                    </TabsTrigger>
                </TabsList>

                <TabsContent value="all" class="mt-4">
                    <div class="space-y-6">
                        <div v-for="event in prestasiData.slice(0, 3)" :key="event.event_name" class="space-y-3 rounded-lg border border-border bg-card p-4">
                            <div class="flex items-center justify-between border-b border-border pb-2">
                                <h3 class="text-sm font-semibold text-card-foreground">{{ event.event_name }}</h3>
                                <div class="text-xs text-muted-foreground">
                                    <span class="font-medium">{{ event.count }}</span> prestasi • 
                                    <span class="font-semibold text-green-600 dark:text-green-400">{{ formatRupiah(event.total_bonus) }}</span>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto rounded-md border border-border">
                                <Table>
                                    <TableHeader>
                                        <TableRow class="border-border hover:bg-muted/50">
                                            <TableHead v-for="col in columns" :key="col.key" class="text-xs font-semibold text-muted-foreground">{{ col.label }}</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow 
                                            v-for="prestasi in event.prestasi.slice(0, 3)" 
                                            :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`"
                                            class="border-border hover:bg-muted/50 transition-colors"
                                        >
                                            <TableCell v-for="col in columns" :key="col.key" class="text-xs text-foreground">
                                                <template v-if="col.format">
                                                    <span :class="col.key === 'bonus' ? 'font-semibold text-green-600 dark:text-green-400' : ''">
                                                        {{ col.format(prestasi) }}
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    <span class="text-foreground">{{ prestasi[col.key] || '-' }}</span>
                                                </template>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            
                            <div v-if="event.prestasi.length > 3" class="text-center pt-2">
                                <Button variant="link" size="sm" @click="router.visit('/prestasi')" class="text-primary hover:text-primary/80">
                                    Lihat {{ event.prestasi.length - 3 }} lagi...
                                </Button>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent 
                    v-for="eventName in eventNames.slice(0, 4)" 
                    :key="eventName" 
                    :value="eventName"
                    class="mt-4"
                >
                    <div v-if="!currentEventData" class="text-center py-8 text-muted-foreground">
                        Tidak ada data untuk event ini
                    </div>
                    <div v-else class="space-y-4 rounded-lg border border-border bg-card p-4">
                        <div class="flex items-center justify-between border-b border-border pb-2">
                            <h3 class="text-sm font-semibold text-card-foreground">{{ currentEventData.event_name }}</h3>
                            <div class="text-xs text-muted-foreground">
                                <span class="font-medium">{{ currentEventData.count }}</span> prestasi • 
                                <span class="font-semibold text-green-600 dark:text-green-400">{{ formatRupiah(currentEventData.total_bonus) }}</span>
                            </div>
                        </div>
                        
                        <div class="overflow-x-auto rounded-md border border-border">
                            <Table>
                                <TableHeader>
                                    <TableRow class="border-border hover:bg-muted/50">
                                        <TableHead v-for="col in columns" :key="col.key" class="text-xs font-semibold text-muted-foreground">{{ col.label }}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow 
                                        v-for="prestasi in currentPrestasi.slice(0, 5)" 
                                        :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`"
                                        class="border-border hover:bg-muted/50 transition-colors"
                                    >
                                        <TableCell v-for="col in columns" :key="col.key" class="text-xs text-foreground">
                                            <template v-if="col.format">
                                                <span :class="col.key === 'bonus' ? 'font-semibold text-green-600 dark:text-green-400' : ''">
                                                    {{ col.format(prestasi) }}
                                                </span>
                                            </template>
                                            <template v-else>
                                                <span class="text-foreground">{{ prestasi[col.key] || '-' }}</span>
                                            </template>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        
                        <div v-if="currentPrestasi.length > 5" class="text-center pt-2">
                            <Button variant="link" size="sm" @click="router.visit(`/prestasi?event=${eventName}`)" class="text-primary hover:text-primary/80">
                                Lihat {{ currentPrestasi.length - 5 }} lagi...
                            </Button>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </div>
</template>

