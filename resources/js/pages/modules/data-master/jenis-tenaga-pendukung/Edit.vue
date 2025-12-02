<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Tenaga Pendukung', href: '/data-master/jenis-tenaga-pendukung' },
    { title: 'Edit Jenis Tenaga Pendukung', href: `/data-master/jenis-tenaga-pendukung/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Jenis Tenaga Pendukung" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-tenaga-pendukung">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
