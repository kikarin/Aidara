<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Unit Pendukung', href: '/data-master/jenis-unit-pendukung' },
    { title: 'Edit Jenis Unit Pendukung', href: `/data-master/jenis-unit-pendukung/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Jenis Unit Pendukung" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-unit-pendukung">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
