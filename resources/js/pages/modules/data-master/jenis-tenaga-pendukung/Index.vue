<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Tenaga Pendukung', href: '/data-master/jenis-tenaga-pendukung' },
];

const columns = [{ key: 'nama', label: 'Nama Jenis Tenaga Pendukung' }];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/data-master/jenis-tenaga-pendukung/${row.id}`),
        permission: 'Mst Jenis Tenaga Pendukung Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/data-master/jenis-tenaga-pendukung/${row.id}/edit`),
        permission: 'Mst Jenis Tenaga Pendukung Edit',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Mst Jenis Tenaga Pendukung Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/data-master/jenis-tenaga-pendukung/destroy-selected', {
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
    await router.delete(`/data-master/jenis-tenaga-pendukung/${row.id}`, {
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
            title="Jenis Tenaga Pendukung"
            module-name="Mst Jenis Tenaga Pendukung"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/data-master/jenis-tenaga-pendukung/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/jenis-tenaga-pendukung"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteRow"
            :show-import="false"
        />
    </div>
</template>
