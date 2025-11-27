<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Prestasi Pelatih', href: '/data-master/kategori-prestasi-pelatih' },
    { title: 'Create Kategori Prestasi Pelatih', href: '/data-master/kategori-prestasi-pelatih/create' },
];
</script>

<template>
    <PageCreate title="Create Kategori Prestasi Pelatih" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-prestasi-pelatih">
        <Form mode="create" />
    </PageCreate>
</template>
