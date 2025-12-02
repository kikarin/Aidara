<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Cabor Kategori', href: '/cabor-kategori' },
    { title: 'Daftar Tenaga Pendukung', href: `/cabor-kategori/${props.item.cabor_kategori_id}/tenaga-pendukung` },
    { title: 'Edit Jenis Tenaga Pendukung' },
];
</script>

<template>
    <PageEdit
        title="Jenis Tenaga Pendukung"
        :breadcrumbs="breadcrumbs"
        :back-url="`/cabor-kategori/${props.item.cabor_kategori_id}/tenaga-pendukung`"
    >
        <Form mode="edit" :initial-data="props.item" />
    </PageEdit>
</template>
