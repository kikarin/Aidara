<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Menu & Permissions', href: '#' },
    { title: 'Permissions', href: '/menu-permissions/permissions' },
    { title: 'Create Category', href: '#' },
];
</script>

<template>
    <PageCreate title="Create Permission Category" :breadcrumbs="breadcrumbs" back-url="/menu-permissions/permissions">
        <Form mode="create" />
    </PageCreate>
</template>
