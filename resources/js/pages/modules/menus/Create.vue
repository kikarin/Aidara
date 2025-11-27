<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    listUsersMenu: Record<number, string>;
    get_Permission: Record<number, string>;
}>();

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Menus', href: '/menu-permissions/menus' },
    { title: 'Create Menu', href: '/menu-permissions/menus/create' },
];
</script>

<template>
    <PageCreate title="Create Menu" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/menus">
        <Form mode="create" :listUsersMenu="listUsersMenu" :get_Permission="get_Permission" />
    </PageCreate>
</template>
