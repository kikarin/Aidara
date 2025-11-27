<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Peserta', href: '/data-master/kategori-peserta' },
    { title: 'Create Kategori Peserta', href: '/data-master/kategori-peserta/create' },
];
</script>

<template>
    <PageCreate title="Create Kategori Peserta" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-peserta">
        <Form mode="create" />
    </PageCreate>
</template>
