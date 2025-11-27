<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        name: string;
        bg: string;
        init_page_login: string;
        is_allow_login: string;
        is_vertical_menu: string;
        created_at: string;
        created_by_user: {
            id: number;
            name: string;
        } | null;
        updated_at: string;
        updated_by_user: {
            id: number;
            name: string;
        } | null;
    };
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Roles', href: '/menu-permissions/roles' },
    { title: 'Detail Role', href: `/menu-permissions/roles/${props.item.id}` },
];

const fields = [
    { label: 'Name', value: props.item.name },
    { label: 'BG', value: props.item.bg },
    { label: 'Default Page', value: props.item.init_page_login },
    { label: 'Can Login', value: props.item.is_allow_login },
    { label: 'Menu Type', value: props.item.is_vertical_menu },
];

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/menu-permissions/roles/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/menu-permissions/roles/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Role berhasil dihapus', variant: 'success' });
            router.visit('/menu-permissions/roles');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus role', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Role"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/menu-permissions/roles'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
