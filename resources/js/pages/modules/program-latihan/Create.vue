<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Create Program Latihan', href: '/program-latihan/create' },
];
</script>

<template>
    <PageCreate title="Create Program Latihan" :breadcrumbs="breadcrumbs" back-url="/program-latihan">
        <Form mode="create" />
    </PageCreate>
</template>
