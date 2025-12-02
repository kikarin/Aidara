<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Tingkat', href: '/data-master/tingkat' },
    { title: 'Create Tingkat', href: '/data-master/tingkat/create' },
];
</script>

<template>
    <PageCreate title="Create Tingkat" :breadcrumbs="breadcrumbs" back-url="/data-master/tingkat">
        <Form mode="create" />
    </PageCreate>
</template>
