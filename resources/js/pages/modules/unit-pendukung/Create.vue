<script setup lang="ts">
import PageCreate from '@/pages/modules/base-page/PageCreate.vue';
import Form from './Form.vue';

defineProps<{
    jenisUnitPendukungs?: Array<{ id: number; nama: string }>;
}>();

const breadcrumbs = [
    { title: 'Unit Pendukung', href: '/unit-pendukung' },
    { title: 'Create Unit Pendukung', href: '/unit-pendukung/create' },
];
</script>

<template>
    <PageCreate title="Create Unit Pendukung" :breadcrumbs="breadcrumbs" back-url="/unit-pendukung">
        <Form mode="create" :jenis-unit-pendukungs="jenisUnitPendukungs" />
    </PageCreate>
</template>
