<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{ item: Record<string, any> }>();

const breadcrumbs = [
    { title: 'Cabor', href: '/cabor' },
    { title: 'Kategori', href: '/cabor-kategori' },
    { title: 'Edit Kategori', href: `/cabor-kategori/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori" :breadcrumbs="breadcrumbs" back-url="/cabor-kategori">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
