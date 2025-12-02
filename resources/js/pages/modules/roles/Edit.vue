<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    listBg: Record<string, string>;
    listInitPage: Record<string, string>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Roles', href: '/menu-permissions/roles' },
    { title: 'Edit Role', href: `/menu-permissions/roles/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Role" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/roles">
        <Form mode="edit" :initial-data="item" :listBg="listBg" :listInitPage="listInitPage" />
    </PageEdit>
</template>
