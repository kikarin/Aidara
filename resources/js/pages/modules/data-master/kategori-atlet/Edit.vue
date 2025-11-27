<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Atlet', href: '/data-master/kategori-atlet' },
    { title: 'Edit Kategori Atlet', href: `/data-master/kategori-atlet/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori Atlet" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-atlet">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
