<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Program Latihan', href: '/program-latihan' },
    { title: 'Edit Program Latihan', href: `/program-latihan/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Program Latihan" :breadcrumbs="breadcrumbs" back-url="/program-latihan">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
