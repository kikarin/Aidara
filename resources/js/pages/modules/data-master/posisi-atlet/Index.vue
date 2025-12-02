<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Posis Atlet', href: '/data-master/posisi-atlet' },
];

const columns = [{ key: 'nama', label: 'Nama Posisi Atlet' }];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/posisi-atlet/${row.id}`),
        permission: 'Mst Posisi Atlet Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/posisi-atlet/${row.id}/edit`),
        permission: 'Mst Posisi Atlet Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Mst Posisi Atlet Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-master/posisi-atlet/destroy-selected', {
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
    await router.delete(`/data-master/posisi-atlet/${row.id}`, {
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
            title="Posisi Atlet"
            module-name="Mst Posisi Atlet"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/data-master/posisi-atlet/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/posisi-atlet"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
        />
    </div>
</template>
