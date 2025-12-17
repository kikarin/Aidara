<script setup lang="ts">
import FilterModal from '@/components/FilterModal.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';
import PesertaModal from './components/PesertaModal.vue';

const { toast } = useToast();
const breadcrumbs = [{ title: 'Pemeriksaan Khusus', href: '/pemeriksaan-khusus' }];

const columns = [
    { key: 'peserta', label: 'Peserta', orderable: false },
    { key: 'cabor', label: 'Cabor', orderable: false },
    { key: 'cabor_kategori', label: 'Kategori', orderable: false },
    { key: 'nama_pemeriksaan', label: 'Nama Pemeriksaan' },
    { key: 'tanggal_pemeriksaan', label: 'Tanggal Pemeriksaan' },
    {
        key: 'status',
        label: 'Status',
        format: (row: any) => {
            if (row.status === 'belum') return '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-300 rounded-full">Belum</span>';
            if (row.status === 'sebagian')
                return '<span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded-full">Sebagian</span>';
            if (row.status === 'selesai')
                return '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Selesai</span>';
            return row.status;
        },
    },
];

const selected = ref<number[]>([]);

// Filter state
const showFilterModal = ref(false);
const currentFilters = ref<any>({});

// Peserta modal state
const showPesertaModal = ref(false);
const selectedPesertaData = ref<any[]>([]);
const selectedPesertaTipe = ref<string>('');
const selectedPemeriksaanKhususId = ref<number | null>(null);

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pemeriksaan-khusus/${row.id}`),
        permission: 'Pemeriksaan Khusus Detail',
    },
    {
        label: 'Input Hasil Tes',
        onClick: () => router.visit(`/pemeriksaan-khusus/${row.id}/input-hasil-tes`),
        permission: 'Pemeriksaan Khusus Input Hasil Tes',
    },
    {
        label: 'Setup',
        onClick: () => router.visit(`/pemeriksaan-khusus/${row.id}/setup`),
        permission: 'Pemeriksaan Khusus Setup',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pemeriksaan-khusus/${row.id}/edit`),
        permission: 'Pemeriksaan Khusus Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Pemeriksaan Khusus Delete',
    },
];

const pageIndex = ref();

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post('/pemeriksaan-khusus/destroy-selected', { ids: selected.value });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message || 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus data', variant: 'destructive' });
    }
};

const bukaFilterModal = () => {
    showFilterModal.value = true;
};

const handleFilter = (filters: any) => {
    currentFilters.value = filters;
    showFilterModal.value = false;
    
    // Apply filters to PageIndex
    if (pageIndex.value) {
        pageIndex.value.handleFilterFromParent(filters);
    }
    toast({ title: 'Filter berhasil diterapkan', variant: 'success' });
};

const handlePesertaClick = async (pemeriksaanKhususId: number, tipe: string) => {
    try {
        const response = await axios.get(`/api/pemeriksaan-khusus/${pemeriksaanKhususId}/peserta?jenis_peserta=${tipe}`);
        if (response.data.success) {
            selectedPesertaData.value = response.data.data || [];
            selectedPesertaTipe.value = tipe;
            selectedPemeriksaanKhususId.value = pemeriksaanKhususId;
            showPesertaModal.value = true;
        } else {
            toast({ title: 'Gagal mengambil data peserta', variant: 'destructive' });
        }
    } catch (error: any) {
        console.error('Gagal mengambil data peserta:', error);
        toast({ 
            title: error.response?.data?.message || 'Gagal mengambil data peserta', 
            variant: 'destructive' 
        });
    }
};

const handleRefreshPeserta = async () => {
    if (selectedPemeriksaanKhususId.value && selectedPesertaTipe.value) {
        try {
            const response = await axios.get(`/api/pemeriksaan-khusus/${selectedPemeriksaanKhususId.value}/peserta?jenis_peserta=${selectedPesertaTipe.value}`);
            if (response.data.success) {
                selectedPesertaData.value = response.data.data || [];
                // Refresh data table juga
                pageIndex.value?.fetchData();
            }
        } catch (error) {
            console.error('Gagal refresh data peserta:', error);
        }
    }
};

const closePesertaModal = () => {
    showPesertaModal.value = false;
    selectedPesertaData.value = [];
    selectedPesertaTipe.value = '';
    selectedPemeriksaanKhususId.value = null;
};

</script>

<template>
    <PageIndex
        title="Pemeriksaan Khusus"
        module-name="Pemeriksaan Khusus"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/pemeriksaan-khusus/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/pemeriksaan-khusus"
        ref="pageIndex"
        :showImport="false"
        :showDelete="false"
        :showFilter="true"
        @filter="bukaFilterModal"
    >       
        <template #cell-peserta="{ row }">
            <BadgeGroup
                :badges="[
                    {
                        label: 'Atlet',
                        value: row.jumlah_atlet || 0,
                        colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                        onClick: () => handlePesertaClick(row.id, 'atlet'),
                    },
                    {
                        label: 'Pelatih',
                        value: row.jumlah_pelatih || 0,
                        colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                        onClick: () => handlePesertaClick(row.id, 'pelatih'),
                    },
                    {
                        label: 'Tenaga Pendukung',
                        value: row.jumlah_tenaga_pendukung || 0,
                        colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                        onClick: () => handlePesertaClick(row.id, 'tenaga_pendukung'),
                    },
                ]"
            />
        </template>
    </PageIndex>

    <!-- Filter Modal -->
    <FilterModal
        :open="showFilterModal"
        @update:open="showFilterModal = $event"
        module-type="pemeriksaan-khusus"
        :initial-filters="currentFilters"
        @filter="handleFilter"
    />

    <!-- Peserta Modal -->
    <PesertaModal
        :show="showPesertaModal"
        :data="selectedPesertaData"
        :tipe="selectedPesertaTipe"
        :pemeriksaan-khusus-id="selectedPemeriksaanKhususId"
        @close="closePesertaModal"
        @refresh="handleRefreshPeserta"
    />
</template>

