<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
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
const kategoriPesertaList = ref<{ id: number; nama: string }[]>([]);
const selectedKategoriPeserta = ref<string | number>('all');
const selectedJenisPrestasi = ref<string>('all'); // 'all', 'individu', 'ganda/mixed/beregu/double'
const totalBonus = ref(0);

// Modal untuk list anggota beregu
const showBereguModal = ref(false);
const anggotaBereguList = ref<{ id: number; nama: string; peserta_type: string }[]>([]);

// Total medali dari semua kategori peserta
const totalMedaliAll = computed(() => {
    const medali = {
        Emas: 0,
        Perak: 0,
        Perunggu: 0,
    };
    
    prestasiData.value.forEach((kategori: any) => {
        if (kategori.total_medali) {
            medali.Emas += kategori.total_medali.Emas || 0;
            medali.Perak += kategori.total_medali.Perak || 0;
            medali.Perunggu += kategori.total_medali.Perunggu || 0;
        }
    });
    
    return medali;
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

const fetchPrestasi = async (kategoriPesertaId: string | number = 'all', limit: number = 0) => {
    loading.value = true;
    try {
        const params: any = {};
        if (kategoriPesertaId && kategoriPesertaId !== 'all') {
            params.kategori_peserta_id = kategoriPesertaId;
        }
        if (selectedJenisPrestasi.value && selectedJenisPrestasi.value !== 'all') {
            params.jenis_prestasi = selectedJenisPrestasi.value;
        }
        if (limit > 0) {
            params.limit = limit;
        }
        
        const response = await axios.get('/api/prestasi', { params });
        prestasiData.value = response.data.data || [];
        kategoriPesertaList.value = response.data.kategori_peserta_list || [];
        totalBonus.value = response.data.total_bonus || 0;
    } catch (error) {
        console.error('Error fetching prestasi:', error);
    } finally {
        loading.value = false;
    }
};

const handleKategoriPesertaChange = (kategoriId: string | number) => {
    selectedKategoriPeserta.value = kategoriId;
    fetchPrestasi(kategoriId === 'all' ? 'all' : kategoriId, 0); // 0 means no limit
};

const handleJenisPrestasiChange = (jenisPrestasi: string) => {
    selectedJenisPrestasi.value = jenisPrestasi;
    fetchPrestasi(selectedKategoriPeserta.value, 0);
};

// Get columns for all prestasi - check if any prestasi has NPCI or SOIna
const columns = computed(() => {
    // Check all prestasi across all kategori
    const allPrestasi = prestasiData.value.flatMap((kategori: any) => kategori.prestasi || []);
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
        { key: 'juara', label: 'Juara' },
        { key: 'medali', label: 'Medali' },
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

// Get current kategori data
const currentKategoriData = computed(() => {
    if (selectedKategoriPeserta.value === 'all' || !selectedKategoriPeserta.value) {
        return null; // Show all kategori
    }
    return prestasiData.value.find((kategori: any) => kategori.kategori_peserta_id == selectedKategoriPeserta.value) || null;
});

// Get medali badge color
const getMedaliBadgeColor = (medali: string | null | undefined) => {
    if (!medali || medali === '-') return 'secondary';
    if (medali === 'Emas') return 'default';
    if (medali === 'Perak') return 'secondary';
    if (medali === 'Perunggu') return 'outline';
    return 'secondary';
};

// Open modal untuk list anggota beregu
const openBereguModal = (anggotaList: { id: number; nama: string; peserta_type: string }[]) => {
    anggotaBereguList.value = anggotaList;
    showBereguModal.value = true;
};

// Navigate to peserta detail
const goToPesertaDetail = (anggota: { id: number; nama: string; peserta_type: string }) => {
    let url = '';
    switch (anggota.peserta_type) {
        case 'atlet':
            url = `/atlet/${anggota.id}`;
            break;
        case 'pelatih':
            url = `/pelatih/${anggota.id}`;
            break;
        case 'tenaga_pendukung':
            url = `/tenaga-pendukung/${anggota.id}`;
            break;
        default:
            return;
    }
    router.visit(url);
};

// Close modal
const closeBereguModal = () => {
    showBereguModal.value = false;
    anggotaBereguList.value = [];
};


onMounted(() => {
    // Check if there's kategori parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    const kategoriParam = urlParams.get('kategori');
    if (kategoriParam) {
        selectedKategoriPeserta.value = kategoriParam;
        fetchPrestasi(kategoriParam, 0);
    } else {
        fetchPrestasi('all', 0); // Load all without limit
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
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <div class="text-sm text-muted-foreground">Total Bonus</div>
                        <div class="text-2xl font-bold text-green-600">{{ formatRupiah(totalBonus) }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-muted-foreground mb-2">Total Medali (Semua Kategori)</div>
                        <div class="flex items-center gap-2">
                            <Badge variant="default">🥇 {{ totalMedaliAll.Emas }}</Badge>
                            <Badge variant="secondary">🥈 {{ totalMedaliAll.Perak }}</Badge>
                            <Badge variant="outline">🥉 {{ totalMedaliAll.Perunggu }}</Badge>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Jenis Prestasi -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Filter Jenis Prestasi:</span>
                <div class="flex items-center gap-2">
                    <Button
                        :variant="selectedJenisPrestasi === 'all' ? 'default' : 'outline'"
                        size="sm"
                        @click="handleJenisPrestasiChange('all')"
                    >
                        Semua
                    </Button>
                    <Button
                        :variant="selectedJenisPrestasi === 'individu' ? 'default' : 'outline'"
                        size="sm"
                        @click="handleJenisPrestasiChange('individu')"
                    >
                        Individu
                    </Button>
                    <Button
                        :variant="selectedJenisPrestasi === 'ganda/mixed/beregu/double' ? 'default' : 'outline'"
                        size="sm"
                        @click="handleJenisPrestasiChange('ganda/mixed/beregu/double')"
                    >
                        Ganda/Mixed/Beregu/Double
                    </Button>
                </div>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Prestasi per Kategori Peserta</CardTitle>
                </CardHeader>
                <CardContent>
                    <Tabs :model-value="selectedKategoriPeserta" @update:model-value="handleKategoriPesertaChange" class="w-full">
                        <TabsList class="grid w-full overflow-x-auto bg-background" :style="{ gridTemplateColumns: `repeat(${Math.min(kategoriPesertaList.length + 1, 10)}, minmax(150px, 1fr))` }">
                            <TabsTrigger value="all" @click="handleKategoriPesertaChange('all')">
                                Semua
                            </TabsTrigger>
                            <TabsTrigger 
                                v-for="kategori in kategoriPesertaList" 
                                :key="kategori.id" 
                                :value="kategori.id"
                                @click="handleKategoriPesertaChange(kategori.id)"
                            >
                                {{ kategori.nama }}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="all" class="mt-4">
                            <div v-if="loading" class="text-center py-8">Memuat data...</div>
                            <div v-else-if="prestasiData.length === 0" class="text-center py-8 text-muted-foreground">
                                Tidak ada data prestasi
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="kategori in prestasiData" :key="kategori.kategori_peserta_id" class="space-y-2">
                                    <div class="flex items-center justify-between border-b pb-2">
                                        <div>
                                            <h3 class="text-lg font-semibold">{{ kategori.kategori_peserta_nama }}</h3>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                                                <span>{{ kategori.count }} prestasi</span>
                                                <span>•</span>
                                                <span>Total: {{ formatRupiah(kategori.total_bonus) }}</span>
                                                <span v-if="kategori.total_medali" class="flex items-center gap-2">
                                                    <span>•</span>
                                                    <span class="flex items-center gap-1">
                                                        <Badge variant="default">🥇 {{ kategori.total_medali.Emas || 0 }}</Badge>
                                                        <Badge variant="secondary">🥈 {{ kategori.total_medali.Perak || 0 }}</Badge>
                                                        <Badge variant="outline">🥉 {{ kategori.total_medali.Perunggu || 0 }}</Badge>
                                                    </span>
                                                </span>
                                            </div>
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
                                                <TableRow 
                                                    v-for="prestasi in kategori.prestasi" 
                                                    :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`"
                                                    :class="{ 'bg-muted/50': prestasi.is_beregu }"
                                                >
                                                    <TableCell v-for="col in columns" :key="col.key">
                                                        <template v-if="col.key === 'nama'">
                                                            <div class="flex items-center gap-2">
                                                                <span>{{ prestasi.nama || '-' }}</span>
                                                                <Badge 
                                                                    v-if="prestasi.is_beregu && prestasi.jumlah_anggota > 1" 
                                                                    variant="secondary" 
                                                                    class="cursor-pointer hover:bg-secondary/80"
                                                                    @click="openBereguModal(prestasi.anggota_beregu || [])"
                                                                >
                                                                    {{ prestasi.jumlah_anggota }} peserta
                                                                </Badge>
                                                            </div>
                                                        </template>
                                                        <template v-else-if="col.key === 'medali'">
                                                            <Badge v-if="prestasi.medali && prestasi.medali !== '-'" :variant="getMedaliBadgeColor(prestasi.medali)">
                                                                {{ prestasi.medali }}
                                                            </Badge>
                                                            <span v-else>-</span>
                                                        </template>
                                                        <template v-else-if="col.format">
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
                            v-for="kategori in kategoriPesertaList" 
                            :key="kategori.id" 
                            :value="kategori.id"
                            class="mt-4"
                        >
                            <div v-if="loading" class="text-center py-8">Memuat data...</div>
                            <div v-else-if="!currentKategoriData" class="text-center py-8 text-muted-foreground">
                                Tidak ada data untuk kategori ini
                            </div>
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <div>
                                        <h3 class="text-lg font-semibold">{{ currentKategoriData.kategori_peserta_nama }}</h3>
                                        <div class="flex items-center gap-4 mt-1 text-sm text-muted-foreground">
                                            <span>{{ currentKategoriData.count }} prestasi</span>
                                            <span>•</span>
                                            <span>Total: {{ formatRupiah(currentKategoriData.total_bonus) }}</span>
                                            <span v-if="currentKategoriData.total_medali" class="flex items-center gap-2">
                                                <span>•</span>
                                                <span class="flex items-center gap-1">
                                                    <Badge variant="default">🥇 {{ currentKategoriData.total_medali.Emas || 0 }}</Badge>
                                                    <Badge variant="secondary">🥈 {{ currentKategoriData.total_medali.Perak || 0 }}</Badge>
                                                    <Badge variant="outline">🥉 {{ currentKategoriData.total_medali.Perunggu || 0 }}</Badge>
                                                </span>
                                            </span>
                                        </div>
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
                                            <TableRow 
                                                v-for="prestasi in currentKategoriData.prestasi" 
                                                :key="`${prestasi.peserta_type}-${prestasi.peserta_id}-${prestasi.id}`"
                                                :class="{ 'bg-muted/50': prestasi.is_beregu }"
                                            >
                                                <TableCell v-for="col in columns" :key="col.key">
                                                    <template v-if="col.key === 'nama'">
                                                        <div class="flex items-center gap-2">
                                                            <span>{{ prestasi.nama || '-' }}</span>
                                                            <Badge 
                                                                v-if="prestasi.is_beregu && prestasi.jumlah_anggota > 1" 
                                                                variant="secondary" 
                                                                class="cursor-pointer hover:bg-secondary/80"
                                                                @click="openBereguModal(prestasi.anggota_beregu || [])"
                                                            >
                                                                {{ prestasi.jumlah_anggota }} peserta
                                                            </Badge>
                                                        </div>
                                                    </template>
                                                    <template v-else-if="col.key === 'medali'">
                                                        <Badge v-if="prestasi.medali && prestasi.medali !== '-'" :variant="getMedaliBadgeColor(prestasi.medali)">
                                                            {{ prestasi.medali }}
                                                        </Badge>
                                                        <span v-else>-</span>
                                                    </template>
                                                    <template v-else-if="col.format">
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

        <!-- Modal List Anggota Beregu -->
        <Dialog :open="showBereguModal" @update:open="showBereguModal = $event">
            <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-xl font-semibold">
                        Daftar Anggota Beregu
                    </DialogTitle>
                </DialogHeader>

                <div class="mt-4">
                    <div v-if="anggotaBereguList.length === 0" class="text-center py-8 text-muted-foreground">
                        Tidak ada data anggota
                    </div>
                    <div v-else class="space-y-2">
                        <div
                            v-for="(anggota, index) in anggotaBereguList"
                            :key="anggota.id"
                            class="flex items-center rounded-lg border p-3 hover:bg-accent cursor-pointer transition-colors"
                            @click="goToPesertaDetail(anggota)"
                        >
                            <span class="mr-3 text-sm font-medium text-muted-foreground">{{ index + 1 }}.</span>
                            <span class="flex-1 font-medium text-sm">{{ anggota.nama }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <Button variant="outline" @click="closeBereguModal">Tutup</Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
