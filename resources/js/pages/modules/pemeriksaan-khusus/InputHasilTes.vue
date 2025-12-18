<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useToast } from '@/components/ui/toast/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Loader2, Save } from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref } from 'vue';

const props = defineProps<{
    item: {
        id: number;
        nama_pemeriksaan: string;
        cabor_id: number;
        cabor_kategori_id: number;
        tanggal_pemeriksaan: string;
        status: string;
        cabor?: { nama: string };
        caborKategori?: { nama: string };
    };
}>();

const { toast } = useToast();

const breadcrumbs = [
    { title: 'Pemeriksaan Khusus', href: '/pemeriksaan-khusus' },
    { title: 'Input Hasil Tes', href: `/pemeriksaan-khusus/${props.item.id}/input-hasil-tes` },
];

// State
const loading = ref(false);
const loadingData = ref(false);
const aspekList = ref<any[]>([]);
const pesertaList = ref<any[]>([]);
const tableState = ref<any[]>([]);

// Helper: Konversi format waktu ke detik
const parseTimeToSeconds = (timeString: string): number | null => {
    const parts = timeString.split(':');
    
    if (parts.length === 2) {
        // Format mm:ss
        const minutes = parseInt(parts[0], 10);
        const seconds = parseInt(parts[1], 10);
        if (isNaN(minutes) || isNaN(seconds)) return null;
        return (minutes * 60) + seconds;
    } else if (parts.length === 3) {
        // Format hh:mm:ss
        const hours = parseInt(parts[0], 10);
        const minutes = parseInt(parts[1], 10);
        const seconds = parseInt(parts[2], 10);
        if (isNaN(hours) || isNaN(minutes) || isNaN(seconds)) return null;
        return (hours * 3600) + (minutes * 60) + seconds;
    }
    
    return null;
};

// Helper: Parse number dengan support comma, dot, dan format waktu
const parseNumber = (value: string | null): number | null => {
    if (!value) return null;
    
    const strValue = value.trim();
    if (!strValue) return null;
    
    // Deteksi format waktu (ada titik dua)
    if (strValue.includes(':')) {
        return parseTimeToSeconds(strValue);
    }
    
    const normalizedValue = strValue.replace(',', '.');
    const parsed = parseFloat(normalizedValue);
    
    return isNaN(parsed) ? null : parsed;
};

// Helper: Calculate persentase performa (client-side preview)
const calculatePerforma = (nilaiAktual: string | null, target: string | null, performaArah: 'max' | 'min'): { persentase: number | null; predikat: string | null } => {
    if (!nilaiAktual || !target) {
        return { persentase: null, predikat: null };
    }

    const nilai = parseNumber(nilaiAktual);
    const targetValue = parseNumber(target);

    if (nilai === null || targetValue === null || targetValue <= 0) {
        return { persentase: null, predikat: null };
    }

    let persentaseRiil: number;
    if (performaArah === 'min') {
        persentaseRiil = (targetValue / nilai) * 100;
    } else {
        persentaseRiil = (nilai / targetValue) * 100;
    }

    const persentase = Math.min(100, Math.max(0, persentaseRiil));
    const predikat = getPredikat(persentase);

    return { persentase: Math.round(persentase * 100) / 100, predikat };
};

// Helper: Get predikat berdasarkan persentase
const getPredikat = (persentase: number | null): string | null => {
    if (persentase === null) return null;
    if (persentase >= 0 && persentase < 20) return 'sangat_kurang';
    if (persentase >= 20 && persentase < 40) return 'kurang';
    if (persentase >= 40 && persentase < 60) return 'sedang';
    if (persentase >= 60 && persentase < 80) return 'mendekati_target';
    if (persentase >= 80 && persentase < 100) return 'mendekati_target';
    return 'target'; // >= 100
};

// Helper: Get predikat label
const getPredikatLabel = (predikat: string | null): string => {
    const labels: Record<string, string> = {
        sangat_kurang: 'Sangat Kurang',
        kurang: 'Kurang',
        sedang: 'Sedang',
        mendekati_target: 'Mendekati Target',
        target: 'Target',
    };
    return predikat ? labels[predikat] || '-' : '-';
};

// Helper: Get predikat color
const getPredikatColor = (predikat: string | null): string => {
    const colors: Record<string, string> = {
        sangat_kurang: 'bg-red-500 text-white',
        kurang: 'bg-orange-500 text-white',
        sedang: 'bg-yellow-500 text-white',
        mendekati_target: 'bg-green-400 text-white',
        target: 'bg-green-600 text-white',
    };
    return predikat ? colors[predikat] || 'bg-gray-500 text-white' : 'bg-gray-300 text-gray-600';
};

// Helper: Get jenis kelamin peserta
const getJenisKelamin = (peserta: any): string | null => {
    return peserta?.jenis_kelamin || null;
};

// Helper: Get target berdasarkan jenis kelamin
const getTarget = (itemTes: any, jenisKelamin: string | null): string | null => {
    if (jenisKelamin === 'L' || jenisKelamin === 'Laki-laki') {
        return itemTes.target_laki_laki;
    }
    return itemTes.target_perempuan;
};

// Helper: Format persentase dengan aman
const formatPersentase = (persentase: any): string => {
    // Handle null, undefined, empty string
    if (persentase === null || persentase === undefined || persentase === '') {
        return '-';
    }
    
    // Handle number
    if (typeof persentase === 'number') {
        if (isNaN(persentase)) {
            return '-';
        }
        return persentase.toFixed(1);
    }
    
    // Handle string - try to parse
    if (typeof persentase === 'string') {
        const num = parseFloat(persentase);
        if (isNaN(num)) {
            return '-';
        }
        return num.toFixed(1);
    }
    
    // Handle other types (object, array, etc.) - convert to string first
    try {
        const num = parseFloat(String(persentase));
        if (isNaN(num)) {
            return '-';
        }
        return num.toFixed(1);
    } catch (e) {
        return '-';
    }
};

// Helper: Get item tes data untuk row tertentu
const getItemTesData = (row: any, itemTesId: number) => {
    return row.item_tes.find((it: any) => it.item_tes_id === itemTesId) || null;
};

// Load data
const loadData = async () => {
    loadingData.value = true;
    try {
        // Load aspek & item tes
        const aspekRes = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/aspek-item-tes`);
        const rawAspek = aspekRes.data.aspek || [];
        
        // Filter unique aspek berdasarkan id dan filter unique item tes
        const uniqueAspekMap = new Map<number, any>();
        rawAspek.forEach((aspek: any) => {
            if (!uniqueAspekMap.has(aspek.id)) {
                // Filter unique item tes dalam aspek ini
                const uniqueItemTesMap = new Map<number, any>();
                (aspek.item_tes || []).forEach((itemTes: any) => {
                    if (!uniqueItemTesMap.has(itemTes.id)) {
                        uniqueItemTesMap.set(itemTes.id, itemTes);
                    }
                });
                
                uniqueAspekMap.set(aspek.id, {
                    ...aspek,
                    item_tes: Array.from(uniqueItemTesMap.values()),
                });
            }
        });
        
        aspekList.value = Array.from(uniqueAspekMap.values());

        // Load peserta dari pemeriksaan khusus - HANYA ATLET (pelatih dan tenaga pendukung tidak dinilai)
        const pesertaRes = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/peserta?jenis_peserta=atlet`);
        // Response structure: { success: true, data: [...], tipe: 'atlet' }
        // data[].id = pemeriksaan_khusus_peserta.id (untuk mapping dengan hasil tes)
        // data[].peserta_id = id peserta asli (atlet id)
        if (pesertaRes.data.success && pesertaRes.data.data) {
            pesertaList.value = pesertaRes.data.data.map((a: any) => ({ 
                id: a.id, // pemeriksaan_khusus_peserta.id (untuk mapping dengan hasil tes)
                peserta_id: a.peserta_id, // id peserta asli (atlet id)
                nama: a.nama,
                jenis_kelamin: a.jenis_kelamin,
                usia: a.usia,
                jenis_peserta: 'atlet' 
            }));
        } else {
            // Fallback untuk backward compatibility
            pesertaList.value = [
                ...(pesertaRes.data.atlet || []).map((a: any) => ({ ...a, jenis_peserta: 'atlet' })),
            ];
        }

        // Load hasil tes yang sudah ada
        const hasilTesRes = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/hasil-tes`);
        const hasilTesMap = new Map<string, any>();
        if (hasilTesRes.data.success && hasilTesRes.data.data) {
            hasilTesRes.data.data.forEach((pesertaData: any) => {
                pesertaData.item_tes.forEach((item: any) => {
                    hasilTesMap.set(`${pesertaData.peserta_id}-${item.item_tes_id}`, item);
                });
            });
        }

        // Build table state
        tableState.value = pesertaList.value.map((peserta) => {
            const jenisKelamin = getJenisKelamin(peserta);
            const itemTesList: any[] = [];

            // Flatten aspek -> item tes (filter unique)
            const seenItemTesIds = new Set<number>();
            aspekList.value.forEach((aspek) => {
                (aspek.item_tes || []).forEach((itemTes: any) => {
                    // Skip jika item tes id sudah pernah ditambahkan
                    if (seenItemTesIds.has(itemTes.id)) {
                        return;
                    }
                    seenItemTesIds.add(itemTes.id);
                    
                    // Gunakan peserta.id (yang sudah di-map dari peserta_id di loadData)
                    const key = `${peserta.id}-${itemTes.id}`;
                    const existingHasil = hasilTesMap.get(key);
                    const target = getTarget(itemTes, jenisKelamin);

                    itemTesList.push({
                        aspek_id: aspek.id,
                        aspek_nama: aspek.nama,
                        item_tes_id: itemTes.id,
                        item_tes_nama: itemTes.nama,
                        satuan: itemTes.satuan,
                        target: target,
                        performa_arah: itemTes.performa_arah,
                        nilai: existingHasil?.nilai || '',
                        persentase: existingHasil?.persentase_performa !== null && existingHasil?.persentase_performa !== undefined 
                            ? (typeof existingHasil.persentase_performa === 'number' 
                                ? existingHasil.persentase_performa 
                                : parseFloat(existingHasil.persentase_performa) || null)
                            : null,
                        predikat: existingHasil?.predikat || null,
                    });
                });
            });

            return {
                peserta_id: peserta.id, // peserta.id sudah di-map dari peserta_id
                peserta: {
                    id: peserta.id,
                    nama: peserta.nama,
                    jenis_kelamin: jenisKelamin,
                    jenis_peserta: peserta.jenis_peserta,
                },
                item_tes: itemTesList,
            };
        });
    } catch (error: any) {
        console.error('Error loading data:', error);
        toast({
            title: error.response?.data?.message || 'Gagal memuat data',
            variant: 'destructive',
        });
    } finally {
        loadingData.value = false;
    }
};

// Reload hanya hasil tes (untuk mendapatkan nilai yang sudah di-calculate)
const reloadHasilTes = async () => {
    try {
        // Load hasil tes yang sudah ada
        const hasilTesRes = await axios.get(`/api/pemeriksaan-khusus/${props.item.id}/hasil-tes`);
        const hasilTesMap = new Map<string, any>();
        if (hasilTesRes.data.success && hasilTesRes.data.data) {
            hasilTesRes.data.data.forEach((pesertaData: any) => {
                pesertaData.item_tes.forEach((item: any) => {
                    hasilTesMap.set(`${pesertaData.peserta_id}-${item.item_tes_id}`, item);
                });
            });
        }

        // Update tableState secara incremental (tidak rebuild semua)
        await nextTick();
        tableState.value = tableState.value.map((row) => {
            const updatedRow = { ...row };
            updatedRow.item_tes = row.item_tes.map((item: any) => {
                const key = `${row.peserta_id}-${item.item_tes_id}`;
                const updatedItem = hasilTesMap.get(key);
                if (updatedItem) {
                    return {
                        ...item,
                        nilai: updatedItem.nilai || item.nilai,
                        persentase: updatedItem.persentase_performa !== null && updatedItem.persentase_performa !== undefined
                            ? (typeof updatedItem.persentase_performa === 'number'
                                ? updatedItem.persentase_performa
                                : parseFloat(updatedItem.persentase_performa) || null)
                            : (item.persentase !== null && item.persentase !== undefined
                                ? (typeof item.persentase === 'number'
                                    ? item.persentase
                                    : parseFloat(item.persentase) || null)
                                : null),
                        predikat: updatedItem.predikat ?? item.predikat,
                    };
                }
                return item;
            });
            return updatedRow;
        });
    } catch (error: any) {
        console.error('Error reloading hasil tes:', error);
        // Fallback: reload semua data jika ada error
        await loadData();
    }
};

// Update nilai dan calculate
const updateNilai = (rowIndex: number, itemTesIndex: number, nilai: string) => {
    const row = tableState.value[rowIndex];
    const itemTes = row.item_tes[itemTesIndex];
    const jenisKelamin = row.peserta.jenis_kelamin;

    itemTes.nilai = nilai;

    if (nilai && itemTes.target) {
        const result = calculatePerforma(nilai, itemTes.target, itemTes.performa_arah);
        itemTes.persentase = result.persentase;
        itemTes.predikat = result.predikat;
    } else {
        itemTes.persentase = null;
        itemTes.predikat = null;
    }
};

// Save data
const handleSave = async () => {
    loading.value = true;
    try {
        const dataToSave = {
            pemeriksaan_khusus_id: props.item.id,
            data: tableState.value.map((row) => ({
                peserta_id: row.peserta_id,
                item_tes: row.item_tes.map((item: any) => ({
                    item_tes_id: item.item_tes_id,
                    nilai: item.nilai || null,
                })),
            })),
        };

        const response = await axios.post('/api/pemeriksaan-khusus/save-hasil-tes', dataToSave);

        if (response.data?.success) {
            toast({
                title: response.data.message || 'Hasil tes berhasil disimpan',
                variant: 'success',
            });
            
            // Reload hasil tes saja (bukan semua data) untuk mendapatkan nilai yang sudah di-calculate
            await reloadHasilTes();
        }
    } catch (error: any) {
        console.error('Error saving hasil tes:', error);
        toast({
            title: error.response?.data?.message || 'Gagal menyimpan hasil tes',
            variant: 'destructive',
        });
    } finally {
        loading.value = false;
    }
};

// Group item tes by aspek untuk display
const groupedItemTes = computed(() => {
    if (aspekList.value.length === 0) return [];
    
    // Filter unique aspek berdasarkan id
    const uniqueAspek = aspekList.value.filter((aspek, index, self) => 
        index === self.findIndex((a) => a.id === aspek.id)
    );
    
    return uniqueAspek.map((aspek) => {
        // Filter unique item tes berdasarkan id
        const uniqueItemTes = (aspek.item_tes || []).filter((item: any, index: number, self: any[]) => 
            index === self.findIndex((i: any) => i.id === item.id)
        );
        
        return {
            id: aspek.id,
            nama: aspek.nama,
            item_tes: uniqueItemTes,
        };
    });
});

onMounted(() => {
    loadData();
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen w-full bg-gray-100 pt-4 dark:bg-neutral-950">
            <div class="mx-auto max-w-[95%] px-4">
                <!-- Info Card -->
                <Card class="mb-4">
                    <CardHeader>
                        <CardTitle>Informasi Pemeriksaan Khusus</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <CardDescription>Nama Pemeriksaan</CardDescription>
                                <p class="text-sm font-medium">{{ item.nama_pemeriksaan }}</p>
                            </div>
                            <div>
                                <CardDescription>Cabor</CardDescription>
                                <p class="text-sm font-medium">{{ item.cabor?.nama || '-' }}</p>
                            </div>
                            <div>
                                <CardDescription>Kategori</CardDescription>
                                <p class="text-sm font-medium">{{ item.caborKategori?.nama || '-' }}</p>
                            </div>
                            <div>
                                <CardDescription>Tanggal Pemeriksaan</CardDescription>
                                <p class="text-sm font-medium">
                                    {{ new Date(item.tanggal_pemeriksaan).toLocaleDateString('id-ID') }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Save Button -->
                <div class="mb-4 flex justify-end">
                    <Button @click="handleSave" :disabled="loading || loadingData" size="lg">
                        <Loader2 v-if="loading" class="h-4 w-4 mr-2 animate-spin" />
                        <Save v-else class="h-4 w-4 mr-2" />
                        {{ loading ? 'Menyimpan...' : 'Simpan Hasil Tes' }}
                    </Button>
                </div>

                <!-- Loading State -->
                <div v-if="loadingData" class="flex items-center justify-center py-12">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                    <span class="ml-2 text-muted-foreground">Memuat data...</span>
                </div>

                <!-- Table -->
                <div v-else-if="tableState.length > 0 && groupedItemTes.length > 0" class="overflow-x-auto rounded-xl bg-white shadow dark:bg-neutral-900">
                    <table class="w-full min-w-max border-separate border-spacing-0 text-sm">
                        <thead>
                            <!-- Header Row 1: Aspek -->
                            <tr class="bg-muted">
                                <th class="border-b px-3 py-2 text-left" rowspan="2">Nama</th>
                                <th class="border-b px-3 py-2 text-left" rowspan="2">Jenis Kelamin</th>
                                <template v-for="(aspek, aspekIdx) in groupedItemTes" :key="'aspek-header-' + aspek.id + '-' + aspekIdx">
                                    <th
                                        v-if="aspek.item_tes.length > 0"
                                        class="border-b px-3 py-2 text-center"
                                        :colspan="aspek.item_tes.length"
                                    >
                                        {{ aspek.nama }}
                                    </th>
                                </template>
                            </tr>
                            <!-- Header Row 2: Item Tes -->
                            <tr class="bg-muted">
                                <template v-for="(aspek, aspekIdx) in groupedItemTes" :key="'aspek-item-header-' + aspek.id + '-' + aspekIdx">
                                    <template v-for="(itemTes, itemIdx) in aspek.item_tes" :key="'item-header-' + itemTes.id + '-' + itemIdx">
                                        <th class="border-b px-2 py-1 text-center whitespace-nowrap">
                                            <div class="font-medium">{{ itemTes.nama }}</div>
                                            <div class="text-xs text-muted-foreground">{{ itemTes.satuan }}</div>
                                            <div class="text-xs text-muted-foreground mt-1">
                                                <span v-if="itemTes.target_laki_laki || itemTes.target_perempuan">
                                                    Target: 
                                                    <span v-if="itemTes.target_laki_laki">L: {{ itemTes.target_laki_laki }}</span>
                                                    <span v-if="itemTes.target_laki_laki && itemTes.target_perempuan"> / </span>
                                                    <span v-if="itemTes.target_perempuan">P: {{ itemTes.target_perempuan }}</span>
                                                </span>
                                                <span v-else class="text-muted-foreground/50">Target: -</span>
                                            </div>
                                        </th>
                                    </template>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, rowIndex) in tableState"
                                :key="'peserta-' + row.peserta_id"
                                class="hover:bg-muted/40 border-t transition"
                            >
                                <td class="border-b px-3 py-2 whitespace-nowrap">{{ row.peserta.nama }}</td>
                                <td class="border-b px-3 py-2 whitespace-nowrap">
                                    {{ row.peserta.jenis_kelamin === 'L' || row.peserta.jenis_kelamin === 'Laki-laki' ? 'Laki-laki' : 'Perempuan' }}
                                </td>
                                <template v-for="(aspek, aspekIndex) in groupedItemTes" :key="'aspek-cell-' + row.peserta_id + '-' + aspek.id + '-' + aspekIndex">
                                    <template v-for="(itemTes, itemIndex) in aspek.item_tes" :key="'item-cell-' + row.peserta_id + '-' + itemTes.id + '-' + rowIndex + '-' + itemIndex">
                                        <td class="border-b px-2 py-1">
                                            <div class="space-y-1">
                                                <!-- Input Nilai -->
                                                <template v-if="getItemTesData(row, itemTes.id)">
                                                    <Input
                                                        type="text"
                                                        class="w-24 text-center"
                                                        :model-value="getItemTesData(row, itemTes.id)?.nilai || ''"
                                                        @update:model-value="(val: string) => updateNilai(rowIndex, row.item_tes.findIndex((it: any) => it.item_tes_id === itemTes.id), val)"
                                                        :placeholder="getTarget(itemTes, row.peserta.jenis_kelamin) || 'Target'"
                                                    />
                                                    <!-- Persentase & Predikat -->
                                                    <div v-if="getItemTesData(row, itemTes.id)?.persentase != null" class="text-center">
                                                        <Badge
                                                            :class="getPredikatColor(getItemTesData(row, itemTes.id)?.predikat)"
                                                            variant="default"
                                                            class="text-xs"
                                                        >
                                                            {{ formatPersentase(getItemTesData(row, itemTes.id)?.persentase) }}%
                                                            <span class="ml-1" v-if="getItemTesData(row, itemTes.id)?.predikat">
                                                                ({{ getPredikatLabel(getItemTesData(row, itemTes.id)?.predikat) }})
                                                            </span>
                                                        </Badge>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                    </template>
                                </template>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="py-12 text-center text-muted-foreground">
                    <p>Belum ada aspek & item tes. Silakan setup terlebih dahulu.</p>
                    <Button
                        variant="outline"
                        class="mt-4"
                        @click="router.visit(`/pemeriksaan-khusus/${item.id}/setup`)"
                    >
                        Setup Aspek & Item Tes
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

