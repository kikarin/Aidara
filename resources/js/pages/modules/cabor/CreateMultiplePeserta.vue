<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/components/ui/toast/useToast';
import ButtonsForm from '@/pages/modules/base-page/ButtonsForm.vue';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const props = defineProps<{
    cabor: {
        id: number;
        nama: string;
        kategori_peserta_id?: number | null;
    };
    tipe: 'atlet' | 'pelatih' | 'tenaga_pendukung';
}>();

const { toast } = useToast();

const tipeLabel = computed(() => {
    switch (props.tipe) {
        case 'atlet':
            return 'Atlet';
        case 'pelatih':
            return 'Pelatih';
        case 'tenaga_pendukung':
            return 'Tenaga Pendukung';
        default:
            return 'Peserta';
    }
});

const posisiLabel = computed(() => {
    switch (props.tipe) {
        case 'atlet':
            return 'Posisi/Nomor/Kelas';
        case 'pelatih':
            return 'Jenis Pelatih';
        case 'tenaga_pendukung':
            return 'Jenis Tenaga Pendukung';
        default:
            return 'Posisi/Jenis';
    }
});

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: props.cabor.nama, href: `/cabor/${props.cabor.id}` },
    { title: `Tambah ${tipeLabel.value}`, href: '#' },
];

const calculateAge = (birthDate: string | null | undefined): number | string => {
    if (!birthDate) return '-';
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
};

function getLamaBergabung(tanggalBergabung: string) {
    if (!tanggalBergabung) return '-';
    const start = new Date(tanggalBergabung);
    const now = new Date();
    let tahun = now.getFullYear() - start.getFullYear();
    let bulan = now.getMonth() - start.getMonth();
    if (bulan < 0) {
        tahun--;
        bulan += 12;
    }
    let result = '';
    if (tahun > 0) result += tahun + ' tahun ';
    if (bulan > 0) result += bulan + ' bulan';
    if (!result) result = 'Kurang dari 1 bulan';
    return result.trim();
}

const selectedIds = ref<number[]>([]);
const dataList = ref<any[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const total = ref(0);
const selectedStatus = ref(1);
const posisi = ref('');

const columns = [
    { key: 'nama', label: 'Nama' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-'),
    },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => calculateAge(row.tanggal_lahir),
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => getLamaBergabung(row.tanggal_bergabung),
    },
    {
        key: 'is_active',
        label: 'Status',
        format: (row: any) =>
            row.is_active
                ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Aktif</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Nonaktif</span>',
    },
];

const apiEndpoint = computed(() => {
    switch (props.tipe) {
        case 'atlet':
            return '/api/atlet';
        case 'pelatih':
            return '/api/pelatih';
        case 'tenaga_pendukung':
            return '/api/tenaga-pendukung';
        default:
            return '/api/atlet';
    }
});

// Fetch data yang belum ada di cabor ini
const fetchAvailableData = async () => {
    loading.value = true;
    try {
        const params: Record<string, any> = {
                page: currentPage.value > 1 ? currentPage.value - 1 : 0,
                per_page: perPage.value,
                search: searchQuery.value,
                exclude_cabor_id: props.cabor.id, // Exclude yang sudah ada di cabor ini
        };
        
        // Filter berdasarkan kategori_peserta_id dari cabor jika ada
        if (props.cabor.kategori_peserta_id) {
            params.kategori_peserta_id = props.cabor.kategori_peserta_id;
        }
        
        const response = await axios.get(apiEndpoint.value, { params });
        dataList.value = response.data.data || [];
        total.value = response.data.meta?.total || 0;
    } catch (error) {
        console.error('Gagal mengambil data:', error);
        toast({ title: 'Gagal mengambil data', variant: 'destructive' });
    } finally {
        loading.value = false;
    }
};

const toggleSelect = (id: number) => {
    const index = selectedIds.value.indexOf(id);
    if (index > -1) {
        selectedIds.value.splice(index, 1);
    } else {
        selectedIds.value.push(id);
    }
};

const toggleSelectAll = (checked: boolean) => {
    if (checked) {
        selectedIds.value = dataList.value.map((item) => item.id);
    } else {
        selectedIds.value = [];
    }
};

const isSelected = (id: number) => selectedIds.value.includes(id);

const handleSearch = (value: string) => {
    searchQuery.value = value;
    currentPage.value = 1;
    fetchAvailableData();
};

const handlePageChange = (page: number) => {
    currentPage.value = page;
    fetchAvailableData();
};

const handlePerPageChange = (value: number) => {
    perPage.value = value;
    currentPage.value = 1;
    fetchAvailableData();
};

const totalPages = computed(() => Math.ceil(total.value / perPage.value));

const getPageNumbers = () => {
    const pages = [];
    const maxPages = 5;
    let start = Math.max(1, currentPage.value - Math.floor(maxPages / 2));
    const end = Math.min(totalPages.value, start + maxPages - 1);
    if (end - start + 1 < maxPages) {
        start = Math.max(1, end - maxPages + 1);
    }
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
};

const handleSave = async () => {
    if (selectedIds.value.length === 0) {
        toast({ title: `Pilih minimal 1 ${tipeLabel.value.toLowerCase()}`, variant: 'destructive' });
        return;
    }
    try {
        await router.post(
            `/cabor/${props.cabor.id}/peserta/${props.tipe}/store`,
            {
                peserta_ids: selectedIds.value,
                is_active: selectedStatus.value,
                posisi: posisi.value,
            },
            {
                onSuccess: () => {
                    toast({ title: `${tipeLabel.value} berhasil ditambahkan ke cabor`, variant: 'success' });
                },
                onError: () => {
                    toast({ title: `Gagal menambahkan ${tipeLabel.value.toLowerCase()}`, variant: 'destructive' });
                },
            },
        );
    } catch {
        toast({ title: `Gagal menambahkan ${tipeLabel.value.toLowerCase()}`, variant: 'destructive' });
    }
};

const handleCancel = () => {
    router.visit('/cabor');
};

// Load data saat komponen dimount
fetchAvailableData();
</script>

<template>
    <PageCreate :title="`Tambah ${tipeLabel} ke ${cabor.nama}`" :breadcrumbs="breadcrumbs" back-url="/cabor" :use-grid="true">
        <div class="space-y-6">
            <!-- Informasi Cabor -->
            <div class="bg-card rounded-lg border p-4">
                <h3 class="mb-2 text-lg font-semibold">Informasi Cabor</h3>
                <div class="flex items-center gap-2">
                    <span class="text-muted-foreground text-sm font-medium">Cabor:</span>
                    <span class="text-sm font-medium">{{ cabor.nama }}</span>
                </div>
            </div>

            <!-- Input Posisi/Jenis (opsional) -->
            <div class="flex items-center gap-4">
                <div class="w-64">
                    <label class="mb-2 block text-sm font-medium">{{ posisiLabel }} (Opsional)</label>
                    <Input v-model="posisi" :placeholder="`Masukkan ${posisiLabel.toLowerCase()}`" />
                </div>
            </div>

            <!-- Selection Counter -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Pilih {{ tipeLabel }}</h3>
                <div class="flex items-center gap-4">
                    <Select v-model="selectedStatus" class="w-32">
                        <SelectTrigger>
                            <SelectValue placeholder="Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="1">Aktif</SelectItem>
                            <SelectItem :value="0">Nonaktif</SelectItem>
                        </SelectContent>
                    </Select>
                    <Badge variant="secondary"> {{ selectedIds.length }} {{ tipeLabel.toLowerCase() }} dipilih </Badge>
                </div>
            </div>

            <!-- Search dan Length -->
            <div class="flex flex-col flex-wrap items-center justify-center gap-4 text-center sm:flex-row sm:justify-between">
                <div class="ml-2 flex items-center gap-2">
                    <span class="text-muted-foreground text-sm">Show</span>
                    <Select :model-value="perPage" @update:model-value="(val: any) => handlePerPageChange(val as number)">
                        <SelectTrigger class="w-24">
                            <SelectValue :placeholder="String(perPage)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="10">10</SelectItem>
                            <SelectItem :value="25">25</SelectItem>
                            <SelectItem :value="50">50</SelectItem>
                            <SelectItem :value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    <span class="text-muted-foreground text-sm">entries</span>
                </div>
                <div class="w-full sm:w-64">
                    <Input :model-value="searchQuery" @update:model-value="(val: any) => handleSearch(val as string)" placeholder="Search..." class="w-full" />
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="border-primary h-8 w-8 animate-spin rounded-full border-b-2"></div>
                <span class="text-muted-foreground ml-2 text-sm">Memuat data...</span>
            </div>

            <!-- Empty State -->
            <div v-else-if="dataList.length === 0" class="py-8 text-center">
                <p class="text-muted-foreground">Tidak ada {{ tipeLabel.toLowerCase() }} yang tersedia</p>
            </div>

            <!-- Table -->
            <div v-else class="rounded-md shadow-sm">
                <div class="w-full overflow-x-auto">
                    <Table class="min-w-max">
                        <TableHeader class="bg-muted">
                            <TableRow>
                                <TableHead class="w-12 text-center">No</TableHead>
                                <TableHead class="w-10 text-center">
                                    <label class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500">
                                        <input
                                            type="checkbox"
                                            class="peer sr-only"
                                            :checked="selectedIds.length > 0 && selectedIds.length === dataList.length"
                                            @change="(e) => toggleSelectAll((e.target as HTMLInputElement).checked)"
                                        />
                                        <div class="bg-primary h-3 w-3 scale-0 transform rounded-sm transition-all peer-checked:scale-100"></div>
                                    </label>
                                </TableHead>
                                <TableHead v-for="col in columns" :key="col.key">{{ col.label }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, index) in dataList" :key="item.id" class="hover:bg-muted/40 border-t transition">
                                <TableCell class="px-2 text-center text-xs sm:px-4 sm:text-sm">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </TableCell>
                                <TableCell class="px-2 text-center text-xs sm:px-4 sm:text-sm">
                                    <label class="bg-background relative inline-flex h-5 w-5 cursor-pointer items-center justify-center rounded border border-gray-500">
                                        <input type="checkbox" class="peer sr-only" :checked="isSelected(item.id)" @change="() => toggleSelect(item.id)" />
                                        <svg
                                            class="text-primary h-4 w-4 scale-75 opacity-0 transition-all duration-200 peer-checked:scale-100 peer-checked:opacity-100"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </label>
                                </TableCell>
                                <TableCell v-for="col in columns" :key="col.key">
                                    <span v-if="typeof col.format === 'function'" v-html="col.format(item)"></span>
                                    <span v-else>{{ item[col.key] }}</span>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="text-muted-foreground flex flex-col items-center justify-center gap-2 border-t p-4 text-center text-sm md:flex-row md:justify-between">
                    <span> Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} entries </span>
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <Button size="sm" :disabled="currentPage === 1" @click="handlePageChange(currentPage - 1)" class="bg-muted/40 text-foreground"> Previous </Button>
                        <div class="flex flex-wrap items-center gap-1">
                            <Button
                                v-for="page in getPageNumbers()"
                                :key="page"
                                size="sm"
                                class="rounded-md border px-3 py-1.5 text-sm"
                                :class="[currentPage === page ? 'bg-primary text-primary-foreground border-primary' : 'bg-muted border-input text-black dark:text-white']"
                                @click="handlePageChange(page)"
                            >
                                {{ page }}
                            </Button>
                        </div>
                        <Button size="sm" :disabled="currentPage === totalPages" @click="handlePageChange(currentPage + 1)" class="bg-muted/40 text-foreground"> Next </Button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <ButtonsForm
                :show-save="true"
                :show-cancel="true"
                :save-text="`Tambah ${selectedIds.length} ${tipeLabel}`"
                :save-disabled="selectedIds.length === 0"
                @save="handleSave"
                @cancel="handleCancel"
            />
        </div>
    </PageCreate>
</template>

