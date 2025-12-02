<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Unit Pendukung', href: '/data-master/jenis-unit-pendukung' },
    { title: 'Create Jenis Unit Pendukung', href: '/data-master/jenis-unit-pendukung/create' },
];
</script>

<template>
    <PageCreate title="Create Jenis Unit Pendukung" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-unit-pendukung">
        <Form mode="create" />
    </PageCreate>
</template>
