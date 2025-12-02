<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Tenaga Pendukung', href: '/data-master/jenis-tenaga-pendukung' },
    { title: 'Create Jenis Tenaga Pendukung', href: '/data-master/jenis-tenaga-pendukung/create' },
];
</script>

<template>
    <PageCreate title="Create Jenis Tenaga Pendukung" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-tenaga-pendukung">
        <Form mode="create" />
    </PageCreate>
</template>
