<script setup lang="ts">
import FilterModal from '@/components/FilterModal.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [{ title: 'Pelatih', href: '/pelatih' }];

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

const columns = [
    {
        key: 'foto',
        label: 'Foto',
        format: (row: any) => {
            if (row.foto) {
                return `<div class="cursor-pointer" onclick="window.open('${row.foto}', '_blank')">
                    <img src="${row.foto}" alt="Foto ${row.nama}" class="w-12 h-12 object-cover rounded-full border hover:shadow-md transition-shadow" />
                </div>`;
            }
            return '<div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs">No</div>';
        },
        orderable: false,
    },
    { key: 'nama', label: 'Nama' },
    {
        key: 'jenis_kelamin',
        label: 'Jenis Kelamin',
        format: (row: any) => {
            return row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : '-';
        },
    },
    { key: 'tempat_lahir', label: 'Tempat Lahir' },
    {
        key: 'tanggal_lahir',
        label: 'Tanggal Lahir',
        format: (row: any) => {
            return row.tanggal_lahir
                ? new Date(row.tanggal_lahir).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'numeric',
                      year: 'numeric',
                  })
                : '-';
        },
    },
    {
        key: 'usia',
        label: 'Usia',
        format: (row: any) => {
            return calculateAge(row.tanggal_lahir);
        },
    },
    {
        key: 'tanggal_bergabung',
        label: 'Tanggal Bergabung',
        format: (row: any) => {
            return row.tanggal_bergabung
                ? new Date(row.tanggal_bergabung).toLocaleDateString('id-ID', {
                      day: 'numeric',
                      month: 'numeric',
                      year: 'numeric',
                  })
                : '-';
        },
    },
    {
        key: 'lama_bergabung',
        label: 'Lama Bergabung',
        format: (row: any) => getLamaBergabung(row.tanggal_bergabung),
    },

    { key: 'no_hp', label: 'No HP' },
    {
        key: 'is_active',
        label: 'Status',
        format: (row: any) => {
            return row.is_active
                ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Aktif</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Nonaktif</span>';
        },
        orderable: false,
    },
];

const selected = ref<number[]>([]);
const { toast } = useToast();

// Filter state
const showFilterModal = ref(false);
const currentFilters = ref<any>({});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pelatih/${row.id}`),
        permission: 'Pelatih Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pelatih/${row.id}/edit`),
        permission: 'Pelatih Edit',
    },
    {
        label: 'Riwayat Pemeriksaan',
        onClick: () => router.visit(`/pelatih/${row.id}/riwayat-pemeriksaan`),
        permission: 'Pelatih Detail',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Pelatih Delete',
    },
];

const pageIndex = ref();

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post('/pelatih/destroy-selected', { ids: selected.value });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message || 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus data', variant: 'destructive' });
    }
};

const deletePelatih = async (row: any) => {
    await router.delete(`/pelatih/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};

const showImportModal = ref(false);
const importFile = ref<File | null>(null);
const importLoading = ref(false);
const fileName = ref<string>('');
const fileInput = ref<HTMLInputElement>();

function openImportModal() {
    showImportModal.value = true;
    importFile.value = null;
    fileName.value = '';
}
function closeImportModal() {
    showImportModal.value = false;
    importFile.value = null;
    fileName.value = '';
}
function handleFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!validTypes.includes(file.type)) {
            toast({ title: 'File harus berformat Excel (.xlsx atau .xls)', variant: 'destructive' });
            target.value = '';
            return;
        }
        importFile.value = file;
        fileName.value = file.name;
    }
}

async function handleImport() {
    if (!importFile.value) return toast({ title: 'Pilih file Excel terlebih dahulu', variant: 'destructive' });
    importLoading.value = true;
    const formData = new FormData();
    formData.append('file', importFile.value);
    try {
        const response = await axios.post('/pelatih/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
        if (response.data.success) {
            const data = response.data.data;
            let message = response.data.message;
            if (data.error_count > 0) {
                const errorGroups: Record<string, number[]> = {};
                data.errors.forEach((err: any) => {
                    if (!errorGroups[err.error]) errorGroups[err.error] = [];
                    errorGroups[err.error].push(err.row);
                });
                message += '\n\nError yang ditemukan:';
                Object.entries(errorGroups).forEach(([err, rows]) => {
                    message += `\n• Baris ${rows.join(', ')}: ${err}`;
                });
            }
            toast({ title: message, variant: data.error_count > 0 ? 'destructive' : 'success' });
            closeImportModal();
            pageIndex.value.fetchData();
        } else {
            toast({ title: response.data.message || 'Gagal import', variant: 'destructive' });
        }
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal import', variant: 'destructive' });
    } finally {
        importLoading.value = false;
    }
}

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

const bukaFilterModal = () => {
    showFilterModal.value = true;
};

const handleFilter = (filters: any) => {
    currentFilters.value = filters;
    // Apply filters to the data table
    pageIndex.value.handleFilterFromParent(filters);
    toast({ title: 'Filter berhasil diterapkan', variant: 'success' });
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Pelatih"
            module-name="Pelatih"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/pelatih/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/pelatih"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deletePelatih"
            @import="openImportModal"
            :showImport="true"
            :showStatistik="true"
            statistik-url="/pelatih/karakteristik"
            :showFilter="true"
            @filter="bukaFilterModal"
        />

        <!-- Filter Modal -->
        <FilterModal
            :open="showFilterModal"
            @update:open="showFilterModal = $event"
            module-type="pelatih"
            :initial-filters="currentFilters"
            @filter="handleFilter"
        />

        <Dialog v-model:open="showImportModal">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Import Data Pelatih</DialogTitle>
                    <DialogDescription> Upload file Excel (.xlsx atau .xls) yang berisi data pelatih. </DialogDescription>
                </DialogHeader>
                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">File Excel</label>
                        <div class="flex items-center gap-2">
                            <input
                                ref="fileInput"
                                type="file"
                                accept=".xlsx,.xls"
                                @change="handleFileChange"
                                class="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-10 w-full rounded-md border px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                        <div v-if="fileName" class="text-muted-foreground text-sm">File: {{ fileName }}</div>
                    </div>
                    <div class="bg-muted rounded-lg border p-3">
                        <div class="mb-2 text-sm font-medium">Template kolom yang didukung:</div>
                        <div class="mt-2">
                            <a href="/template-import/template_import.xlsx" target="_blank" class="text-sm text-blue-600 hover:underline" download>
                                Unduh Format Excel Pelatih
                            </a>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" @click="closeImportModal" :disabled="importLoading"> Batal </Button>
                    <Button @click="handleImport" :disabled="importLoading || !importFile">
                        {{ importLoading ? 'Mengimpor...' : 'Import' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
