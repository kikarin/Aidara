<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import PermissionForm from './PermissionForm.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Permissions', href: '/menu-permissions/permissions' },
    { title: 'Edit Permission', href: '#' },
];
</script>

<template>
    <PageEdit title="Permission" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/permissions">
        <PermissionForm mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
