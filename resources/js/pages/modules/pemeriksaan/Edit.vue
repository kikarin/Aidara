<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Pemeriksaan', href: '/pemeriksaan' },
    { title: 'Edit Pemeriksaan', href: `/pemeriksaan/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Edit Pemeriksaan" :breadcrumbs="breadcrumbs" back-url="/pemeriksaan">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
