<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Roles', href: '/menu-permissions/roles' },
];

const columns = [
    { key: 'name', label: 'Name', searchable: true, orderable: true, visible: true },
    { key: 'init_page_login', label: 'Default Page', searchable: true, orderable: true, visible: true },
    { key: 'is_allow_login', label: 'Can Login', searchable: false, orderable: true, visible: true },
];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/menu-permissions/roles/${row.id}`),
        permission: 'Role Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/menu-permissions/roles/${row.id}/edit`),
        permission: 'Role Edit',
    },
    {
        label: 'Set Permissions',
        onClick: () => router.visit(`/menu-permissions/roles/set-permissions/${row.id}`),
        permission: 'Role Set Permission',
    },
    {
        label: 'Delete',
        onClick: () => pageIndex.value.handleDeleteRow(row),
        permission: 'Role Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/menu-permissions/roles/destroy-selected', {
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

const deleteRole = async (row: any) => {
    await router.delete(`/menu-permissions/roles/${row.id}`, {
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
        title="Roles"
        module-name="Role"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/menu-permissions/roles/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/roles"
        ref="pageIndex"
        :on-toast="toast"
        :on-delete-row="deleteRole"
    />
</template>
