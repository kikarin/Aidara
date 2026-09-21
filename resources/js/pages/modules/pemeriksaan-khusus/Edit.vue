<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Pemeriksaan Fisik', href: '/pemeriksaan-khusus' },
    { title: 'Edit Pemeriksaan Fisik', href: `/pemeriksaan-khusus/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Edit Pemeriksaan Fisik" :breadcrumbs="breadcrumbs" back-url="/pemeriksaan-khusus">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>

