<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Cabor Kategori', href: '/cabor-kategori' },
    { title: 'Daftar Pelatih', href: `/cabor-kategori/${props.item.cabor_kategori_id}/pelatih` },
    { title: 'Edit Jenis Pelatih', href: `/cabor-kategori-pelatih/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Jenis Pelatih" :breadcrumbs="breadcrumbs" :back-url="`/cabor-kategori/${item.cabor_kategori_id}/pelatih`">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
