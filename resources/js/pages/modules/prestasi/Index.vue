<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import Tabs from '@/components/ui/tabs/Tabs.vue';
import TabsContent from '@/components/ui/tabs/TabsContent.vue';
import TabsList from '@/components/ui/tabs/TabsList.vue';
import TabsTrigger from '@/components/ui/tabs/TabsTrigger.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'List Prestasi',
        href: '/prestasi',
    },
];

const loading = ref(false);
const prestasiData = ref<any[]>([]);
const eventNames = ref<string[]>([]);
const selectedEvent = ref<string>('all');
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

const fetchPrestasi = async (eventName: string = '', limit: number = 0) => {
    loading.value = true;
    try {
        const params: any = {};
        if (eventName && eventName !== 'all') {
            params.event_name = eventName;
        }
        if (limit > 0) {
            params.limit = limit;
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
    fetchPrestasi(eventName === 'all' ? '' : eventName, 0); // 0 means no limit
};

// Get columns for all prestasi - check if any prestasi has NPCI or SOIna
const columns = computed(() => {
    // Check all prestasi across all events
    const allPrestasi = prestasiData.value.flatMap((event: any) => event.prestasi || []);
    const hasNPCI = allPrestasi.some((p: any) => 
        p.kategori_peserta && p.kategori_peserta.includes('NPCI')
    );
    const hasSOIna = allPrestasi.some((p: any) => 
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

// Get current event data
const currentEventData = computed(() => {
    if (selectedEvent.value === 'all' || !selectedEvent.value) {
        return null; // Show all events
    }
    return prestasiData.value.find((event: any) => event.event_name === selectedEvent.value) || null;
});

// Get all prestasi for display
const displayPrestasi = computed(() => {
    if (selectedEvent.value === 'all' || !selectedEvent.value) {
        // Show all prestasi from all events
        return prestasiData.value.flatMap((event: any) => 
            (event.prestasi || []).map((p: any) => ({ ...p, event_name: event.event_name }))
        );
    }
    return currentEventData.value?.prestasi || [];
});

onMounted(() => {
    // Check if there's event parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const eventParam = urlParams.get('event');
    if (eventParam) {
        selectedEvent.value = eventParam;
        fetchPrestasi(eventParam, 0);
    } else {
        fetchPrestasi('', 0); // Load all without limit
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="List Prestasi" />
        
        <div class="mt-6 ml-4 mr-4 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">List Prestasi</h1>
                    <p class="text-muted-foreground mt-1">Daftar semua prestasi peserta (Atlet, Pelatih, Tenaga Pendukung)</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-muted-foreground">Total Bonus</div>
                    <div class="text-2xl font-bold text-green-600">{{ formatRupiah(totalBonus) }}</div>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Prestasi per Event</CardTitle>
                </CardHeader>
                <CardContent>
                    <Tabs :model-value="selectedEvent" @update:model-value="handleEventChange" class="w-full">
                        <TabsList class="grid w-full overflow-x-auto" :style="{ gridTemplateColumns: `repeat(${Math.min(eventNames.length + 1, 10)}, minmax(150px, 1fr))` }">
                            <TabsTrigger value="all" @click="handleEventChange('all')">
                                Semua
                            </TabsTrigger>
                            <TabsTrigger 
                                v-for="eventName in eventNames" 
                                :key="eventName" 
                                :value="eventName"
                                @click="handleEventChange(eventName)"
                            >
                                {{ eventName }}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="all" class="mt-4">
                            <div v-if="loading" class="text-center py-8">Memuat data...</div>
                            <div v-else-if="prestasiData.length === 0" class="text-center py-8 text-muted-foreground">
                                Tidak ada data prestasi
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="event in prestasiData" :key="event.event_name" class="space-y-2">
                                    <div class="flex items-center justify-between border-b pb-2">
                                        <h3 class="text-lg font-semibold">{{ event.event_name }}</h3>
                                        <div class="text-sm text-muted-foreground">
                                            {{ event.count }} prestasi • Total: {{ formatRupiah(event.total_bonus) }}
                                        </div>
                                    </div>
                                    
                                    <div class="overflow-x-auto">
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead v-for="col in columns" :key="col.key">{{ col.label }}</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                <TableRow v-for="prestasi in event.prestasi" :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`">
                                                    <TableCell v-for="col in columns" :key="col.key">
                                                        <template v-if="col.format">
                                                            {{ col.format(prestasi) }}
                                                        </template>
                                                        <template v-else>
                                                            {{ prestasi[col.key] || '-' }}
                                                        </template>
                                                    </TableCell>
                                                </TableRow>
                                            </TableBody>
                                        </Table>
                                    </div>
                                    
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent 
                            v-for="eventName in eventNames" 
                            :key="eventName" 
                            :value="eventName"
                            class="mt-4"
                        >
                            <div v-if="loading" class="text-center py-8">Memuat data...</div>
                            <div v-else-if="!currentEventData" class="text-center py-8 text-muted-foreground">
                                Tidak ada data untuk event ini
                            </div>
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <h3 class="text-lg font-semibold">{{ currentEventData.event_name }}</h3>
                                    <div class="text-sm text-muted-foreground">
                                        {{ currentEventData.count }} prestasi • Total: {{ formatRupiah(currentEventData.total_bonus) }}
                                    </div>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead v-for="col in columns" :key="col.key">{{ col.label }}</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow v-for="prestasi in currentEventData.prestasi" :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`">
                                                <TableCell v-for="col in columns" :key="col.key">
                                                    <template v-if="col.format">
                                                        {{ col.format(prestasi) }}
                                                    </template>
                                                    <template v-else>
                                                        {{ prestasi[col.key] || '-' }}
                                                    </template>
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

