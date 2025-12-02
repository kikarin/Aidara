<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Edit Cabor', href: `/cabor/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Cabor" :breadcrumbs="breadcrumbs" back-url="/cabor">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
