<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Cabor Kategori', href: '/cabor-kategori' },
    { title: 'Daftar Atlet', href: `/cabor-kategori/${props.item.cabor_kategori_id}/atlet` },
    { title: 'Edit Posisi Atlet', href: `/cabor-kategori-atlet/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Posisi Atlet" :breadcrumbs="breadcrumbs" :back-url="`/cabor-kategori/${item.cabor_kategori_id}/atlet`">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
