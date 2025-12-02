<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

const breadcrumbs = [
    { title: 'Turnamen', href: '/turnamen' },
    { title: 'Create Turnamen', href: '/turnamen/create' },
];
</script>

<template>
    <PageCreate title="Create Turnamen" :breadcrumbs="breadcrumbs" back-url="/turnamen">
        <Form mode="create" />
    </PageCreate>
</template>
