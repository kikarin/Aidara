<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const { toast } = useToast();

const props = defineProps<{
    item: {
        id: number;
        nama: string;
        kode: string;
        icon: string;
        rel_users_menu: {
            id: number;
            nama: string;
        } | null;
        permission: {
            id: number;
            name: string;
        } | null;
        url: string;
        urutan: number;
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

const menu = computed(() => props.item);

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Menus', href: '/menu-permissions/menus' },
    { title: 'Detail Menu', href: `/menu-permissions/menus/${props.item.id}` },
];

const fields = computed(() => [
    { label: 'Name', value: menu.value?.nama || '-' },
    { label: 'Code', value: menu.value?.kode || '-' },
    { label: 'Icon', value: menu.value?.icon || '-' },
    { label: 'Parent', value: menu.value?.rel_users_menu?.nama || '-' },
    { label: 'Permission', value: menu.value?.permission?.name || '-' },
    { label: 'URL', value: menu.value?.url || '-' },
    { label: 'Order', value: String(menu.value?.urutan || '-') },
]);

const actionFields = [
    { label: 'Created At', value: new Date(props.item.created_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Created By', value: props.item.created_by_user?.name || '-' },
    { label: 'Updated At', value: new Date(props.item.updated_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' }) },
    { label: 'Updated By', value: props.item.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/menu-permissions/menus/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/menu-permissions/menus/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Menu berhasil dihapus', variant: 'success' });
            router.visit('/menu-permissions/menus');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus menu', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Menu"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        :back-url="'/menu-permissions/menus'"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
