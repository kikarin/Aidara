<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Parameter', href: '/data-master/parameter' },
    { title: 'Create Parameter', href: '/data-master/parameter/create' },
];
</script>

<template>
    <PageCreate title="Create Parameter" :breadcrumbs="breadcrumbs" back-url="/data-master/parameter">
        <Form mode="create" />
    </PageCreate>
</template>
