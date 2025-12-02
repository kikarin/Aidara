<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Juara', href: '/data-master/juara' },
    { title: 'Edit Juara', href: `/data-master/juara/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Juara" :breadcrumbs="breadcrumbs" back-url="/data-master/juara">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
