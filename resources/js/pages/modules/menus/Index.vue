<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Menus', href: '/menu-permissions/menus' },
];

const columns = [
    {
        key: 'no',
        label: 'No',
        searchable: false,
        orderable: false,
        visible: true,
        width: '50px',
    },
    {
        key: 'name',
        label: 'Nama',
        searchable: true,
        orderable: false,
        visible: true,
        format: (row: any) => {
            return `<span>${row.name}</span>`;
        },
    },
    {
        key: 'code',
        label: 'Kode',
        searchable: true,
        orderable: false,
        visible: true,
    },
    {
        key: 'order',
        label: 'Urutan',
        searchable: false,
        orderable: false,
        visible: true,
        width: '80px',
    },
    {
        key: 'url',
        label: 'URL',
        searchable: true,
        orderable: false,
        visible: true,
    },
    {
        key: 'permission',
        label: 'Permission',
        searchable: true,
        orderable: false,
        visible: true,
    },
];

const selected = ref<number[]>([]);
const pageIndex = ref();
const { toast } = useToast();

const actions = (row: any) => [
    {
        label: 'Detail',
        onClick: () => router.visit(`/menu-permissions/menus/${row.id}`),
        permission: 'Users Menu Detail',
    },
    {
        label: 'Edit',
        onClick: () => router.visit(`/menu-permissions/menus/${row.id}/edit`),
        permission: 'Users Menu Edit',
    },
    {
        label: 'Delete',
        onClick: () => {
            if (confirm(`Delete menu "${row.name}"?`)) {
                router.delete(`/menu-permissions/menus/${row.id}`);
            }
        },
        permission: 'Users Menu Delete',
    },
];

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/menu-permissions/menus/destroy-selected', {
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

const deleteMenu = async (row: any) => {
    await router.delete(`/menu-permissions/menus/${row.id}`, {
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
        title="Menus"
        module-name="Users Menu"
        :breadcrumbs="breadcrumbs"
        :columns="columns"
        :create-url="'/menu-permissions/menus/create'"
        :actions="actions"
        :selected="selected"
        @update:selected="(val) => (selected = val)"
        :on-delete-selected="deleteSelected"
        api-endpoint="/api/menus"
        ref="pageIndex"
        :on-toast="toast"
        :on-delete-row="deleteMenu"
        :disable-length="true"
        :hide-pagination="true"
        :hide-search="true"
    />
</template>
