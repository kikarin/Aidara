<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Dokumen', href: '/data-master/jenis-dokumen' },
    { title: 'Create Jenis Dokumen', href: '/data-master/jenis-dokumen/create' },
];
</script>

<template>
    <PageCreate title="Create Jenis Dokumen" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-dokumen">
        <Form mode="create" />
    </PageCreate>
</template>
