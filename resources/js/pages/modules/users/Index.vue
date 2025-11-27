<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageIndex from '@/pages/modules/base-page/PageIndex.vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

const breadcrumbs = [{ title: 'Users', href: '/users' }];

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    {
        key: 'role',
        label: 'Level saat ini',
        format: (row: any) => {
            return row.role || '-';
        },
    },
    {
        key: 'all_roles',
        label: 'Semua Role',
        sortable: false,
        format: (row: any) => {
            if (!row.all_roles) return '-';

            // Split roles dan buat badges
            const roles = row.all_roles.split(', ');
            const badges = roles
                .map(
                    (role: string) =>
                        `<span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full mr-1 mb-1">${role.trim()}</span>`,
                )
                .join('');

            return `<div class="flex flex-wrap">${badges}</div>`;
        },
    },
    {
        key: 'is_active',
        label: 'Status',
        format: (row: any) => {
            return row.is_active
                ? '<span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded-full">Active</span>'
                : '<span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">Inactive</span>';
        },
    },
];

const selected = ref<number[]>([]);

const pageIndex = ref();

const { toast } = useToast();

const page = usePage();
const currentUserId = computed(() => (page.props as any)?.auth?.user?.id);

const actions = (row: any) => {
    const baseActions = [
        {
            label: 'Detail',
            onClick: () => router.visit(`/users/${row.id}`),
            permission: 'Users Detail',
        },
        {
            label: 'Edit',
            onClick: () => router.visit(`/users/${row.id}/edit`),
            permission: 'Users Edit',
        },
        {
            label: 'Delete',
            onClick: () => pageIndex.value.handleDeleteRow(row),
            permission: 'Users Delete',
        },
    ];
    if (row.id !== currentUserId.value) {
        baseActions.push({
            label: 'Login As',
            onClick: () => router.visit(`/users/${row.id}/login-as`),
            permission: 'Users Show',
        });
    }
    return baseActions;
};

const deleteSelected = async () => {
    if (!selected.value.length) {
        return toast({ title: 'Pilih data yang akan dihapus', variant: 'destructive' });
    }

    try {
        const response = await axios.post('/users/destroy-selected', {
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

const deleteUser = async (row: any) => {
    await router.delete(`/users/${row.id}`, {
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
            title="Users"
            module-name="Users"
            :breadcrumbs="breadcrumbs"
            :columns="columns"
            :create-url="'/users/create'"
            :actions="actions"
            :selected="selected"
            @update:selected="(val) => (selected = val)"
            :on-delete-selected="deleteSelected"
            api-endpoint="/api/users"
            ref="pageIndex"
            :on-toast="toast"
            :on-delete-row="deleteUser"
            :show-import="false"
        />
    </div>
</template>
