<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    get_Roles: Record<number, string>;
}>();

const breadcrumbs = [
    { title: 'Users', href: '/users' },
    { title: 'Create User', href: '/users/create' },
];
</script>

<template>
    <PageCreate title="Create User" :breadcrumbs="breadcrumbs" back-url="/users">
        <Form mode="create" :roles="get_Roles" />
    </PageCreate>
</template>
