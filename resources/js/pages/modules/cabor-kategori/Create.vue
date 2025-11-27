<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Kategori', href: '/cabor-kategori' },
    { title: 'Create Kategori', href: '/cabor-kategori/create' },
];
</script>

<template>
    <PageCreate title="Create Kategori" :breadcrumbs="breadcrumbs" back-url="/cabor-kategori">
        <Form mode="create" />
    </PageCreate>
</template>
