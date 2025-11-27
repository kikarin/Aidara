<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Activity Logs', href: '/menu-permissions/logs' },
];

const columns = [
    { key: 'event', label: 'Event', searchable: true, orderable: true, visible: true },
    { key: 'subject_type', label: 'Subject Type', searchable: true, orderable: true, visible: true },
    { key: 'subject_id', label: 'Subject ID', searchable: true, orderable: true, visible: true },
    { key: 'data', label: 'Data', searchable: true, orderable: true, visible: true },
    { key: 'causer_name', label: 'Causer', searchable: false, orderable: false, visible: true },
    { key: 'causer_role', label: 'Causer Role', searchable: false, orderable: false, visible: true },
    { key: 'created_at', label: 'Created At', searchable: false, orderable: true, visible: true },
];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/menu-permissions/logs/${row.id}`),
        permission: 'Activity Log Detail',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Activity Log Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/menu-permissions/logs/destroy-selected', {
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

const deleteLog = async (row: any) => {
    await router.delete(`/menu-permissions/logs/${row.id}`, {
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
    <PageIndex
        title="Activity Logs"
        module-name="Activity Log"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :actions="actions"
        api-endpoint="/api/activity-logs"
        ref="pageIndex"
        :selected="selected"
        @update:selected="(val) => (selected = val)"
        :on-delete-selected="deleteSelected"
        :on-delete-row="deleteLog"
    />
</template>
