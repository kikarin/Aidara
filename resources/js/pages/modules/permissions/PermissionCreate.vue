<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import PermissionForm from './PermissionForm.vue';

const props = defineProps<{
    category_permission_id?: number;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Permissions', href: '/menu-permissions/permissions' },
    { title: 'Add Permission', href: '#' },
];

const query = new URLSearchParams(window.location.search);
const categoryId = props.category_permission_id || Number(query.get('category_id') || 1);

const initialData = {
    category_permission_id: categoryId,
    name: '',
    code: '',
    sequence: 1,
};
</script>

<template>
    <PageCreate title="Add Permission" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/permissions">
        <PermissionForm mode="create" :initial-data="initialData" />
    </PageCreate>
</template>
