<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Permissions', href: '/menu-permissions/permissions' },
    { title: 'Edit Category', href: '#' },
];
</script>

<template>
    <PageEdit title="Permission Category" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/permissions">
        <Form mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
