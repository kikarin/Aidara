<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
    jenisUnitPendukungs?: Array<{ id: number; nama: string }>;
}>();

const breadcrumbs = [
    { title: 'Unit Pendukung', href: '/unit-pendukung' },
    { title: 'Edit Unit Pendukung', href: `/unit-pendukung/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Unit Pendukung" :breadcrumbs="breadcrumbs" back-url="/unit-pendukung">
        <Form mode="edit" :initial-data="item" :jenis-unit-pendukungs="jenisUnitPendukungs" />
    </PageEdit>
</template>
