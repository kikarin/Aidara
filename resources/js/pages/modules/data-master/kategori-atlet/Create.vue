<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Atlet', href: '/data-master/kategori-atlet' },
    { title: 'Create Kategori Atlet', href: '/data-master/kategori-atlet/create' },
];
</script>

<template>
    <PageCreate title="Create Kategori Atlet" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-atlet">
        <Form mode="create" />
    </PageCreate>
</template>
