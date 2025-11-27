<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Jenis Dokumen', href: '/data-master/jenis-dokumen' },
    { title: 'Edit Jenis Dokumen', href: `/data-master/jenis-dokumen/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Jenis Dokumen" :breadcrumbs="breadcrumbs" back-url="/data-master/jenis-dokumen">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
