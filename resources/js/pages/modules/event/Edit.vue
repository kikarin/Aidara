<script setup lang="ts">
import Form from './Form.vue';
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Event', href: '/event' },
    { title: 'Edit Event', href: `/event/${props.item.id}/edit` },
];  
</script>

<template>
    <PageEdit title="Event" :breadcrumbs="breadcrumbs" back-url="/event">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

