<script setup lang="ts">
import FilterModal from '@/components/FilterModal.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const { toast } = useToast();
const breadcrumbs = [{ title: 'Pemeriksaan', href: '/pemeriksaan' }];

const columns = [
    { key: 'peserta', label: 'Peserta', orderable: false },
    { key: 'parameter', label: 'Parameter', orderable: false },
    { key: 'cabor', label: 'Cabor', orderable: false },
    { key: 'cabor_kategori', label: 'Kategori', orderable: false },
    { key: 'tenaga_pendukung', label: 'Tenaga Pendukung', orderable: false },
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

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/pemeriksaan/${row.id}`),
        permission: 'Pemeriksaan Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/pemeriksaan/${row.id}/edit`),
        permission: 'Pemeriksaan Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Pemeriksaan Delete',
    },
];

const pageIndex = ref();

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post('/pemeriksaan/destroy-selected', { ids: selected.value });
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
    // Apply filters to the data table
    pageIndex.value.handleFilterFromParent(filters);
    toast({ title: 'Filter berhasil diterapkan', variant: 'success' });
};
</script>

<template>
    <PageIndex
        title="Pemeriksaan"
        module-name="Pemeriksaan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/pemeriksaan/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/pemeriksaan"
        ref="pageIndex"
        :showImport="false"
        :showDelete="false"
        :showFilter="true"
        :showStatistik="true"
        statistik-url="/pemeriksaan-parameter/AllParameter"
        @filter="bukaFilterModal"
    >
        <template #cell-parameter="{ row }">
            <div class="flex justify-center">
                <BadgeGroup
                    :badges="[
                        {
                            value: row.jumlah_parameter || 0,
                            colorClass: 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200',
                            onClick: () => router.visit(`/pemeriksaan/${row.id}/pemeriksaan-parameter`),
                        },
                    ]"
                />
            </div>
        </template>
        <template #cell-peserta="{ row }">
            <BadgeGroup
                :badges="[
                    {
                        label: 'Atlet',
                        value: row.jumlah_atlet || 0,
                        colorClass: 'bg-blue-100 text-blue-800 hover:bg-blue-200',
                        onClick: () => router.visit(`/pemeriksaan/${row.id}/peserta?jenis_peserta=atlet`),
                    },
                    {
                        label: 'Pelatih',
                        value: row.jumlah_pelatih || 0,
                        colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                        onClick: () => router.visit(`/pemeriksaan/${row.id}/peserta?jenis_peserta=pelatih`),
                    },
                    {
                        label: 'Tenaga Pendukung',
                        value: row.jumlah_tenaga_pendukung || 0,
                        colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                        onClick: () => router.visit(`/pemeriksaan/${row.id}/peserta?jenis_peserta=tenaga-pendukung`),
                    },
                ]"
            />
        </template>
    </PageIndex>

    <!-- Filter Modal -->
    <FilterModal
        :open="showFilterModal"
        @update:open="showFilterModal = $event"
        module-type="pemeriksaan"
        :initial-filters="currentFilters"
        @filter="handleFilter"
    />
</template>
