<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Prestasi Pelatih', href: '/data-master/kategori-prestasi-pelatih' },
];

const columns = [{ key: 'nama', label: 'Nama Kategori Prestasi Pelatih' }];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/kategori-prestasi-pelatih/${row.id}`),
        permission: 'Mst Kategori Prestasi Pelatih Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/kategori-prestasi-pelatih/${row.id}/edit`),
        permission: 'Mst Kategori Prestasi Pelatih Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Mst Kategori Prestasi Pelatih Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-master/kategori-prestasi-pelatih/destroy-selected', {
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

const deleteRow = async (row: any) => {
    await router.delete(`/data-master/kategori-prestasi-pelatih/${row.id}`, {
        onSuccess: () => {
            toast({ title: 'Data berhasil dihapus', variant: 'success' });
            pageIndex.value.fetchData();
        },
        onError: () => {
            toast({ title: 'Gagal menghapus data.', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <PageIndex
            title="Kategori Prestasi Pelatih"
            module-name="Mst Kategori Prestasi Pelatih"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/data-master/kategori-prestasi-pelatih/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/kategori-prestasi-pelatih"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
        />
    </div>
</template>
