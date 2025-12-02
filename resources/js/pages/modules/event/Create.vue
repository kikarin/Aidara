<script setup lang="ts">
import Form from './Form.vue';
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';

const breadcrumbs = [
    { title: 'Event', href: '/event' },
    { title: 'Create Event', href: '/event/create' },
];
</script>

<template>
    <PageCreate title="Create Event" :breadcrumbs="breadcrumbs" back-url="/event">
        <Form mode="create" />
    </PageCreate>
</template>

