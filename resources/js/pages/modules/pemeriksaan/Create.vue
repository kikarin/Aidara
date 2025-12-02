<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Tambah Pemeriksaan', href: '/pemeriksaan/create' },
];
</script>

<template>
    <PageCreate title="Tambah Pemeriksaan" :breadcrumbs="breadcrumbs" back-url="/pemeriksaan">
        <Form mode="create" />
    </PageCreate>
</template>
