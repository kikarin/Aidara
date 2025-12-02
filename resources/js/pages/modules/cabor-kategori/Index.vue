<script setup lang="ts">
import FilterModal from '@/components/FilterModal.vue';
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import BadgeGroup from '../components/BadgeGroup.vue';

const breadcrumbs = [{ title: 'Cabor Kategori', href: '/cabor-kategori' }];

const columns = [
    { key: 'peserta', label: 'Peserta', sortable: false, orderable: false },
    { key: 'cabor_nama', label: 'Cabor', orderable: false },
    { key: 'nama', label: 'Nama' },
    {
        key: 'jenis_kelamin',
        label: 'Gender',
        format: (row: any) => (row.jenis_kelamin === 'L' ? 'Laki-laki' : row.jenis_kelamin === 'P' ? 'Perempuan' : 'Campuran'),
        orderable: false,
    },
    { 
        key: 'kategori_peserta_nama', 
        label: 'Jenis',
        format: (row: any) => row.kategori_peserta_nama || '-',
        orderable: false,
    },
    { key: 'deskripsi', label: 'Deskripsi' },
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
        onClick: () => router.visit(`/cabor-kategori/${row.id}`),
        permission: 'Cabor Kategori Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/cabor-kategori/${row.id}/edit`),
        permission: 'Cabor Kategori Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Cabor Kategori Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/cabor-kategori/destroy-selected', {
            ids: selected.value,
        });

        selected.value = [];
        pageIndex.value.fetchData();

        toast({
            title: response.data?.message,
            variant: 'success',
        });
    } catch (error: any) {
        console.error('Gagal menghapus data:', error);

        const message = error.response?.data?.message;
        toast({
            title: message,
            variant: 'destructive',
        });
    }
};

const deleteKategori = async (row: any) => {
    await router.delete(`/cabor-kategori/${row.id}`, {
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
    <div class="space-y-4">
        <PageIndex
            title="Cabor Kategori"
            module-name="Cabor Kategori"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/cabor-kategori/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val: number[]) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/cabor-kategori"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteKategori"
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
                            onClick: () => router.visit(`/cabor-kategori/${row.id}/atlet`),
                        },
                        {
                            label: 'Pelatih',
                            value: row.jumlah_pelatih || 0,
                            colorClass: 'bg-green-100 text-green-800 hover:bg-green-200',
                            onClick: () => router.visit(`/cabor-kategori/${row.id}/pelatih`),
                        },
                        {
                            label: 'Tenaga Pendukung',
                            value: row.jumlah_tenaga_pendukung || 0,
                            colorClass: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200',
                            onClick: () => router.visit(`/cabor-kategori/${row.id}/tenaga-pendukung`),
                        },
                    ]"
                />
            </template>
        </PageIndex>

        <!-- Filter Modal -->
        <FilterModal
            :open="showFilterModal"
            @update:open="showFilterModal = $event"
            module-type="cabor-kategori"
            :initial-filters="currentFilters"
            @filter="handleFilter"
        />
    </div>
</template>
