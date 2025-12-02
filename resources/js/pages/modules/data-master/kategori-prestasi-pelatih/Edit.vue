<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Prestasi Pelatih', href: '/data-master/kategori-prestasi-pelatih' },
    { title: 'Edit Kategori Prestasi Pelatih', href: `/data-master/kategori-prestasi-pelatih/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori Prestasi Pelatih" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-prestasi-pelatih">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
