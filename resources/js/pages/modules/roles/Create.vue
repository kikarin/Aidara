<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listBg: Record<string, string>;
    listInitPage: Record<string, string>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Roles', href: '/menu-permissions/roles' },
    { title: 'Create Role', href: '/menu-permissions/roles/create' },
];
</script>

<template>
    <PageCreate title="Create Role" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/roles">
        <Form mode="create" :listBg="listBg" :listInitPage="listInitPage" />
    </PageCreate>
</template>
