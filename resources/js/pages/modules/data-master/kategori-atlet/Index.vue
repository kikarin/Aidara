<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Atlet', href: '/data-master/kategori-atlet' },
];

const columns = [{ key: 'nama', label: 'Nama Kategori Atlet' }];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/kategori-atlet/${row.id}`),
        permission: 'Mst Kategori Atlet Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/kategori-atlet/${row.id}/edit`),
        permission: 'Mst Kategori Atlet Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Mst Kategori Atlet Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-master/kategori-atlet/destroy-selected', {
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
    await router.delete(`/data-master/kategori-atlet/${row.id}`, {
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
            title="Kategori Atlet"
            module-name="Mst Kategori Atlet"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/data-master/kategori-atlet/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/kategori-atlet"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
        />
    </div>
</template>
