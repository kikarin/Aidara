<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast/useToast';
import axios from 'axios';
import { ArrowDown, ArrowUp, Loader2, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    caborId: number;
    caborNama: string;
}>();

// Filter states
const selectedKategoriId = ref<string>('all');
const selectedPemeriksaanIds = ref<number[]>([]);

// Data states
const kategoriList = ref<Array<{ id: number; nama: string }>>([]);
const pemeriksaanList = ref<Array<{ id: number; nama_pemeriksaan: string; tanggal_pemeriksaan: string }>>([]);
const perbandinganData = ref<any>(null);

// Loading states
const loadingKategori = ref(false);
const loadingPemeriksaan = ref(false);
const loadingPerbandingan = ref(false);

// Fetch kategori berdasarkan cabor
const fetchKategori = async () => {
    loadingKategori.value = true;
    try {
        const response = await axios.get(`/api/cabor-kategori-by-cabor/${props.caborId}`);
        kategoriList.value = response.data || [];
    } catch (error) {
        console.error('Error fetching kategori:', error);
        kategoriList.value = [];
    } finally {
        loadingKategori.value = false;
    }
};

// Fetch pemeriksaan khusus berdasarkan cabor (dan kategori jika dipilih)
const fetchPemeriksaan = async () => {
    loadingPemeriksaan.value = true;
    try {
        const params: any = {
            cabor_id: props.caborId,
            per_page: -1, // Get all
        };

        if (selectedKategoriId.value !== 'all') {
            params.cabor_kategori_id = selectedKategoriId.value;
        }

        const response = await axios.get('/api/pemeriksaan-khusus', { params });
        pemeriksaanList.value = (response.data.data || []).map((item: any) => ({
            id: item.id,
            nama_pemeriksaan: item.nama_pemeriksaan,
            tanggal_pemeriksaan: item.tanggal_pemeriksaan,
        }));
    } catch (error) {
        console.error('Error fetching pemeriksaan:', error);
        pemeriksaanList.value = [];
    } finally {
        loadingPemeriksaan.value = false;
    }
};

// Fetch data perbandingan
const fetchPerbandingan = async () => {
    if (selectedPemeriksaanIds.value.length < 2) {
        toast({
            title: 'Minimal 2 pemeriksaan harus dipilih',
            variant: 'destructive',
        });
        return;
    }

    loadingPerbandingan.value = true;
    try {
        const params: any = {
            pemeriksaan_khusus_ids: selectedPemeriksaanIds.value,
        };

        if (selectedKategoriId.value !== 'all') {
            params.cabor_kategori_id = selectedKategoriId.value;
        }

        const response = await axios.get(`/api/cabor/${props.caborId}/perbandingan-multi-tes`, { params });
        perbandinganData.value = response.data.data || null;
    } catch (error: any) {
        console.error('Error fetching perbandingan:', error);
        toast({
            title: 'Gagal mengambil data perbandingan',
            description: error.response?.data?.message || error.message,
            variant: 'destructive',
        });
        perbandinganData.value = null;
    } finally {
        loadingPerbandingan.value = false;
    }
};

// Watch untuk auto-fetch
watch(selectedKategoriId, () => {
    fetchPemeriksaan();
    selectedPemeriksaanIds.value = [];
    perbandinganData.value = null;
});

// Helper functions
const getTrendIcon = (trend: string) => {
    if (trend === 'naik') return ArrowUp;
    if (trend === 'turun') return ArrowDown;
    return null;
};

const getTrendColor = (trend: string) => {
    if (trend === 'naik') return 'text-green-600';
    if (trend === 'turun') return 'text-red-600';
    return 'text-gray-600';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('id-ID');
};

const formatPersentase = (value: number | null) => {
    if (value === null || value === undefined) return '-';
    return `${value.toFixed(1)}%`;
};

const getPredikatLabel = (predikat: string | null) => {
    const map: Record<string, string> = {
        sangat_kurang: 'Sangat Kurang',
        kurang: 'Kurang',
        sedang: 'Sedang',
        mendekati_target: 'Mendekati Target',
        target: 'Target',
    };
    return predikat ? map[predikat] || predikat : '-';
};

const getPredikatColor = (predikat: string | null) => {
    const map: Record<string, string> = {
        sangat_kurang: 'bg-red-100 text-red-800',
        kurang: 'bg-orange-100 text-orange-800',
        sedang: 'bg-yellow-100 text-yellow-800',
        mendekati_target: 'bg-blue-100 text-blue-800',
        target: 'bg-green-100 text-green-800',
    };
    return predikat ? map[predikat] || 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800';
};

// Computed untuk cek apakah bisa load perbandingan
const canLoadPerbandingan = computed(() => {
    return selectedPemeriksaanIds.value.length >= 2;
});

// Helper untuk mendapatkan rata-rata per pemeriksaan
const getRataRataKeseluruhan = (pemeriksaanId: number) => {
    if (!perbandinganData.value?.rata_rata_keseluruhan) return null;
    const data = perbandinganData.value.rata_rata_keseluruhan.find((r: any) => r.pemeriksaan_id === pemeriksaanId);
    return data?.rata_rata ?? null;
};

// Helper untuk mendapatkan nilai keseluruhan per pemeriksaan untuk peserta
const getNilaiKeseluruhanPeserta = (peserta: any, pemeriksaanId: number) => {
    if (!peserta.nilai_keseluruhan?.nilai_per_tes) return null;
    const data = peserta.nilai_keseluruhan.nilai_per_tes.find((n: any) => n.pemeriksaan_id === pemeriksaanId);
    return data || null;
};

// Helper untuk menghitung rata-rata nilai keseluruhan peserta di semua pemeriksaan
const getRataRataKeseluruhanPeserta = (peserta: any) => {
    if (!peserta.nilai_keseluruhan?.nilai_per_tes) return null;
    
    const nilaiList = peserta.nilai_keseluruhan.nilai_per_tes
        .map((n: any) => n.nilai_keseluruhan)
        .filter((nilai: any) => nilai !== null && nilai !== undefined);
    
    if (nilaiList.length === 0) return null;
    
    const total = nilaiList.reduce((sum: number, nilai: number) => sum + nilai, 0);
    return total / nilaiList.length;
};

// Initialize
onMounted(async () => {
    await fetchKategori();
    await fetchPemeriksaan();
});
</script>

<template>
    <div class="space-y-4">
        <!-- Filter Section -->
        <Card>
            <CardHeader>
                <CardTitle>Filter Perbandingan</CardTitle>
                <CardDescription>Pilih kategori dan pemeriksaan yang akan dibandingkan</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Filter Kategori -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>Kategori</Label>
                        <Select v-model="selectedKategoriId" :disabled="loadingKategori">
                            <SelectTrigger>
                                <SelectValue placeholder="Pilih Kategori" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua Kategori</SelectItem>
                                <SelectItem
                                    v-for="kategori in kategoriList"
                                    :key="kategori.id"
                                    :value="String(kategori.id)"
                                >
                                    {{ kategori.nama }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Filter Pemeriksaan (multi-select) -->
                    <div class="space-y-2">
                        <Label>Pemeriksaan Khusus (Min. 2)</Label>
                        <div class="border rounded-md p-2 min-h-[40px] max-h-32 overflow-auto">
                            <div v-if="loadingPemeriksaan" class="flex items-center justify-center py-2">
                                <Loader2 class="h-4 w-4 animate-spin" />
                            </div>
                            <div v-else-if="pemeriksaanList.length === 0" class="text-sm text-muted-foreground">
                                Tidak ada pemeriksaan
                            </div>
                            <div v-else class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="pemeriksaan in pemeriksaanList"
                                    :key="pemeriksaan.id"
                                    :variant="selectedPemeriksaanIds.includes(pemeriksaan.id) ? 'default' : 'outline'"
                                    class="cursor-pointer"
                                    @click="
                                        selectedPemeriksaanIds.includes(pemeriksaan.id)
                                            ? (selectedPemeriksaanIds = selectedPemeriksaanIds.filter(
                                                  (id) => id !== pemeriksaan.id
                                              ))
                                            : selectedPemeriksaanIds.push(pemeriksaan.id)
                                    "
                                >
                                    {{ pemeriksaan.nama_pemeriksaan }}
                                </Badge>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Dipilih: {{ selectedPemeriksaanIds.length }} pemeriksaan
                        </p>
                    </div>
                </div>

                <!-- Button Load Perbandingan -->
                <div class="flex justify-end">
                    <Button @click="fetchPerbandingan" :disabled="!canLoadPerbandingan || loadingPerbandingan">
                        <Loader2 v-if="loadingPerbandingan" class="mr-2 h-4 w-4 animate-spin" />
                        <TrendingUp v-else class="mr-2 h-4 w-4" />
                        Load Perbandingan
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Perbandingan Table -->
        <Card v-if="perbandinganData">
            <CardHeader>
                <CardTitle>Hasil Perbandingan Multi-Tes</CardTitle>
                <CardDescription>Perbandingan performa aspek antar pemeriksaan</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="loadingPerbandingan" class="flex items-center justify-center py-8">
                    <Loader2 class="h-8 w-8 animate-spin" />
                </div>
                <div v-else-if="!perbandinganData || !perbandinganData.perbandingan_per_peserta || perbandinganData.perbandingan_per_peserta.length === 0" class="text-center py-8 text-muted-foreground">
                    Tidak ada data perbandingan
                </div>
                <div v-else class="overflow-x-auto">
                    <div v-for="peserta in perbandinganData.perbandingan_per_peserta" :key="peserta.peserta_id" class="mb-6">
                        <h3 class="text-lg font-semibold mb-3">
                            {{ peserta.nama }}
                            <span v-if="(peserta.posisi && peserta.posisi !== '-') || (peserta.usia && peserta.usia !== '-' && peserta.usia !== null && peserta.usia !== undefined)" class="text-sm font-normal text-muted-foreground">
                                ({{ peserta.posisi && peserta.posisi !== '-' ? peserta.posisi : '' }}{{ (peserta.posisi && peserta.posisi !== '-') && (peserta.usia && peserta.usia !== '-' && peserta.usia !== null && peserta.usia !== undefined) ? ', ' : '' }}{{ peserta.usia && peserta.usia !== '-' && peserta.usia !== null && peserta.usia !== undefined ? peserta.usia + ' tahun' : '' }})
                            </span>
                        </h3>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Aspek</TableHead>
                                    <TableHead v-for="pemeriksaan in perbandinganData.pemeriksaan_list" :key="pemeriksaan.id" class="text-center">
                                        <div>{{ pemeriksaan.nama_pemeriksaan }}</div>
                                        <div class="text-xs text-muted-foreground">{{ formatDate(pemeriksaan.tanggal_pemeriksaan) }}</div>
                                    </TableHead>
                                    <TableHead class="text-center">Trend</TableHead>
                                    <TableHead class="text-center">Rata-rata</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="aspek in peserta.perbandingan_aspek" :key="aspek.aspek_id">
                                    <TableCell class="font-medium">{{ aspek.aspek_nama }}</TableCell>
                                    <TableCell
                                        v-for="(nilaiTes, idx) in aspek.nilai_per_tes"
                                        :key="`${aspek.aspek_id}-${nilaiTes.pemeriksaan_id}`"
                                        class="text-center"
                                    >
                                        <div v-if="nilaiTes.nilai_performa !== null">
                                            <div class="font-semibold">{{ formatPersentase(nilaiTes.nilai_performa) }}</div>
                                            <Badge :class="getPredikatColor(nilaiTes.predikat)" class="text-xs mt-1">
                                                {{ getPredikatLabel(nilaiTes.predikat) }}
                                            </Badge>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div v-if="aspek.trend !== 'stabil'" class="flex items-center justify-center gap-1" :class="getTrendColor(aspek.trend)">
                                            <component :is="getTrendIcon(aspek.trend)" class="h-4 w-4" />
                                            <span class="text-xs">{{ aspek.trend === 'naik' ? 'Naik' : 'Turun' }}</span>
                                            <span v-if="aspek.selisih !== null" class="text-xs">({{ aspek.selisih > 0 ? '+' : '' }}{{ aspek.selisih.toFixed(1) }}%)</span>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs">Stabil</span>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <!-- Kolom rata-rata kosong untuk aspek -->
                                    </TableCell>
                                </TableRow>
                                <!-- Baris Nilai Keseluruhan -->
                                <TableRow class="bg-muted/50 font-semibold">
                                    <TableCell class="font-bold">Nilai Keseluruhan</TableCell>
                                    <TableCell
                                        v-for="pemeriksaan in perbandinganData.pemeriksaan_list"
                                        :key="`keseluruhan-${pemeriksaan.id}`"
                                        class="text-center"
                                    >
                                        <div v-if="getNilaiKeseluruhanPeserta(peserta, pemeriksaan.id)">
                                            <div v-if="getNilaiKeseluruhanPeserta(peserta, pemeriksaan.id)?.nilai_keseluruhan !== null">
                                                <div class="font-bold text-lg">{{ formatPersentase(getNilaiKeseluruhanPeserta(peserta, pemeriksaan.id)?.nilai_keseluruhan) }}</div>
                                                <Badge :class="getPredikatColor(getNilaiKeseluruhanPeserta(peserta, pemeriksaan.id)?.predikat)" class="text-xs mt-1">
                                                    {{ getPredikatLabel(getNilaiKeseluruhanPeserta(peserta, pemeriksaan.id)?.predikat) }}
                                                </Badge>
                                            </div>
                                            <span v-else class="text-muted-foreground">-</span>
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                    <TableCell class="text-center">
                                        <div v-if="peserta.nilai_keseluruhan?.trend && peserta.nilai_keseluruhan.trend !== 'stabil'" class="flex items-center justify-center gap-1" :class="getTrendColor(peserta.nilai_keseluruhan.trend)">
                                            <component :is="getTrendIcon(peserta.nilai_keseluruhan.trend)" class="h-4 w-4" />
                                            <span class="text-xs">{{ peserta.nilai_keseluruhan.trend === 'naik' ? 'Naik' : 'Turun' }}</span>
                                            <span v-if="peserta.nilai_keseluruhan.selisih !== null" class="text-xs">({{ peserta.nilai_keseluruhan.selisih > 0 ? '+' : '' }}{{ peserta.nilai_keseluruhan.selisih.toFixed(1) }}%)</span>
                                        </div>
                                        <span v-else class="text-muted-foreground text-xs">Stabil</span>
                                    </TableCell>
                                    <TableCell class="text-center bg-primary/5">
                                        <div v-if="getRataRataKeseluruhanPeserta(peserta) !== null" class="font-bold text-lg text-primary">
                                            {{ formatPersentase(getRataRataKeseluruhanPeserta(peserta)) }}
                                        </div>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

