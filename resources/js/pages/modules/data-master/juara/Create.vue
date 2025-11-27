<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Juara', href: '/data-master/juara' },
    { title: 'Create Juara', href: '/data-master/juara/create' },
];
</script>

<template>
    <PageCreate title="Create Juara" :breadcrumbs="breadcrumbs" back-url="/data-master/juara">
        <Form mode="create" />
    </PageCreate>
</template>
