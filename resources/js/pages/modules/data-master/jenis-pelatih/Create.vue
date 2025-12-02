<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Pelatih', href: '/data-master/jenis-pelatih' },
    { title: 'Create Jenis Pelatih', href: '/data-master/jenis-pelatih/create' },
];
</script>

<template>
    <PageCreate title="Create Jenis Pelatih" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-pelatih">
        <Form mode="create" />
    </PageCreate>
</template>
