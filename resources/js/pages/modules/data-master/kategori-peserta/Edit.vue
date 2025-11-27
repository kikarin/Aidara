<script setup lang="ts">
import PageEdit from '@/pages/modules/base-page/PageEdit.vue';
import Form from './Form.vue';

const props = defineProps<{
    item: Record<string, any>;
}>();

const breadcrumbs = [
    { title: 'Data Master', href: '/data-master' },
    { title: 'Kategori Peserta', href: '/data-master/kategori-peserta' },
    { title: 'Edit Kategori Peserta', href: `/data-master/kategori-peserta/${props.item.id}/edit` },
];
</script>

<template>
    <PageEdit title="Kategori Peserta" :breadcrumbs="breadcrumbs" back-url="/data-master/kategori-peserta">
        <Form mode="edit" :initial-data="item" />
    </PageEdit>
</template>
