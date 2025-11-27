<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Posisi Atlet', href: '/data-master/posisi-atlet' },
    { title: 'Create Posisi Atlet', href: '/data-master/posisi-atlet/create' },
];
</script>

<template>
    <PageCreate title="Create Posisi Atlet" :breadcrumbs="breadcrumbs" back-url="/data-master/posisi-atlet">
        <Form mode="create" />
    </PageCreate>
</template>
