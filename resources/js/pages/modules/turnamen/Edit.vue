<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Turnamen', href: '/turnamen' },
    { title: 'Edit Turnamen', href: `/turnamen/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Turnamen" :breadcrumbs="breadcrumbs" back-url="/turnamen">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
