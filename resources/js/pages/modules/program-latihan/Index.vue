<script setup lang="ts">
import FilterModal from '@/components/FilterModal.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const breadcrumbs = [{ title: 'Program Latihan', href: '/program-latihan' }];

const formatPeriode = (startDate: string, endDate: string) => {
    if (!startDate || !endDate) return '-';

    const start = new Date(startDate);
    const end = new Date(endDate);

    const startDay = start.getDate();
    const startMonth = start.toLocaleDateString('id-ID', { month: 'long' });
    const startYear = start.getFullYear();

    const endDay = end.getDate();
    const endMonth = end.toLocaleDateString('id-ID', { month: 'long' });
    const endYear = end.getFullYear();

    // Jika tahun sama
    if (startYear === endYear) {
        // Jika bulan sama
        if (startMonth === endMonth) {
            return `${startDay}-${endDay} ${startMonth} ${startYear}`;
        } else {
            // Jika bulan berbeda
            return `${startDay} ${startMonth} - ${endDay} ${endMonth} ${startYear}`;
        }
    } else {
        // Jika tahun berbeda
        return `${startDay} ${startMonth} ${startYear} - ${endDay} ${endMonth} ${endYear}`;
    }
};

const formatTahap = (tahap: string) => {
    if (!tahap) return '-';
    const mapping: Record<string, string> = {
        'persiapan umum': 'Persiapan Umum',
        'persiapan khusus': 'Persiapan Khusus',
        'prapertandingan': 'Prapertandingan',
        'pertandingan': 'Pertandingan',
        'transisi': 'Transisi',
    };
    return mapping[tahap] || tahap;
};

const columns = [
    { key: 'nama_program', label: 'Nama Program' },
    // { key: 'rencana_latihan', label: 'Rencana Latihan', orderable: false },
    { key: 'cabor_nama', label: 'Cabor', orderable: false },
    {
        key: 'cabor_kategori_nama',
        label: 'Kategori',
        format: (row: any) => row.cabor_kategori_nama || row.cabor_kategori?.nama || '-',
        orderable: false,
    },
    {
        key: 'periode',
        label: 'Periode',
        format: (row: any) => formatPeriode(row.periode_mulai, row.periode_selesai),
        orderable: false,
    },
    {
        key: 'periode_hitung',
        label: 'Durasi',
        format: (row: any) => row.periode_hitung || '-',
        orderable: false,
    },
    {
        key: 'tahap',
        label: 'Tahap',
        format: (row: any) => formatTahap(row.tahap),
        orderable: false,
    },
    // { key: 'target_individu', label: 'Target Individu', orderable: false },
    // { key: 'target_kelompok', label: 'Target Kelompok', orderable: false },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

// Filter state
const showFilterModal = ref(false);
const currentFilters = ref<any>({});

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/program-latihan/${row.id}`),
        permission: 'Program Latihan Detail',
    },
    {
        label: 'Rekap Absen',
        onClick: () => router.visit(`/program-latihan/${row.id}/rekap-absen`),
        permission: 'Program Latihan Rekap Absen',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/program-latihan/${row.id}/edit`),
        permission: 'Program Latihan Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Program Latihan Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }
    try {
        const response = await axios.post('/program-latihan/destroy-selected', { ids: selected.value });
        selected.value = [];
        pageIndex.value.fetchData();
        toast({ title: response.data?.message || 'Data berhasil dihapus', variant: 'success' });
    } catch (error: any) {
        toast({ title: error.response?.data?.message || 'Gagal menghapus data', variant: 'destructive' });
    }
};

const deleteRow = async (row: any) => {
    await router.delete(`/program-latihan/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
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
        title="Program Latihan"
        module-name="Program Latihan"
        base-url="program-latihan"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/program-latihan/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val: number[]) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :on-delete-row="deleteRow"
        api-endpoint="/api/program-latihan"
        ref="pageIndex"
        :showImport="false"
        :showDelete="false"
        :showFilter="true"
        @filter="bukaFilterModal"
    >
    </PageIndex>

    <!-- Filter Modal -->
    <FilterModal
        :open="showFilterModal"
        @update:open="showFilterModal = $event"
        module-type="program-latihan"
        :initial-filters="currentFilters"
        @filter="handleFilter"
    />
</template>
