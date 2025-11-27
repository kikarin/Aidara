<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Create Cabor', href: '/cabor/create' },
];
</script>

<template>
    <PageCreate title="Create Cabor" :breadcrumbs="breadcrumbs" back-url="/cabor">
        <Form mode="create" />
    </PageCreate>
</template>
