<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Pemeriksaan Kondisi Fisik/Kebugaran', href: '/pemeriksaan-khusus' },
    { title: 'Tambah Pemeriksaan Kondisi Fisik', href: '/pemeriksaan-khusus/create' },
];
</script>
    
<template>
    <PageCreate title="Tambah Pemeriksaan Khusus" :breadcrumbs="breadcrumbs" back-url="/pemeriksaan-khusus">
        <Form mode="create" />
    </PageCreate>
</template>

