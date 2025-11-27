<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Tingkat', href: '/data-master/tingkat' },
    { title: 'Edit Tingkat', href: `/data-master/tingkat/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Tingkat" :breadcrumbs="breadcrumbs" back-url="/data-master/tingkat">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
