<script setup lang="ts">
import { useToast } from '@/components/ui/toast/useToast';
import PageShow from '@/pages/modules/base-page/PageShow.vue';
import { router } from '@inertiajs/vue3';

const { toast } = useToast();

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Permissions', href: '/menu-permissions/permissions' },
    { title: 'Detail Category', href: '#' },
];

const fields = [
    { label: 'Name', value: props.item?.name || '-' },
    { label: 'Sequence', value: props.item?.sequence || '-' },
];

const actionFields = [
    { label: 'Created At', value: props.item?.created_at || '-' },
    { label: 'Created By', value: props.item?.created_by_user?.name || '-' },
    { label: 'Updated At', value: props.item?.updated_at || '-' },
    { label: 'Updated By', value: props.item?.updated_by_user?.name || '-' },
];

const handleEdit = () => {
    router.visit(`/menu-permissions/permissions/category/${props.item.id}/edit`);
};

const handleDelete = () => {
    router.delete(`/menu-permissions/permissions/${props.item.id}`, {
        onSuccess: () => {
            toast({ title: 'Kategori Permission berhasil dihapus', variant: 'success' });
            router.visit('/menu-permissions/permissions');
        },
        onError: () => {
            toast({ title: 'Gagal menghapus kategori Permission', variant: 'destructive' });
        },
    });
};
</script>

<template>
    <PageShow
        title="Permission Category"
        :breadcrumbs="breadcrumbs"
        :fields="fields"
        :action-fields="actionFields"
        back-url="/menu-permissions/permissions"
        :on-edit="handleEdit"
        :on-delete="handleDelete"
    />
</template>
